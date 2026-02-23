<?php

namespace App\Controller\Admin\Customer;

use App\Entity\RequestCallBack;
use App\Repository\RequestCallBackRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('call-requests')]
class CallRequestsController extends AbstractController
{
    #[Route('/', name: 'call_requests')]
    public function index(Request $request, RequestCallBackRepository $requestCallBackRepository, PaginatorInterface $paginator): Response
    {
        $enquiries = $requestCallBackRepository->findNewRequests();
        $page = $request->get('page', 1);
        $enquiries = $paginator->paginate($enquiries, $page, 32);
        return $this->render('admin/customer/call_requests/index.html.twig', [
            'enquiries' => $enquiries,
        ]);
    }

    #[Route('/view/{id}', name: 'call_requests_view')]
    public function view(Request $request, RequestCallBack $requestCallBack): Response
    {
        return $this->render('admin/customer/call_requests/view.html.twig',[
            'callRequests' => $requestCallBack
        ]);
    }

    #[Route('/remove/{id}', name: 'call_requests_remove')]
    public function remove(RequestCallBack $requestCallBack, RequestCallBackRepository $requestCallBackRepository): Response
    {
        $requestCallBackRepository->remove($requestCallBack, true);
        $this->addFlash('success', 'Call Request removed successfully');
        return $this->redirectToRoute('admin_call_requests');
    }

    #[Route('/status/{id}/{status}', name: 'call_requests_status')]
    public function changeStatus(Request $request, RequestCallBack $requestCallBack, RequestCallBackRepository $requestCallBackRepository): Response
    {
        $isOpened = $request->get('status') === '1';
        $requestCallBack->setIsOpened($isOpened);
        $requestCallBack->setUpdatedAt($isOpened ? null : new \DateTimeImmutable());
        $requestCallBackRepository->save($requestCallBack, true);
        $message = 'Call Request ' . ($isOpened ? 'Open' : 'Completed') . ' successfully';
        $this->addFlash('success', $message);
        return $this->redirectToRoute('admin_call_requests');
    }
}
