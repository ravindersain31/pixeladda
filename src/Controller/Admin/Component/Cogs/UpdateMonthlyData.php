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
use App\Form\Admin\Reports\CogsMonthlyDataUpdateType;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[AsLiveComponent(
    name: "UpdateMonthlyData",
    template: "admin/components/cogs/monthly-data.html.twig"
)]
class UpdateMonthlyData extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentWithFormTrait;
    use ValidatableComponentTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?MonthlyCogsReport $monthlyCogsReport;

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(CogsMonthlyDataUpdateType::class, $this->monthlyCogsReport);
    }

        public function hasValidationErrors(): bool
    {
        return $this->getForm()->isSubmitted() && !$this->getForm()->isValid();
    }

    #[LiveAction]
    public function save(): Response
    {
        $this->validate();
        $this->submitForm();
        $form = $this->getForm();
        $data = $form->getData();

        $this->entityManager->persist($this->monthlyCogsReport);
        $this->entityManager->flush();

        $this->addFlash('success', 'Monthly data has been updated successfully.');

        return $this->redirectToRoute('admin_report_cogs_view', ['month' => $this->monthlyCogsReport->getId()]);
    }
}