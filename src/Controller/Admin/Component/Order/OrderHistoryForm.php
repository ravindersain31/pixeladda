<?php

namespace App\Controller\Admin\Component\Order;

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
    name: "OrderHistoryForm",
    template: "admin/components/order/history/order_history_form.html.twig"
)]
class OrderHistoryForm extends AbstractController
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

    public function getOrdersBySizes(): array
    {
        $orders = $this->getOrders();

        // Flatten order items from all orders into a single array
        $orderItems = array_reduce($orders, function ($carry, $order) {
            return array_merge($carry, $order['orderItems']);
        }, []);

        // Reduce the flattened order items to aggregate quantities by size
        $ordersBySizes = array_reduce($orderItems, function ($carry, $orderItem) {
            // Determine size
            if ($orderItem['product']['parentSku'] === 'SAMPLE') {
                $size = 'SAMPLE';
            } elseif ($orderItem['product']['parentSku'] === 'CUSTOM-SIZE') {
                $size = 'CUSTOM-SIZE';
            } else {
                $size = !empty($orderItem['product']['name']) ? $orderItem['product']['name'] : $orderItem['product']['parentSku'];
            }

            // Initialize customSizeFormatted as null
            $customSizeFormatted = null;

            // Check if the metadata indicates that this size is a custom size
            if (!empty($orderItem['metaData']) && isset($orderItem['metaData']['isCustomSize']) && $orderItem['metaData']['isCustomSize'] && !empty($orderItem['metaData']['customSize']) && isset($orderItem['metaData']['customSize'])) {
                $customSizeFormatted = sprintf('%dx%d', $orderItem['metaData']['customSize']['templateSize']['width'], $orderItem['metaData']['customSize']['templateSize']['height']);
            }

            // Initialize size entry if not already set
            if (!isset($carry[$size])) {
                $carry[$size] = [
                    'totalQuantity' => 0,
                    'orderIds' => [],
                    'sizes' => []
                ];
            }
            // Accumulate total quantity
            $carry[$size]['totalQuantity'] += $orderItem['quantity'];

            // Collect unique order IDs
            if (!in_array($orderItem['orderId'], $carry[$size]['orderIds'])) {
                $carry[$size]['orderIds'][] = $orderItem['orderId'];
            }

            // Add customSizeFormatted to sizes if it's set
            if ($customSizeFormatted) {
                if (!isset($carry[$size]['sizes'][$customSizeFormatted])) {
                    $carry[$size]['sizes'][$customSizeFormatted] = [
                        'quantity' => 0
                    ];
                }
                $carry[$size]['sizes'][$customSizeFormatted]['quantity'] += $orderItem['quantity'];
            }

            return $carry;
        }, []);

        $customOrder = ['6x18', '6x24', '9x12', '9x24', '12x12', '12x18', '18x12', '18x24', '24x18', '24x24', 'CUSTOM-SIZE'];

        // Custom sorting function
        uksort($ordersBySizes, function ($a, $b) use ($customOrder) {
            $aIndex = array_search($a, $customOrder);
            $bIndex = array_search($b, $customOrder);

            // If $a or $b is in the custom order, prioritize them
            if ($aIndex !== false && $bIndex === false) {
                return -1;
            } elseif ($aIndex === false && $bIndex !== false) {
                return 1;
            } elseif ($aIndex !== false && $bIndex !== false) {
                return $aIndex - $bIndex;
            }

            // If neither is in the custom order, sort alphabetically
            return strcmp($a, $b);
        });

        return [
            'ordersBySizes' => $ordersBySizes,
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