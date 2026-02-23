<?php

namespace App\Controller\Admin\Component\Order\History;

use App\Form\Admin\Order\OrdeHistoryType;
use App\Repository\OrderRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "OrderHistorySidesForm",
    template: "admin/components/order/history/order_history_sides_form.html.twig"
)]
class OrderHistorySidesForm extends AbstractController
{
    
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;
    use ValidatableComponentTrait;
    
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserService $userService,
        private readonly MailerInterface $mailer,
        private readonly OrderRepository $orderRepository,
    ) {
    }

    #[LiveProp]
    public ?string $flashMessage = null;
    public ?string $flashError = 'success';
    public bool $isSuccessful = false;

    public ?\DateTime $startDate = null;

    public ?\DateTime $endDate = null;

    #[LiveProp]
    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(OrdeHistoryType::class);
    }

    public function hasValidationErrors(): bool
    {
        return $this->getForm()->isSubmitted() && !$this->getForm()->isValid();
    }

    public function getStartDate(): \DateTime
    {
        return $this->getForm()->get('startDate')->getData() ?? (new \DateTime())->modify('-1 days');
    }


    public function getEndDate(): \DateTime
    {
        return $this->getForm()->get('endDate')->getData() ?? (new \DateTime());
    }

    public function getOrders(?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $this->startDate = $startDate ?? $this->getForm()->get('startDate')->getData() ?? (new \DateTime())->modify('-1 days');
        $this->endDate = $endDate ?? $this->getForm()->get('endDate')->getData() ?? (new \DateTime());

        $orderStatuses = $this->getForm()->get('orderStatus')->getData() ?? [];
        $paymentStatuses = $this->getForm()->get('paymentStatus')->getData() ?? [];

        $startDate = new \DateTimeImmutable(($this->startDate)->format('Y-m-d H:i:s'));
        $endDate = new \DateTimeImmutable(($this->endDate)->format('Y-m-d H:i:s'));

        $orders = $this->orderRepository->filterOrderSelective(
            fromDate: $startDate,
            endDate: $endDate,
            result: true,
            status: $orderStatuses,
            paymentStatus: $paymentStatuses,
        );

        return $orders;
    }

    public function getOrdersBySides(): array
    {
        $orders = $this->getOrders();
        // Flatten order items from all orders into a single array
        $orderItems = array_reduce($orders, function ($carry, $order) {
            return array_merge($carry, $order['orderItems']);
        }, []);

        // Reduce the flattened order items to aggregate quantities by size
        $ordersBySides = array_reduce($orderItems, function ($carry, $orderItem) {
            if (!empty($orderItem['addOns']) && isset($orderItem['addOns']['sides'])) {
                $name = $orderItem['addOns']['sides']['key'];
                if (!isset($carry[$name])) {
                    $carry[$name] = [
                        'totalQuantity' => 0,
                        'orderIds' => []
                    ];
                }
                $carry[$name]['totalQuantity'] += $orderItem['quantity'];

                if (!in_array($orderItem['orderId'], $carry[$name]['orderIds'])) {
                    $carry[$name]['orderIds'][] = $orderItem['orderId'];
                }

            }

            return $carry;
        }, []);


        return [
            'ordersBySides' => $ordersBySides,
            'totalOrders' => count($orders)
        ];
    }

    #[LiveAction]
    public function search(): void
    {
        $this->startDate = $this->getForm()->get('startDate')->getData();
        $this->endDate = $this->getForm()->get('endDate')->getData();

        try {



        } catch (\Exception $e) {
            $this->flashMessage = $e->getMessage();
            $this->flashError = 'danger';
        }
    }

}