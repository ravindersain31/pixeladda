<?php

namespace App\Controller\Admin\Customer;

use App\Entity\Subscriber;
use App\Form\Admin\Customer\FilterSubscriberType;
use App\Form\Admin\Customer\SubscriberType;
use App\Repository\SubscriberRepository;
use App\Service\ExportService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('subscribers')]
class SubscriberController extends AbstractController
{

    #[Route('/', name: 'subscribers')]
    public function subscribers(Request $request, SubscriberRepository $SubscriberRepository, PaginatorInterface $paginator): Response
    {
        $filterForm = $this->createForm(FilterSubscriberType::class, null, ['method' => 'GET']);
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $query = $SubscriberRepository->filterSubscribers(
                email:  $filterForm->get('email')->getData(),
                name:  $filterForm->get('name')->getData(),
                phone:  $filterForm->get('phone')->getData(),
            );
        } else {
            $query = $SubscriberRepository->findBy([],['id' => 'DESC']);;
        }

        $page = $request->get('page', 1);
        $Subscribers = $paginator->paginate($query, $page, 32);

        return $this->render('admin/customer/subscriber/index.html.twig', [
            'subscribers' => $Subscribers,
            'filterForm' => $filterForm,
            'isFilterFormSubmitted' => $filterForm->isSubmitted() && $filterForm->isValid(),
        ]);
    }

    #[Route('/edit/{id}', name: 'subscribers_edit')]
    public function edit(Subscriber $subscriber, Request $request, SubscriberRepository $subscriberRepository): Response
    {
        $form = $this->createForm(SubscriberType::class, $subscriber);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $subscriberRepository->save($subscriber, true);
            $this->addFlash('success', 'Subscriber has been updated successfully.');
            return $this->redirectToRoute('admin_subscribers');
        }

        return $this->render('admin/customer/subscriber/edit.html.twig', [
            'form' => $form,
            'subscriber' => $subscriber,
        ]);
    }

    #[Route('/export', name: 'subscribers_export')]
    public function exportCsv(Request $request, EntityManagerInterface $entityManager, ExportService $exportService): Response
    {
        try {
            $fromDateStr = $request->query->get('from_date');
            $toDateStr = $request->query->get('to_date');

            if (!$fromDateStr || !$toDateStr) {
                $this->addFlash('danger', 'Both From and To dates are required.');
                return $this->redirectToRoute('admin_subscribers');
            }

            $fromDate = new \DateTime($fromDateStr . ' 00:00:00');
            $toDate = new \DateTime($toDateStr . ' 23:59:59');

            if ($fromDate > $toDate) {
                $this->addFlash('danger', 'From date cannot be after To date.');
                return $this->redirectToRoute('admin_subscribers');
            }

            $repo = $entityManager->getRepository(Subscriber::class);
            $subscribers = $repo->streamByCreatedDateRange($fromDate, $toDate, 100);

            $filename = 'Subscribers_' . date('YmdHis') . '.csv';
            $this->addFlash('success', 'Subscribers exported successfully.');
            $exportService->exportSubscribersStreamed($subscribers, $filename);
            return $this->redirectToRoute('admin_subscribers');
            exit();
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Record Not Found.');
            return $this->redirectToRoute('admin_subscribers');
        }
    }
}
