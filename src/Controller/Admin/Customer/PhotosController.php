<?php

namespace App\Controller\Admin\Customer;

use App\Entity\CustomerPhotos;
use App\Entity\StoreDomain;
use App\Repository\CustomerPhotosRepository;
use App\Twig\ConfigProvider;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/photos')]
class PhotosController extends AbstractController
{
    #[Route('/', name: 'customer_photos')]
    public function index(Request $request, CustomerPhotosRepository $customerPhotosRepository, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $customerPhotos = $customerPhotosRepository->findBy([],['id' => 'DESC']);
        $page = $request->get('page', 1);
        $customerPhotos = $paginator->paginate($customerPhotos, $page, 32);
        return $this->render('admin/customer/photos/index.html.twig', [
            'customerPhotos' => $customerPhotos,
        ]);
    }

    #[Route('/remove/{id}', name: 'customer_photos_remove')]
    public function remove(CustomerPhotos $customerPhotos, CustomerPhotosRepository $customerPhotosRepository): Response
    {
        $customerPhotosRepository->remove($customerPhotos, true);
        $this->addFlash('success', 'Photo removed successfully');
        return $this->redirectToRoute('admin_customer_photos');
    }

    #[Route('/change-status/{id}/{status}', name: 'customer_photos_status')]
    public function changeStatus(Request $request, CustomerPhotos $customerPhotos, CustomerPhotosRepository $customerPhotosRepository): Response
    {
        $status = $request->get('status') === '0' ? true : false;
        $customerPhotos->setIsEnabled($status);
        $customerPhotosRepository->save($customerPhotos, true);
        $action = $status ? 'Enabled' : 'Disabled';
        $this->addFlash('success', "$action successfully");

        return $this->redirectToRoute('admin_customer_photos');
    }
}
