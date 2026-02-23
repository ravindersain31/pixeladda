<?php

namespace App\Controller\Admin\Component;

use App\Constant\CogsConstant;
use App\Entity\Reports\DailyCogsReport;
use App\Form\Admin\Reports\CogsDailyDataUpdateType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "AdminDailyCogUpdateForm",
    template: "admin/components/daily-cog-update.html.twig"
)]
class DailyCogUpdateFormComponent extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp]
    public DailyCogsReport $dailyCog;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(CogsDailyDataUpdateType::class, null, [
            'materialBreakdown' => $this->dailyCog->getMaterialCostBreakdown(),
        ]);
    }

    #[LiveAction]
    public function save(): Response
    {
        $this->submitForm();
        $form = $this->getForm();
        $sheetsSingleSidedPrint = $form->get('sheetsSingleSidedPrint')->getData();
        $sheetsDoubleSidedPrint = $form->get('sheetsDoubleSidedPrint')->getData();
        $wireStakeUsed = $form->get('stakes')->getData();

        $sheetsUsed = $sheetsSingleSidedPrint + $sheetsDoubleSidedPrint;
        $sheetsCost = ceil($sheetsUsed) * CogsConstant::FULL_SHEET_COST;
        $inkCost = ($sheetsSingleSidedPrint * CogsConstant::INK_COST_SINGLE_SIDED) + ($sheetsDoubleSidedPrint * CogsConstant::INK_COST_DOUBLE_SIDED);
        $wireStakeCost = $wireStakeUsed * CogsConstant::FULL_SHEET_COST;

        $this->dailyCog->setMaterialCost($sheetsCost + $inkCost + $wireStakeCost);

        $this->dailyCog->setMaterialCostBreakdown([
            ...$this->dailyCog->getMaterialCostBreakdown(),
            'sheets' => [
                'sheetsUsed' => ceil($sheetsUsed),
                'sheetsUsedActual' => $sheetsUsed,
                'singleSheetCost' => CogsConstant::FULL_SHEET_COST,
                'sheetsCost' => $sheetsCost,
            ],
            'inkCost' => [
                'sheetsSingleSidedPrint' => floatval($sheetsSingleSidedPrint),
                'sheetsDoubleSidedPrint' => floatval($sheetsDoubleSidedPrint),
                'inkCostDoubleSided' => CogsConstant::INK_COST_DOUBLE_SIDED,
                'inkCostSingleSided' => CogsConstant::INK_COST_SINGLE_SIDED,
                'inkCost' => $inkCost,
            ],
            'wireStake' => [
                'wireStakeUsed' => intval($wireStakeUsed),
                'singleWireStakeCost' => CogsConstant::FULL_SHEET_COST,
                'wireStakeCost' => $wireStakeCost
            ]
        ]);


        $this->dailyCog->setHasCustomData(true);
        $this->entityManager->persist($this->dailyCog);
        $this->entityManager->flush();

        $this->addFlash('success', 'Cogs data successfully updated for date: ' . $this->dailyCog->getDate()->format('M d, Y'));
        return $this->redirectToRoute('admin_report_cogs_view', ['month' => $this->dailyCog->getMonth()->getId()]);
    }
}
