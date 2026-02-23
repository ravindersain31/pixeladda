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
    name: "OrderHistoryGrommetsForm",
    template: "admin/components/order/history/order_history_grommets_form.html.twig"
)]
class OrderHistoryGrommetsForm extends AbstractController
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
        return $this->getForm()->get('startDate')->getData() ?? (new \DateTime())->modify('-31 days');
    }


    public function getEndDate(): \DateTime
    {
        $startDate = $this->getStartDate();

        return (clone $startDate)->modify('+31 days');
    }

    public function getOrders(?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $start = $startDate ?? $this->getStartDate();
        $end   = $endDate   ?? $this->getEndDate();

        if ($start->diff($end)->days > 31) {
            $end = (clone $start)->modify('+31 days');
        }

        $startImmutable = \DateTimeImmutable::createFromMutable($start);
        $endImmutable   = \DateTimeImmutable::createFromMutable($end);

        return $this->orderRepository->filterOrderSelective(
            fromDate: $startImmutable,
            endDate: $endImmutable,
            result: true,
            status: $this->getForm()->get('orderStatus')->getData() ?? [],
            paymentStatus: $this->getForm()->get('paymentStatus')->getData() ?? [],
        );
    }

    public function getOrdersByGrommets(?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ? new \DateTime($startDate) : null;
        $end   = $endDate   ? new \DateTime($endDate)   : null;

        $orders = $this->getOrders($start, $end);

        if (!$orders) {
            return [
                'ordersByGrommets' => [],
                'totalOrders' => 0
            ];
        }

        $orderItems = array_reduce($orders, fn ($c, $o) =>
            array_merge($c, $o['orderItems'] ?? []), []);

        $ordersByGrommets = [];

        foreach ($orderItems as $item) {
            if (empty($item['addOns']['grommets'])) continue;

            $key = $item['addOns']['grommets']['key'];
            if ($key === 'NONE') continue;

            $ordersByGrommets[$key]['totalQuantity']
                = ($ordersByGrommets[$key]['totalQuantity'] ?? 0) + $item['quantity'];

            $ordersByGrommets[$key]['orderIds'][] = $item['orderId'];
            $ordersByGrommets[$key]['orderIds'] = array_unique($ordersByGrommets[$key]['orderIds']);
        }

        return [
            'ordersByGrommets' => $ordersByGrommets,
            'totalOrders' => count($orders)
        ];
    }


    #[LiveAction]
    public function search(): void
    {
        $startDate = $this->getForm()->get('startDate')->getData();

        if ($startDate instanceof \DateTimeInterface) {
            $endDate = (clone $startDate)->modify('+31 days');

            $this->getForm()->get('endDate')->setData($endDate);
        }

        $this->startDate = $startDate;
        $this->endDate = $endDate ?? null;
        try {



        } catch (\Exception $e) {
            $this->flashMessage = $e->getMessage();
            $this->flashError = 'danger';
        }
    }

}