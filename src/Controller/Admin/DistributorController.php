<?php

namespace App\Controller\Admin;

use App\Entity\Distributor;
use App\Repository\DistributorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\Admin\Distributor\FilterDistributorType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class DistributorController extends AbstractController
{
    #[Route('/distributor', name: 'distributor')]
    public function index(Request $request, DistributorRepository $distributorRepository, PaginatorInterface $paginator, ParameterBagInterface $params): Response
    {

        $this->denyAccessUnlessGranted($request->get('_route'));
        $filterForm = $this->createForm(FilterDistributorType::class, null, ['method' => 'GET']);
        $filterForm->handleRequest($request);

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            
            $query = $distributorRepository->filterDistributorsQuery(
                email: $filterForm->get('email')->getData(),
                phoneNumber: $filterForm->get('phoneNumber')->getData(),
            );
        } else {
            $query = $distributorRepository->findAllActiveQuery();
        }

        $page = $request->get('page', 1);
        $distributors = $paginator->paginate($query, $page, 20);

        return $this->render('admin/distributor/index.html.twig', [
            'distributors' => $distributors,
            'filterForm' => $filterForm,
            'isFilterFormSubmitted' => $filterForm->isSubmitted() && $filterForm->isValid(),
        ]);
    }

    #[Route('/update-distributor-enquiry-status/{id}/{status}', name: 'update_distributor_enquiry_status')]
    public function updateDistributorStatus(int $id, string $status, DistributorRepository $repository, EntityManagerInterface $em): Response
    {
        $data = $repository->find($id);
        if (!$data) {
            throw $this->createNotFoundException('Distributor not found.');
        }
        $now = new \DateTimeImmutable();
        $data->setStatus($status ?? Distributor::STATUS_CLOSED);
        $data->setUpdatedAt($updatedAt ?? $now);
        $em->flush();

        return $this->redirectToRoute('admin_distributor');
    }

    #[Route('/distributor/{id}', name: 'distributor_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        int $id,
        DistributorRepository $repository
    ): Response {
        $distributor = $repository->findActiveById($id);

        if (!$distributor) {
            throw $this->createNotFoundException('Distributor not found.');
        }

        return $this->render('admin/distributor/show.html.twig', [
            'distributor' => $distributor,
        ]);
    }


    #[Route('/distributor/delete/{id}', name: 'distributor_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        int $id,
        DistributorRepository $distributorRepository,
        EntityManagerInterface $em
    ): RedirectResponse {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $distributor = $distributorRepository->find($id);

        if (!$distributor) {
            throw $this->createNotFoundException('Distributor not found.');
        }

        if (!$this->isCsrfTokenValid(
            'delete_distributor_' . $id,
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $distributor->setDeletedAt(new \DateTimeImmutable());

        $em->flush();

        $this->addFlash('success', 'Distributor deleted successfully.');

        return $this->redirectToRoute('admin_distributor');
    }



    #[Route('/distributor/deleted', name: 'distributor_deleted')]
    public function deleted(DistributorRepository $distributorRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $query = $distributorRepository->findDeletedQuery();

        $page = $request->get('page', 1);
        $distributors = $paginator->paginate($query, $page, 20);

        return $this->render('admin/distributor/deleted.html.twig', [
            'distributors' => $distributors
        ]);
    }

    #[Route('/distributor/restore/{id}', name: 'distributor_restore')]
    public function restore(
        int $id,
        Request $request,
        DistributorRepository $distributorRepository,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted($request->get('_route'));

        $distributor = $distributorRepository->findByIdIncludingDeleted($id);

        if (!$distributor) {
            throw $this->createNotFoundException('Distributor not found.');
        }

        $distributor->setDeletedAt(null);

        $em->flush();

        $this->addFlash('success', 'Distributor restored successfully.');

        return $this->redirectToRoute('admin_distributor_deleted');
    }

}
