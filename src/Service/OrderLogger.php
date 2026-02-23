<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderLog;
use App\Entity\User;
use App\Helper\AddressHelper;
use App\Helper\DifferenceHighlighterHelper;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class OrderLogger
{
    private Order|null $order = null;

    public function __construct(
        public readonly EntityManagerInterface      $entityManager,
        public readonly RequestStack                $requestStack,
        public readonly AddressHelper               $addressHelper,
        public readonly DifferenceHighlighterHelper $differenceHighlighter,
        private readonly TokenStorageInterface      $tokenStorage,
    )
    {
    }

    public function setOrder(Order $order): self
    {
        $this->order = $order;
        return $this;
    }

    public function log(string $content, ?User $changedBy = null, string $type = OrderLog::ORDER_UPDATED): self
    {
        if (!$this->order) {
            throw new Exception('Order not set');
        }

        if (!$changedBy) {
            $changedBy = $this->tokenStorage->getToken()?->getUser();
        }

        $note = new OrderLog();
        $note->setOrder($this->order);
        $note->setChangedBy($changedBy);
        $note->setContent($content);
        $note->setType($type);

        $this->entityManager->persist($note);
        $this->entityManager->flush();
        return $this;
    }

    public function addFlash(string $type, string $message): void
    {
        $session = $this->requestStack->getSession();
        $flashBag = $session->getFlashBag();
        $flashBag->add($type, $message);
    }

}