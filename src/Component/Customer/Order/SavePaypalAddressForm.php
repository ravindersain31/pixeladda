<?php

namespace App\Component\Customer\Order;

use App\Entity\Country;
use App\Entity\Order;
use App\Form\Order\SavePaypalAddressType;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "SavePaypalAddressForm",
    template: "components/order/_save_paypal_address.html.twig"
)]
class SavePaypalAddressForm extends AbstractController
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use ValidatableComponentTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderRepository $orderRepository,
    ){}

    #[LiveProp]
    #[NotNull]
    public ?Order $order = null;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(SavePaypalAddressType::class, [], [
            'country' => $this->getCountry(),
            'order' => $this->order,
        ]);
    }

    public function hasValidationErrors(): bool
    {
        return $this->getForm()->isSubmitted() && !$this->getForm()->isValid();
    }

    public function getCountry(): ?Country
    {
        $defaultCountryCode = $this->order->getShippingAddress()['country'];
        return $this->entityManager->getRepository(Country::class)->findOneBy(['isoCode' => $defaultCountryCode]);
    }

    #[LiveAction]
    public function save(Request $request): Response
    {
        $this->submitForm();
        $form = $this->getForm();

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $order = $this->order;
            $phone = $data['phone'];
            $state = isset($data['state']) ? $data['state']->getIsoCode() : null;

            try {
                $updatedShippingAddress = array_merge($order->getShippingAddress(), [
                    'phone' => $phone,
                ], $state ? ['state' => $state] : []);

                $updatedBillingAddress = array_merge($order->getBillingAddress(), [
                    'phone' => $phone,
                ], $state ? ['state' => $state] : []);

                $order->setShippingAddress($updatedShippingAddress);
                $order->setBillingAddress($updatedBillingAddress);
                $order->setTextUpdatesNumber($phone);

                $this->entityManager->persist($order);
                $this->entityManager->flush();

                $message = isset($state)
                    ? 'Address and Phone number has been updated successfully.'
                    : 'Phone number has been updated successfully.';

                $this->addFlash('success', $message);
            } catch (\Exception $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->redirectToRoute('order_view', ['oid' => $this->order->getOrderId()]);
    }
}
