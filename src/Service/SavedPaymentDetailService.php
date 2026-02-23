<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Entity\SavedPaymentDetail;
use App\Payment\Braintree\Braintree;
use App\Repository\SavedPaymentDetailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class SavedPaymentDetailService
{
    public function __construct(
        private readonly Braintree $braintree,
        private readonly EntityManagerInterface $em,
        private readonly SavedPaymentDetailRepository $savedPaymentDetailRepository,
    ) {}

    public function add(AppUser $user, string $nonce): array
    {
        [$firstName, $lastName] = $this->getNameFromEmail($user);

        $result = $this->braintree->createPaymentMethod($user, $nonce, [
            'firstName' => $firstName,
            'lastName'  => $lastName,
        ]);

        if (!$result['success']) {
            return [
                'success' => false,
                'data'    => null,
                'error'   => [
                    'message' => $result['error']['message'] ?? 'Unable to save card.',
                    'code'    => $result['code'] ?? null,
                ],
            ];
        }

        $data = $result['data'];

        $hasExistingCard = (bool) $this->savedPaymentDetailRepository
            ->findOneBy(['user' => $user]);

        $savedPaymentDetail = new SavedPaymentDetail();
        $savedPaymentDetail->setUser($user);
        $savedPaymentDetail->setToken($data['token']);
        $savedPaymentDetail->setType('card');
        $savedPaymentDetail->setCardType($data['cardType']);
        $savedPaymentDetail->setLast4($data['last4']);
        $savedPaymentDetail->setExpMonth((int) $data['expMonth']);
        $savedPaymentDetail->setExpYear((int) $data['expYear']);
        $savedPaymentDetail->setIsDefault(!$hasExistingCard);
        $savedPaymentDetail->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($savedPaymentDetail);
        $this->em->flush();

        return [
            'success' => true,
            'data'    => $data,
            'error'   => null,
        ];
    }

    public function deleteById(AppUser $user, int $id): array
    {
        $paymentDetail = $this->findUserPaymentDetailOrFail($user, $id);
        $wasDefault = $paymentDetail->isDefault();

        try {
            $this->braintree->deletePaymentMethod($paymentDetail->getToken());

            $this->em->remove($paymentDetail);
            $this->em->flush();

            if ($wasDefault) {
                $next = $this->savedPaymentDetailRepository->findNextDefaultForUser($user);

                if ($next) {
                    $next->setIsDefault(true);
                    $this->em->flush();
                }
            }

            return ['success' => true];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error'   => 'Failed to delete saved card',
                'details' => $e->getMessage(),
            ];
        }
    }

    public function setDefaultById(AppUser $user, int $id): void
    {
        $paymentDetail = $this->findUserPaymentDetailOrFail($user, $id);

        if ($paymentDetail->isDefault()) {
            return;
        }

        $now = new \DateTimeImmutable();

        foreach ($user->getSavedPaymentDetails() as $card) {
            $isDefault = $card->getId() === $paymentDetail->getId();

            if ($card->isDefault() !== $isDefault) {
                $card->setIsDefault($isDefault);
                $card->setUpdatedAt($now);
            }
        }

        $this->em->flush();
    }

    public function getSavedPaymentDetails(AppUser $user): array
    {
        return $this->savedPaymentDetailRepository->findByUser($user);
    }

    private function findUserPaymentDetailOrFail(AppUser $user, int $id): SavedPaymentDetail
    {
        $paymentDetail = $this->savedPaymentDetailRepository->find($id);

        if (!$paymentDetail) {
            throw new NotFoundHttpException('Payment detail not found.');
        }

        if ($paymentDetail->getUser()->getId() !== $user->getId()) {
            throw new AccessDeniedException('You do not own this payment detail.');
        }

        return $paymentDetail;
    }

    private function getNameFromEmail(AppUser $user): array
    {
        $firstName = trim((string) $user->getFirstName());
        $lastName  = trim((string) $user->getLastName());

        if ($firstName && $lastName) {
            return [$firstName, $lastName];
        }

        $fullName = trim((string) $user->getName());
        if ($fullName !== '') {
            $parts = preg_split('/\s+/', strtolower($fullName), -1, PREG_SPLIT_NO_EMPTY);

            [$derivedFirst, $derivedLast] = array_pad(
                array_map('ucfirst', $parts),
                2,
                null
            );

            if ($derivedFirst) {
                return [
                    $firstName ?: $derivedFirst,
                    $lastName  ?: ($derivedLast ?? 'User'),
                ];
            }
        }

        $email = (string) $user->getEmail();

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['Guest', 'User'];
        }

        $localPart = strtok($email, '@') ?: '';
        $localPart = str_replace(['.', '_', '-'], ' ', $localPart);

        $parts = preg_split('/\s+/', strtolower($localPart), -1, PREG_SPLIT_NO_EMPTY);

        [$derivedFirst, $derivedLast] = array_pad(
            array_map('ucfirst', $parts),
            2,
            null
        );

        return [
            $firstName ?: ($derivedFirst ?? 'Guest'),
            $lastName  ?: ($derivedLast  ?? 'User'),
        ];
    }
}
