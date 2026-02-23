<?php

namespace App\Controller\Admin\Faq;

use App\Constant\Faqs;
use App\Entity\Admin\Faq\Faq;
use App\Entity\Admin\Faq\FaqType;
use App\Form\Admin\Faq\FaqForm;
use App\Form\Admin\Faq\FilterFaqTypeForm;
use App\Repository\FaqRepository;
use App\Repository\FaqTypeRepository;
use App\Service\ExportService;
use App\Service\ImportService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/faqs')]
class FaqController extends AbstractController
{
    #[Route('/', name: 'faq_index')]
    public function index(FaqRepository $faqRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $filterForm = $this->createForm(FilterFaqTypeForm::class);
        $filterForm->handleRequest($request);

        $question = null;
        $types = []; 

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $data = $filterForm->getData();
            $question = $data['question'] ?? null;
            $types = $filterForm->get('type')->getData() ?: [];
        }

        $faqs = $paginator->paginate(
            $faqRepository->findAllFaqsQuery($question, $types),
            $request->query->getInt('page', 1),
            32 
        );

        return $this->render('admin/faq/index.html.twig', [
            'faqs' => $faqs,
            'filterForm' => $filterForm->createView(),
        ]);
    }

    #[Route('/new', name: 'faq_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $faq = new Faq();
        $form = $this->createForm(FaqForm::class, $faq, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $keywordsString = $form->get('keywords')->getData();

            $keywordsArray = array_filter(
                array_map('trim', explode(',', $keywordsString ?? '')),
                fn($keyword) => $keyword !== ''
            );

            $faq->setKeywords($keywordsArray);
            $em->persist($faq);
            $em->flush();
            return $this->redirectToRoute('admin_faq_index');
        }

        return $this->render('admin/faq/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'faq_edit')]
    public function edit(Faq $faq, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(FaqForm::class, $faq, ['is_edit' => true]);

        $form->get('keywords')->setData(implode(',', $faq->getKeywords()));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $keywordsString = $form->get('keywords')->getData();

            $keywordsArray = array_filter(
                array_map('trim', explode(',', $keywordsString ?? '')),
                fn($keyword) => $keyword !== ''
            );

            $faq->setKeywords($keywordsArray);
            $em->flush();
            return $this->redirectToRoute('admin_faq_index');
        }

        return $this->render('admin/faq/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/toggle', name: 'faq_toggle', methods: ['POST'])]
    public function toggle(Faq $faq, EntityManagerInterface $em): Response
    {
        $faq->setShowOnEditor(!$faq->isShowOnEditor());
        $em->flush();
        return $this->redirectToRoute('admin_faq_index');
    }

    #[Route('/{id}/delete', name: 'faq_delete', methods: ['POST'])]
    public function delete(Faq $faq, Request $request, EntityManagerInterface $em): Response
    {
        $page = $request->request->get('page', 1); 

        if ($this->isCsrfTokenValid('delete'.$faq->getId(), $request->get('_token'))) {
            $em->remove($faq);
            $em->flush();
        }

        return $this->redirectToRoute('admin_faq_index', ['page' => $page]);
    }

    #[Route('/export/{type?}', name: 'faq_export')]
    public function export(?string $type, FaqTypeRepository $faqTypeRepo, Faqs $faqs, ExportService $exportService): Response 
    {
        $staticFaqs = $faqs->getFaqs();
        $dynamicFaqs = [];

        if ($type === 'dynamic') {
            $types = $faqTypeRepo->findAll();

            foreach ($types as $t) {
                $dynamicFaqs[$t->getName()] = $t->getFaqs()->toArray();
            }
        }

        if ($type === 'static') {
            $dynamicFaqs = [];
        }else {
            $staticFaqs = [];
        }

        $fileName = $type === 'dynamic' ? 'dynamic_faqs.xlsx' : 'static_faqs.xlsx';

        $exportService->exportFaqs($staticFaqs, $dynamicFaqs, $fileName);

        return new Response(); 
    }

    #[Route('/import', name: 'faq_import', methods: ['POST'])]
    public function import(Request $request, ImportService $importService): Response
    {
        $uploadedFile = $request->files->get('faq_file');

        if (!$uploadedFile) {
            $this->addFlash('danger', 'Please select a file to import.');
            return $this->redirectToRoute('admin_faq_index');
        }

        try {
            $importedQuestions = $importService->importFaqs($uploadedFile);

            $count = count($importedQuestions);
            $this->addFlash('success', "Successfully imported {$count} FAQs.");
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Error importing file: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_faq_index');
    }
}
