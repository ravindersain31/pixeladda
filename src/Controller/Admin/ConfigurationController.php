<?php

namespace App\Controller\Admin;

use App\Entity\StoreSettings;
use App\Entity\Store;
use App\Enum\Admin\CacheEnum;
use App\Form\Admin\Configuration\StoreSettingsType;
use App\Form\Admin\Configuration\StoreType;
use App\Repository\StoreSettingsRepository;
use App\Repository\StoreRepository;
use App\Service\CacheService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/config')]
class ConfigurationController extends AbstractController
{
    #[Route('/stores', name: 'config_stores')]
    public function index(Request $request, StoreRepository $repository, PaginatorInterface $paginator): Response
    {
        $page = $request->query->getInt('page', 1);
        $query = $repository->listStore();
        $stores = $paginator->paginate($query, $page, 10);

        return $this->render('admin/configuration/store/index.html.twig', [
            'stores' => $stores,
        ]);
    }

    #[Route('/store/add', name: 'config_store_add')]
    public function addStore(Request $request, StoreRepository $repository): Response
    {
        $store = new Store();
        $form = $this->createForm(StoreType::class, $store);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $repository->save($store, true);
            $this->addFlash('success', 'Store added successfully');
            return $this->redirectToRoute('admin_config_stores');
        }
        return $this->render('admin/configuration/store/add.html.twig', [
            'form' => $form,
            'store' => $store,
        ]);
    }

    #[Route('/store/edit/{id}', name: 'config_store_edit')]
    public function editStore($id, Request $request, StoreRepository $repository, CacheService $cacheService): Response
    {
        $store = $repository->find($id);
        $form = $this->createForm(StoreType::class, $store);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $repository->save($store, true);
            $cacheService->clearPool(CacheEnum::STORE->value);
            $this->addFlash('success', 'Store updated successfully');
            return $this->redirectToRoute('admin_config_stores');
        }
        return $this->render('admin/configuration/store/edit.html.twig', [
            'form' => $form,
            'store' => $store,
        ]);
    }

    #[Route('/store/settings/{id}', name: 'config_store_settings')]
    public function storeSettings($id, Request $request, StoreSettingsRepository $repository, PaginatorInterface $paginator, StoreRepository $storeRepository): Response
    {
        $store = $storeRepository->find($id);
        $page = $request->query->getInt('page', 1);
        $query = $repository->listSettings($store);
        $settings = $paginator->paginate($query, $page, 10);

        return $this->render('admin/configuration/store/settings/settings-index.html.twig', [
            'settings' => $settings,
            'store' =>$store
        ]);
    }

    #[Route('/store/settings/edit/{id}', name: 'config_store_edit_settings')]
    public function storeEditSettings(StoreSettings $settings, Request $request, StoreSettingsRepository $settingsRepository, CacheService $cacheService): Response
    {
        $form = $this->createForm(StoreSettingsType::class, $settings);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $settingsRepository->save($settings, true);
            $cacheService->clearPool(CacheEnum::STORE->value);
            $this->addFlash('success', 'Settings updated successfully');
            return $this->redirectToRoute('admin_config_store_settings' ,['id' => $settings->getStore()->getId()]);
        }
        return $this->render('admin/configuration/store/settings/settings.html.twig', [
            'form' => $form,
            'settings' => $settings,
        ]);
    }

    #[Route('/store/settings/{id}/add', name: 'config_store_add_settings')]
    public function storeAddSettings($id,Request $request, StoreSettingsRepository $settingsRepository, StoreRepository $storeRepository): Response
    {
        $settings = new StoreSettings();
        $form = $this->createForm(StoreSettingsType::class, $settings);
        $store = $storeRepository->find($id);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $settings->setStore($store);
            $settingsRepository->save($settings, true);
            $this->addFlash('success', 'Setting added successfully');
            return $this->redirectToRoute('admin_config_store_settings', ['id' => $settings->getStore()->getId()]);
        }
        return $this->render('admin/configuration/store/settings/settings.html.twig', [
            'form' => $form,
            'settings' => $settings,
        ]);
    }
}
