<?php

namespace App\Component\Customer;

use App\Entity\Order;
use App\Form\RepeatOrderType;
use App\Service\CartManagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent(
    name: "RepeatOrderForm",
    template: "components/repeat_order.html.twig"
)]
class RepeatOrderForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;
    use ValidatableComponentTrait;

    #[LiveProp]
    public ?string $flashMessage = null;
    public bool $isSuccessful = false;
    public ?string $flashError = 'success';

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly MailerInterface $mailer, private readonly CartManagerService $cartManagerService)
    {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(RepeatOrderType::class);
    }

    #[LiveAction]
    public function cloneCart()
    {
        $this->validate();
        $this->submitForm();
        $form = $this->getForm();
        $data = $form->getData();

        $order = $this->entityManager->getRepository(Order::class)->findOneBy(['orderId' => $data['orderId']]);
        if(empty($order) || empty($order->getCart())){
            $this->isSuccessful = true;
            $this->flashError = 'danger';
            $this->flashMessage = 'Order does not exist. Please try again or call +1-877-958-1499 for assistance.';
        }else{
            $this->isSuccessful = false;
            $cart = $this->cartManagerService->deepClone($order->getCart(), isRepeatOrder: true, order: $order);
            $this->dispatchBrowserEvent('modal:open',[
                'cartUrl' => $this->generateUrl('cart', ['id' => $cart->getCartId()], UrlGeneratorInterface::ABSOLUTE_URL)
            ]);
        }
        
        $this->resetForm();
    }
}