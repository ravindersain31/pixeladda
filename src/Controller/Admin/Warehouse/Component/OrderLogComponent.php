<?php

namespace App\Controller\Admin\Warehouse\Component;

use App\Entity\Admin\WarehouseOrder;
use App\Entity\Admin\WarehouseOrderLog;
use App\Form\Admin\Warehouse\OrderLogType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "AdminWarehouseOrderLog",
    template: "admin/warehouse/components/order-log-form.html.twig"
)]
class OrderLogComponent extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp]
    public ?WarehouseOrder $warehouseOrder;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(OrderLogType::class);
    }

    #[LiveAction]
    public function save(): Response|null
    {
        $this->submitForm();
        $form = $this->getForm();
        if ($form->isSubmitted() && $form->isValid()) {
            $log = $form->getData();

            $user = $this->getUser();
            $log->setLoggedBy($user);
            $log->setIsManual(true);
            $log->setOrder($this->warehouseOrder);
            $this->entityManager->persist($log);
            $this->entityManager->flush();

            $this->resetForm();
        }

        return null;
    }

    #[LiveAction]
    public function deleteLog(#[LiveArg] int $id): Response|null
    {
        $log = $this->entityManager->getRepository(WarehouseOrderLog::class)->find($id);
        if ($log) {
            $this->entityManager->remove($log);
            $this->entityManager->flush();
        }

        return null;
    }
}
