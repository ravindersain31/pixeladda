<?php

namespace App\Controller\Admin\Component\Cogs;

use App\Controller\Cron\UpdateCogsReportController;
use App\Entity\Order;
use App\Entity\Admin\ShippingInvoice;
use App\Entity\OrderShipment;
use App\Entity\Reports\DailyCogsReport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use App\Form\Admin\Cogs\RefreshCogsReportType;
use App\Repository\OrderRepository;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[AsLiveComponent(
    name: "RefreshCogsReport",
    template: "admin/components/cogs/upload-shipping-invoice.html.twig"
)]
class RefreshCogsReport extends UpdateCogsReportController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;
    use ValidatableComponentTrait;

    #[LiveProp]
    public DailyCogsReport $day;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(RefreshCogsReportType::class);
    }

    #[LiveAction]
    public function save(Request $request): Response
    {
        $this->submitForm();
        $this->validate();
        $form = $this->getForm();
        $data = $form->getData();
        if(!$data['UPDATE_COGS_REPORT']) {
            $this->addFlash('danger', 'Cogs Report '. $this->day->getDate()->format('d M Y') . ' not updated');
            return $this->redirectToRoute('admin_report_cogs_view', ['month' => $this->day->getMonth()->getId()]);
        }
        $this->updateShipping();
        $this->updateCogsReport();

        $this->addFlash('success', 'Cogs Report '. $this->day->getDate()->format('d M Y') . ' updated successfully');
        return $this->redirectToRoute('admin_report_cogs_view', ['month' => $this->day->getMonth()->getId()]);
    }

    private function updateShipping() {
        $dayDate = new \DateTimeImmutable($this->day->getDate()->format('Y-m-d'));
        $startOfDay = $dayDate->setTime(0, 0, 0);
        $endOfDay = $dayDate->setTime(23, 59, 59);
        $orders = $this->entityManager->getRepository(Order::class)->filterOrder(fromDate: $startOfDay, endDate: $endOfDay)->getResult();

        foreach ($orders as $order) {
            $this->updateEasyPostShipmentCost($order);
        }

        $this->entityManager->flush();

        $this->updateCogsReport();
    }

    /**
     * Returns the cost of an order shipment as determined by EasyPost.
     *
     * @param Order $order
     *
     * @return float
     */
    private function updateEasyPostShipmentCost(Order $order): void
    {
        $orderShipment = $this->entityManager->getRepository(OrderShipment::class)->findOneBy([
            'order' => $order,
            'shipmentOrderId' => $order->getShippingOrderId(),
        ]);

        if (!$orderShipment) {
            return;
        }

        $selectedRate = $orderShipment->getSelectedRate();
        $shipmentCost = $selectedRate['rate'] ?? 0;

        $order->setShippingCost($shipmentCost);
        $order->setCompanyShippingCost($shipmentCost);


        $this->entityManager->persist($order);
    }

    private function updateCogsReport() {
        $this->updateDailyCogsReport(new \DateTimeImmutable($this->day->getDate()->format('Y-m-d')));
    }
}
