<?php

namespace App\Controller\Admin\Component\Cogs;

use App\Controller\Cron\UpdateCogsReportController;
use App\Entity\Order;
use App\Entity\OrderShipment;
use App\Entity\Reports\MonthlyCogsReport;
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
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[AsLiveComponent(
    name: "RefreshCogsMonthReport",
    template: "admin/components/cogs/cogs-report-month.html.twig"
)]
class RefreshCogsMonthReport extends UpdateCogsReportController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;
    use ValidatableComponentTrait;

    #[LiveProp]
    public MonthlyCogsReport $month;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(RefreshCogsReportType::class);
    }

    #[Route(path: '/update-cogs/month/report', name: 'update_cogs_month_report')]
    public function monthReport(Request $request): Response
    {
        $date = new \DateTimeImmutable();
        $date = $date->modify('-1 day');
        $customDate = \DateTimeImmutable::createFromFormat('Y-m-d', $request->get('date', $date->format('Y-m-d')));
        if ($customDate !== false) {
            $date = $customDate;
        }

        $this->updateDailyCogsReport($date);

        return $this->json(['status' => 'ok', 'date' => $date->format('Y-m-d')]);
    }
}