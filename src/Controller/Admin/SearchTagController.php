<?php

namespace App\Controller\Admin;

use App\Entity\SearchTag;
use App\Form\Admin\SearchTag\SearchTagType;
use App\Repository\SearchTagRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/search-tag')]
class SearchTagController extends AbstractController
{
    #[Route('/', name: 'search_tag_list')]
    public function index(Request $request, SearchTagRepository $SearchTagRepository, PaginatorInterface $paginator): Response
    {
        $SearchTag = $SearchTagRepository->findBy([], ['id' => 'DESC']);
        $page = $request->get('page', 1);
        $SearchTag = $paginator->paginate($SearchTag, $page, 32);

        return $this->render('admin/tags/index.html.twig',[
            'SearchTag' => $SearchTag
        ]);
    }

    #[Route('/add', name: 'search_tag_add')]
    public function add(Request $request, SearchTagRepository $SearchTagRepository): Response
    {
        $searchTag = new SearchTag();
        $form = $this->createForm(SearchTagType::class, $searchTag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $SearchTagRepository->save($searchTag, true);
            $this->addFlash('success', 'SearchTag has been created successfully.');
            return $this->redirectToRoute('admin_search_tag_list');
        }

        return $this->render('admin/tags/add.html.twig',[
            'form' => $form,
            'searchTag' => $searchTag
        ]);
    }

    #[Route('/edit/{id}', name: 'search_tag_edit')]
    public function edit(SearchTag $searchTag, SearchTagRepository $SearchTagRepository, Request $request): Response
    {
        $form = $this->createForm(SearchTagType::class, $searchTag);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $SearchTagRepository->save($searchTag, true);
            $this->addFlash('success', 'SearchTag "'.$searchTag->getUrlName().'" has been updated successfully.');
            return $this->redirectToRoute('admin_search_tag_list');
        }

        return $this->render('admin/tags/edit.html.twig', [
            'form' => $form,
            'searchTag' => $searchTag
        ]);
    }

    #[Route('/remove/{id}', name: 'search_tag_remove')]
    public function remove(SearchTag $searchTag, SearchTagRepository $searchTagRepository): Response
    {
        $searchTagRepository->remove($searchTag, true);
        $this->addFlash('success', "Tag deleted successfully");
        return $this->redirectToRoute('admin_search_tag_list');
    }
}
