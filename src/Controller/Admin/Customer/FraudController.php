<?php

namespace App\Controller\Admin\Customer;

use App\Entity\Fraud;
use App\Form\Admin\Fraud\FraudType;
use App\Repository\FraudRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('fraud')]
class FraudController extends AbstractController
{
    #[Route('/', name: 'fraud')]
    public function index(Request $request, FraudRepository $fraudRepository, PaginatorInterface $paginator): Response
    {
        $frauds = $fraudRepository->findBy([], ['createdAt' => 'DESC']);
        $page = $request->get('page', 1);
        $frauds = $paginator->paginate($frauds, $page, 32);

        return $this->render('admin/fraud/index.html.twig', [
            'frauds' => $frauds,
        ]);
    }

    #[Route('/add', name: 'fraud_add')]
    public function addFraud(Request $request, FraudRepository $fraudRepository): Response
    {
        $fraud = new Fraud();
        $form = $this->createForm(FraudType::class, $fraud);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fraudRepository->save($fraud, true);
            $this->addFlash('success', 'Fraud has been created successfully.');
            return $this->redirectToRoute('admin_fraud');
        }

        return $this->render('admin/fraud/edit.html.twig', [
            'form' => $form->createView(),
            'fraud' => $fraud
        ]);
    }

    #[Route('/edit/{id}', name: 'fraud_edit')]
    public function editFraud(Request $request, Fraud $fraud, FraudRepository $fraudRepository): Response
    {
        $form = $this->createForm(FraudType::class, $fraud);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fraud->setUpdatedAt(new \DateTimeImmutable());
            $fraudRepository->save($fraud, true);
            $this->addFlash('success', 'Fraud has been updated successfully.');
            return $this->redirectToRoute('admin_fraud');
        }

        return $this->render('admin/fraud/edit.html.twig', [
            'form' => $form->createView(),
            'fraud' => $fraud
        ]);
    }

    #[Route('/remove/{id}', name: 'fraud_remove')]
    public function deleteFraud(Request $request, Fraud $fraud, FraudRepository $fraudRepository): Response
    {
        $fraudRepository->remove($fraud, true);
        $this->addFlash('success', 'Fraud removed successfully');
        return $this->redirectToRoute('admin_fraud');
    }
}
