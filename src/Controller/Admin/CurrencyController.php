<?php

namespace App\Controller\Admin;

use App\Entity\Currency;
use App\Form\Admin\Configuration\CurrencyType;
use App\Repository\CurrencyRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/currency')]
class CurrencyController extends AbstractController
{
    #[Route('/', name: 'config_currencies')]
    public function index(Request $request, CurrencyRepository $repository, PaginatorInterface $paginator): Response
    {
        $page = $request->get('page', 1);
        $currencies = $paginator->paginate($repository->list(), $page, 10);
        return $this->render('admin/configuration/currency/index.html.twig', [
            'currencies' => $currencies,
        ]);
    }

    #[Route('/add', name: 'config_currency_add')]
    public function add(Request $request, CurrencyRepository $repository): Response
    {
        $currency = new Currency();
        $form = $this->createForm(CurrencyType::class, $currency);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $repository->save($currency, true);
            $this->addFlash('success', 'Currency has been created successfully.');
            return $this->redirectToRoute('admin_config_currencies');
        }
        return $this->render('admin/configuration/currency/add.html.twig', [
            'form' => $form,
            'currency' => $currency,
        ]);
    }

    #[Route('/edit/{id}', name: 'config_currency_edit')]
    public function edit(Currency $currency, Request $request, CurrencyRepository $repository, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $form = $this->createForm(CurrencyType::class, $currency);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
//            $csrfTokenManager->refreshToken('category_form');
            $repository->save($currency, true);
            $this->addFlash('success', 'Currency has been updated successfully.');
            return $this->redirectToRoute('admin_config_currencies');
        }
        return $this->render('admin/configuration/currency/edit.html.twig', [
            'form' => $form,
            'currency' => $currency,
        ]);
    }
}
