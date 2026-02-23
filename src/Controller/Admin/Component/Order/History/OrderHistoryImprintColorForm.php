<?php

namespace App\Controller\Admin\Component\Order\History;

use App\Constant\Editor\Addons;
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
    name: "OrderHistoryImprintColorForm",
    template: "admin/components/order/history/order_history_imprint_color_form.html.twig"
)]
class OrderHistoryImprintColorForm extends AbstractController
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
    ) {}

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
        return $this->getForm()->get('startDate')->getData() ?? (new \DateTime())->modify('-31 days');
    }


    public function getEndDate(): \DateTime
    {
        $startDate = $this->getStartDate();
        return (clone $startDate)->modify('+31 days');
    }

    public function getOrders(?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $this->startDate = $startDate ?? $this->getStartDate();
        $this->endDate = $endDate ?? $this->getEndDate();
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

    public function getOrdersByImprintColor(?string $startDate = null, ?string $endDate = null): array
    {

        $startDate = $startDate ? new \DateTime($startDate) : null;
        $endDate = $endDate ? new \DateTime($endDate) : null;

        $orders = $this->getOrders(startDate: $startDate, endDate: $endDate);

        // Flatten order items from all orders into a single array
        $orderItems = array_reduce($orders, function ($carry, $order) {
            return array_merge($carry, $order['orderItems']);
        }, []);

        // Reduce the flattened order items to aggregate quantities by size
        $ordersByImprintColor = array_reduce($orderItems, function ($carry, $orderItem) {
            if (!empty($orderItem['addOns']) && isset($orderItem['addOns']['imprintColor'])) {
                $name = $orderItem['addOns']['imprintColor']['key'];

                if ($name === 'NONE') {
                    return $carry;
                }

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

        $imprintColorKeys = Addons::getImprintColors();

        $orderedByImprintColor = array_reduce($imprintColorKeys, function ($carry, $key) use ($ordersByImprintColor) {
            if (isset($ordersByImprintColor[$key])) {
                $carry[$key] = $ordersByImprintColor[$key];
            }
            return $carry;
        }, []);

        return [
            'ordersByImprintColor' => $orderedByImprintColor,
            'totalOrders' => count($orders)
        ];
    }


    #[LiveAction]
    public function search(): void
    {
        $start = $this->getForm()->get('startDate')->getData();
        if ($start instanceof \DateTimeInterface) {
            $end = (clone $start)->modify('+31 days');
            $this->getForm()->get('endDate')->setData($end);

            $this->startDate = $start;
            $this->endDate   = $end;
        }
        try {
        } catch (\Exception $e) {
            $this->flashMessage = $e->getMessage();
            $this->flashError = 'danger';
        }
    }
}
