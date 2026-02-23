<?php

namespace App\Component\Customer;

use App\Constant\HomePageBlocks;
use App\Entity\Product;
use App\Form\Admin\Customer\SearchType;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(
    name: "SearchMobileForm",
    template: "components/search.html.twig"
)]
class SearchMobileForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp]
    public ?string $flashMessage = null;
    public ?string $flashError = 'success';
    public ?array $result = [];
    public ?bool $isActive = false;

    #[LiveProp(writable: true, url: true)]
    public ?string $query = '';

    #[LiveProp]
    public bool $notFound = false;

    private array $searchCache = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly RequestStack $requestStack,
        private readonly HomePageBlocks $homePageBlocks
    ) {
        $request = $requestStack->getCurrentRequest();
        if ($request) {
            $data = $request->get('search');
            if (isset($data) && !is_array($data)) {
                $this->query = $data;
                $this->isActive = true;
            }
        }
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(SearchType::class);
    }

    public function getProducts(): array
    {
        if ($this->isActive) {
            return [];
        }

        if (array_key_exists($this->query, $this->searchCache)) {
            return $this->searchCache[$this->query];
        }

        $wordsArray = explode(" ", strtolower($this->query));
        $filteredArray = array_filter(array_values($wordsArray));

        if (empty($filteredArray)) {
            return [];
        }

        $products = $this->entityManager->getRepository(Product::class)->productBySearch($filteredArray);

        if ($this->query != '' && empty($products)) {
            $this->notFound = true;
            return [];
        }

        $this->searchCache[$this->query] = $products;

        return ($this->query == '' || empty($this->query) || $this->query == null) ? [] : $products;
    }

    #[LiveAction]
    public function searchAction()
    {
        try {
            return $this->redirectToRoute('category_shop', ['search' => $this->query], 301);
        } catch (Exception $e) {
            $this->flashError = 'danger';
            $this->flashMessage = $e->getMessage();
        }
    }
}
