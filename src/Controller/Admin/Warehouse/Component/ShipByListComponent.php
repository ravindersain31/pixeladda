<?php

namespace App\Controller\Admin\Warehouse\Component;

use App\Entity\Admin\WarehouseShipByList;
use App\Form\Admin\Warehouse\QueueType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "AdminWarehouseShipByList",
    template: "admin/warehouse/components/ship-by-list.html.twig"
)]
class ShipByListComponent extends AbstractController
{

    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use LiveCollectionTrait;

    #[LiveProp]
    public string $printer;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(QueueType::class, null, [
            'printer' => $this->printer
        ]);
    }

    #[LiveAction]
    public function saveList(): Response|null
    {
        $this->submitForm();
        $form = $this->getForm();
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $alreadyExists = 0;
            $created = 0;
            /** @var WarehouseShipByList $list */
            foreach ($data['lists'] as $list) {
                $isExists = $this->entityManager->getRepository(WarehouseShipByList::class)->findOneBy(['shipBy' => $list->getShipBy(), 'printerName' => $this->printer]);
                if (!$isExists) {
                    $list->setPrinterName($this->printer);
                    $this->entityManager->persist($list);
                    $this->entityManager->flush();
                    $created++;
                } else {
                    if ($isExists->getDeletedAt() !== null) {
                        $isExists->setDeletedAt(null);
                        $isExists->setUpdatedAt(new \DateTimeImmutable());
                        $this->entityManager->persist($isExists);
                        $this->entityManager->flush();
                        $created++;
                    } else {
                        $alreadyExists++;
                    }
                }
            }
            $message = 'We have created ' . $created . ' new ship by list(s) for printer ' . $this->printer;
            if ($alreadyExists > 0) {
                $message .= ' and ' . $alreadyExists . ' ship by list(s) already exists';
            }
            $this->addFlash('success', $message);
            return $this->redirectToRoute('admin_warehouse_queue_by_printer', ['printer' => $this->printer]);
        }
        return null;
    }

}
