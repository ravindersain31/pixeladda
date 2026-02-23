<?php

namespace App\Controller\Admin\Faq;

use App\Entity\Admin\Faq\FaqType;
use App\Form\Admin\Faq\FaqTypeForm;
use App\Repository\FaqTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/faq-types')]
class FaqTypeController extends AbstractController
{
    #[Route('/', name: 'faq_type_index')]
    public function index(FaqTypeRepository $repo): Response
    {
        return $this->render('admin/faq/faq-type/index.html.twig', [
            'types' => $repo->findAll()
        ]);
    }

    #[Route('/new', name: 'faq_type_new')]
    public function new(Request $request, EntityManagerInterface $em, FaqTypeRepository $faqTypeRepository): Response
    {
        $type = new FaqType();
        $form = $this->createForm(FaqTypeForm::class, $type, [
            'is_edit' => false
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $maxSort = $faqTypeRepository->getMaxSortOrder();
            $type->setSortOrder($maxSort + 1);

            $em->persist($type);
            $em->flush();

            $this->addFlash('success', 'FAQ Type created successfully.');
            return $this->redirectToRoute('admin_faq_type_index');
        }

        return $this->render('admin/faq/faq-type/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'faq_type_edit')]
    public function edit(FaqType $type, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(FaqTypeForm::class, $type, [
            'is_edit' => true
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('admin_faq_type_index');
        }

        return $this->render('admin/faq/faq-type/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
