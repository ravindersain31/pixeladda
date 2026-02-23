<?php

namespace App\Controller\Api;

use App\Entity\Artwork;
use App\Entity\ArtworkCategory;
use App\Entity\Category;
use App\Entity\Product;
use App\Helper\ImageHelper;
use App\Helper\UploaderHelper;
use App\Helper\VichS3Helper;
use App\Trait\StoreTrait;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Vich\UploaderBundle\Naming\SmartUniqueNamer;

class CategoryController extends AbstractController
{

    use StoreTrait;

    #[Route(path: '/category/one-product-image-each', name: 'list_category_images', methods: ['GET'])]
    public function oneProductPerCategory(Request $request, EntityManagerInterface $entityManager, VichS3Helper $vichS3Helper, SerializerInterface $serializer): Response
    {
        $categoryIds = $entityManager->getRepository(Category::class)->findByStoreAndSelect($this->store['id'], 'GROUP_CONCAT(C.id) as ids')->getOneOrNullResult();
        $categoryIds = explode(',', $categoryIds['ids']);
        $products = $entityManager->getRepository(Product::class)->findOneProductPerCategory($categoryIds);

        $productsBySizes = [];
        foreach ($products as $product) {
            $variants = $product->getVariants();
            $primaryCategory = $product->getPrimaryCategory();
            foreach ($variants as $variant) {
                $size = $variant->getName();
                if (!isset($productsBySizes[$size])) {
                    $productsBySizes[$size] = [];
                }
                $productsBySizes[$size][] = [
                    'sku' => $product->getSku(),
                    'category' => $primaryCategory->getSlug(),
                    'name' => $product->getName(),
                    'image' => $vichS3Helper->asset($variant, 'imageFile'),
                ];

            }
        }

        return $this->json($productsBySizes);
    }

}