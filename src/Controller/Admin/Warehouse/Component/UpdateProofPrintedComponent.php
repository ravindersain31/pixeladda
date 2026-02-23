<?php

namespace App\Controller\Admin\Warehouse\Component;

use App\Entity\Admin\WarehouseOrder;
use App\Service\Admin\WarehouseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: "UpdateProofPrintedComponent",
    template: "admin/warehouse/components/update-proof-printed-checkbox.html.twig"
)]
class UpdateProofPrintedComponent extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public WarehouseOrder $warehouseOrder;

    #[LiveProp(writable: true)]
    public ?bool $proof_printed = false;

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly WarehouseService $warehouseService)
    {
    }

    public function getProofPrinted(): bool
    {
        return $this->warehouseOrder->isIsProofPrinted();
    }

    #[LiveAction]
    public function save(): void
    {
        $this->denyAccessUnlessGranted('warehouse_update_proof_printed_update');

        $this->warehouseOrder->setIsProofPrinted($this->proof_printed);
        $this->entityManager->persist($this->warehouseOrder);
        $this->entityManager->flush();

    }

}
