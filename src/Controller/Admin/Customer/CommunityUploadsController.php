<?php

namespace App\Controller\Admin\Customer;

use App\Entity\CommunityUploads;
use App\Repository\CommunityUploadsRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/community-photos')]
class CommunityUploadsController extends AbstractController
{
    #[Route('/', name: 'community_uploads')]
    public function index(Request $request, CommunityUploadsRepository $communityUploadsRepository, PaginatorInterface $paginator): Response
    {
        $communityUploads = $communityUploadsRepository->findAll();
        $page = $request->get('page', 1);
        $communityUploads = $paginator->paginate($communityUploads, $page, 32);
        return $this->render('admin/customer/community/index.html.twig', [
            'communityUploads' => $communityUploads,
        ]);
    }

    #[Route('/remove/{id}', name: 'community_uploads_remove')]
    public function remove(CommunityUploads $communityUploads, CommunityUploadsRepository $communityUploadsRepository): Response
    {
        $communityUploadsRepository->remove($communityUploads, true);
        $this->addFlash('success', 'Photo removed successfully');
        return $this->redirectToRoute('admin_community_uploads');
    }

    #[Route('/change-status/{id}/{status}', name: 'community_uploads_status')]
    public function changeStatus(Request $request, CommunityUploads $communityUploads, CommunityUploadsRepository $communityUploadsRepository): Response
    {
        $status = $request->get('status') === '0' ? true : false;
        $communityUploads->setIsEnabled($status);
        $communityUploadsRepository->save($communityUploads, true);
        $action = $status ? 'Enabled' : 'Disabled';
        $this->addFlash('success', "$action successfully");

        return $this->redirectToRoute('admin_community_uploads');
    }
}
