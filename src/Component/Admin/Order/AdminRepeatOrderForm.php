<?php
namespace App\Component\Admin\Order;

use App\Entity\Order;
use App\Form\Admin\Order\AdminRepeatOrderType;
use App\Repository\OrderRepository;
use App\Service\CartManagerService;
use App\Service\OrderLogger;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent(
    name: "AdminRepeatOrderForm",
    template: "admin/order/view/action/component/_repeat_order.html.twig"
)]
class AdminRepeatOrderForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;
    use ValidatableComponentTrait;

    #[LiveProp]
    public ?Order $order = null;


    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly CartManagerService $cartManagerService,
        private readonly OrderLogger $orderLogger,
        private readonly OrderRepository $repository,
        private readonly OrderService $orderService
    ){}

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(AdminRepeatOrderType::class);
    }

    #[LiveAction]
    public function repeatOrder()
    {
        $this->validate();
        $this->submitForm();
        $form = $this->getForm();
        $data = $form->getData();

        $order = $this->order;
        if (empty($order)) {
            $this->addFlash('danger', 'Order not found.');
            return;
        }

        $orderID = $data['orderId'] ?? null;

        // Check if a user-entered order ID exists and is unique
        if (!empty($orderID)) {
            $isOrderIDExist = $this->entityManager->getRepository(Order::class)->findOneBy(['orderId' => $orderID]);
            if ($isOrderIDExist) {
                $this->addFlash('danger', 'Order ID already exists. Please create a new Order ID.');
                return;
            }
        } else {
            $orderID = $this->orderService->generateOrderId();
        }

        if ($order->isIsManual()) {
            $newOrder = $this->orderService->deepCloneOrder($order);
            $newOrder->setOrderId($orderID);

            $this->orderLogger->setOrder($order);
            $message = sprintf(
                'New Order ID %s has been created from Order ID %s and the order channel is %s',
                $newOrder->getOrderId(),
                $order->getOrderId(),
                $order->getOrderChannel()->label()
            );

            if ($order->getParent()) {
                $message .= sprintf(' and this is the sub-order of Order ID %s', $order->getParent()->getOrderId());
            }

            $this->orderLogger->log($message);
            $this->repository->save($newOrder, true);
            $this->addFlash('success', 'Repeat Order has been created.');

            return $this->redirectToRoute('admin_order_overview', ['orderId' => $newOrder->getOrderId()]);
        }

        $this->addFlash('success', 'Order has been repeated');
        $this->entityManager->refresh($this->order);
    }

}