<?php

namespace App\Controller\Admin\Warehouse;

use App\Entity\Admin\WarehouseLabel;
use App\Entity\StoreSettings;
use App\Entity\Store;
use App\Form\Admin\ChangePasswordType;
use App\Form\Admin\Configuration\StoreSettingsType;
use App\Form\Admin\Configuration\StoreType;
use App\Form\Admin\Warehouse\LabelType;
use App\Repository\Admin\WarehouseLabelRepository;
use App\Repository\StoreSettingsRepository;
use App\Repository\StoreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/warehouse/label', name: 'warehouse_label_')]
class LabelController extends AbstractController
{
    #[Route('s/', name: 'list')]
    public function index(Request $request, WarehouseLabelRepository $labelRepository, PaginatorInterface $paginator): Response
    {
        $page = $request->query->getInt('page', 1);
        $labels = $paginator->paginate($labelRepository->findAllActive(), $page, 20);

        $form = $this->createForm(LabelType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $label = $form->getData();
            $labelRepository->save($label, true);
            $this->addFlash('success', 'Label has been created successfully');
            return $this->redirectToRoute('admin_warehouse_label_list');
        }
        return $this->render('admin/warehouse/label/index.html.twig', [
            'labels' => $labels,
            'form' => $form->createView(),
        ]);
    }

}
