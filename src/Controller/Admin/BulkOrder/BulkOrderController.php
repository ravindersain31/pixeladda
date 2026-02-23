<?php

namespace App\Controller\Admin\BulkOrder;

use App\Entity\BulkOrder;
use App\Form\Admin\BulkOrder\FilterBulkOrderType;
use App\Form\BulkOrderType;
use App\Repository\BulkOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;



#[Route('/bulk_order', name: 'bulk_order_')]

final class BulkOrderController extends AbstractController
{
    #[Route('/index', name: 'index')]

    public function index(Request $request, BulkOrderRepository $bulkOrderRepository, PaginatorInterface $paginator, ParameterBagInterface $params): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));
        $filterForm = $this->createForm(FilterBulkOrderType::class, null, ['method' => 'GET']);
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $query = $bulkOrderRepository->filterOrderDetails(
                email: $filterForm->get('email')->getData(),
                phoneNumber: $filterForm->get('phoneNumber')->getData(),
            );
        } else {
            $query = $bulkOrderRepository->findBy([], ['id' => 'DESC']);;
        }

        $page = $request->get('page', 1);
        $bulkOrders = $paginator->paginate($query, $page, 32);

        return $this->render('admin/bulk_order/index.html.twig', [
            'bulkOrders' => $bulkOrders,
            'filterForm' => $filterForm,
            'isFilterFormSubmitted' => $filterForm->isSubmitted() && $filterForm->isValid(),
        ]);
    }

    #[Route('/update-bulk-enquiry-status/{id}/{status}', name: 'update_bulk_enquiry_status')]
    public function updateBulkEnquiryStatus(int $id, string $status, BulkOrderRepository $repository, EntityManagerInterface $em): Response
    {
        $bulkOrder = $repository->find($id);
        if (!$bulkOrder) {
            throw $this->createNotFoundException('Bulk Order not found.');
        }
        $bulkOrder->setStatus($status ?? BulkOrder::STATUS_CLOSED);
        $em->flush();

        return $this->redirectToRoute('admin_bulk_order_index');
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(BulkOrder $bulkOrder, ParameterBagInterface $params): Response
    {

        return $this->render('admin/bulk_order/show.html.twig', [
            'bulk_order' => $bulkOrder,
        ]);
    }
}
