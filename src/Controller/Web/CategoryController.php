<?php

namespace App\Controller\Web;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductType;
use App\Form\ShopFilterType;
use App\Repository\CategoryBlocksRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\ProductTypeRepository;
use App\Service\VirtualProductService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController extends AbstractController
{
    private array $store = [];

    public function __construct(RequestStack $requestStack)
    {
        $request = $requestStack->getCurrentRequest();
        if ($request) {
            $store = $request->get('store');
            if (is_array($store)) {
                $this->store = $request->get('store');
            }
        }
    }

    #[Route(path: '/shop', name: 'category_shop', priority: -1)]
    public function categoryShop(Request $request, ProductRepository $productRepository,VirtualProductService $virtualProductService, PaginatorInterface $paginator, CategoryRepository $categoryRepository): Response
    {
        $selectedCategory = is_array($request->get('c')) ? reset($request->get('c')) : $request->get('c');
        
        if($selectedCategory === 'pre-packed'){
            $selectedCategory = 'yard-letters';
        }

        $selectedSubCategory = is_string($request->get('sc')) ? explode(',', $request->get('sc')) : ($request->get('sc') ?? []);
        $categoriesHasProducts = $categoryRepository->getCategoryHasProductsSelective( $this->store['id'],displayInMenu: null, showAll: true );

        $virtualProducts = $virtualProductService->makeVirtualProduct();

        foreach ($categoriesHasProducts as &$category) {
            if ($category['slug'] === 'custom-signs') {
                $category['productCount1'] = count($virtualProducts);
                $category['productCount2'] = 0;
            }
        }

        $allSubCategories = array_filter($categoriesHasProducts, fn($cat) => $cat['parentSlug'] !== null);

        $validSubCategories = [];
        if ($selectedCategory) {
            foreach ($allSubCategories as $sub) {
                if ($sub['parentSlug'] === $selectedCategory) {
                    $validSubCategories[] = $sub['slug'];
                }
            }
        }

        $selectedSubCategory = array_values(array_intersect($selectedSubCategory, $validSubCategories));

        $queryParams = [];
        if ($selectedCategory) {
            $queryParams['c'] = $selectedCategory;
        }
        if (!empty($selectedSubCategory)) {
            $queryParams['sc'] = implode(',', $selectedSubCategory);
        }
        $searchQuery = $request->get('search');
        if ($searchQuery) {
            $queryParams['search'] = $searchQuery;
        }

        if ($request->query->all() != $queryParams) {
            return $this->redirectToRoute('category_shop', $queryParams, 301);
        }

        $filterForm = $this->createForm(ShopFilterType::class, $categoriesHasProducts, [
            'method' => 'GET',
        ]);

        $request->query->set('c', $selectedCategory);
        $request->query->set('sc', $selectedSubCategory);
        $filterForm->handleRequest($request);

        $categories = $filterForm->get('c')->getData() ? [$filterForm->get('c')->getData()] : [];
        $subCategories = $filterForm->has('sc') ? $filterForm->get('sc')->getData() : [];
        $subCategories = is_array($subCategories) ? $subCategories : [];

        $choices = $filterForm->get('c')->getConfig()->getOption('choices') ?? [];
        $subChoices = ($filterForm->has('sc') && count($categories) === 1)
            ? $filterForm->get('sc')->getConfig()->getOption('choices') ?? []
            : [];

        if ($request->get('search')) {
            $productQuery = $productRepository->productBySearch(explode(' ', $request->get('search')));
            $virtualProducts = $virtualProductService->filterVirtualProductsByQuery($virtualProducts, $request->get('search', ''));
        } else {
            $productQuery = $productRepository->filterByCategories($categories, $subCategories);
            $virtualProducts = [];
        }

        $page = $request->get('page', 1);
        $products = $paginator->paginate($productQuery, $page, 32);

        return $this->render('shop/index.html.twig', [
            'products' => $products,
            'virtualProducts' => $virtualProducts,
            'filterForm' => $filterForm->createView(),
            'categories' => $categories,
            'subCategories' => $subCategories,
            'choices' => array_values($choices),
            'subChoices' => array_values($subChoices),
            'isAllCategorySelected' => count($choices) === count($categories),
            'isAllSubCategorySelected' => count($subChoices) === count($subCategories),
        ]);
    }

    #[Route(path: '/{slug}/{size}-yard-signs', name: 'category_sizes', priority: 1)]
    public function categorySize(string $slug, string $size, CategoryBlocksRepository $blocksRepository, EntityManagerInterface $entityManager): Response
    {
        $category = $entityManager->getRepository(Category::class)->findOneBy(['slug' => $slug]);

        if (!$category || !$category->isEnabled()) {
            throw $this->createNotFoundException();
        }

        $blocks = $blocksRepository->findBy(['category' => $category], ['position' => 'ASC']);

        return $this->render('category/sizes.html.twig', [
            'category' => $category,
            'blocks' => $blocks,
            'size' => $size,
        ]);
    }

    #[Route(path: '/{slug}/{subSlug}', name: 'category', defaults: ['subSlug' => null], priority: -1)]
    public function category(string $slug, EntityManagerInterface $entityManager, ?string $subSlug = null): Response
    {

        if ($subSlug) {
            $category = $entityManager->getRepository(Category::class)->findSubCategory($subSlug);
        } else {
            $category = $entityManager->getRepository(Category::class)->findCategory($slug);
        }

        $defaultVariants = $entityManager->getRepository(ProductType::class)->getDefaultVariantsBySlug($slug);
        $defaulVariantsSizes = $this->sortVariantsByDefinedOrder($defaultVariants, Product::VARIANTS);

        if (!$category || !$category->isEnabled()) {
            throw $this->createNotFoundException();
        }

        if ($category->getDisplayLayout() === Category::LIST_VIEW) {
            return $this->render('category/products/index.html.twig', [
                'category' => $category,
                'parent' => $category->getParent(),
            ]);
        }

        if ($category->getDisplayLayout() === Category::CATEGORY_VIEW) {
            return $this->render('category/category-view/index.html.twig', [
                'category' => $category,
                'parent' => $category->getParent(),
            ]);
        }

        if ($category->getDisplayLayout() === Category::CATEGORY_SIZE_VIEW) {
            return $this->render('category/category-size-view/index.html.twig', [
                'category' => $category,
                'parent' => $category->getParent(),
            ]);
        }

        return $this->render('category/index.html.twig', [
            'category' => $category,
            'parent' => $category->getParent(),
            'defaulVariantsSizes' =>  $defaulVariantsSizes
        ]);
    }

    private function sortVariantsByDefinedOrder(array $variants, array $definedOrder, ?callable $keyExtractor = null): array
    {
        usort($variants, function ($a, $b) use ($definedOrder, $keyExtractor) {
            $aKey = $keyExtractor ? $keyExtractor($a) : $a;
            $bKey = $keyExtractor ? $keyExtractor($b) : $b;

            $aInOrder = array_key_exists($aKey, $definedOrder);
            $bInOrder = array_key_exists($bKey, $definedOrder);

            if ($aInOrder && $bInOrder) {
                return $definedOrder[$aKey] <=> $definedOrder[$bKey];
            } elseif ($aInOrder) {
                return -1;
            } elseif ($bInOrder) {
                return 1;
            } else {
                return 0;
            }
        });

        return $variants;
    }
}
