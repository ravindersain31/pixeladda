<?php

namespace App\Controller\Api;

use App\Entity\CustomerPhotos;
use App\Entity\Store;
use App\Entity\StoreDomain;
use App\Helper\ProductConfigHelper;
use App\Repository\CustomerPhotosRepository;
use App\Repository\ProductRepository;
use App\Service\StoreInfoService;
use App\Trait\StoreTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class CustomerPhotosController extends AbstractController
{

    use StoreTrait;

    #[Route(path: '/customer-photos/list', name: 'list_customer_photos', methods: ['GET'])]
    public function customerPhotos(Request $request, CustomerPhotosRepository $repository, SerializerInterface $serializer): Response
    {
        $photos = $repository->findBy(['store' => $this->getStore()->id, 'isEnabled' => true], ['createdAt' => 'DESC']);
        return new Response($serializer->serialize($photos, 'json', ['groups' => 'apiData']));
    }

    #[Route(path: '/customer-photos/upload', name: 'upload_customer_photos', methods: ['POST'])]
    public function uploadCustomerPhotos(Request $request, EntityManagerInterface $entityManager, StoreInfoService $storeInfoService): Response
    {
        $file = $request->files->get('file');
        $name = $request->get('name');
        $comments = $request->get('comments');

        if (!$file instanceof UploadedFile) {
            return $this->json([
                'success' => false,
                'message' => 'File is required.'
            ]);
        }

        if (!$name) {
            return $this->json([
                'success' => false,
                'message' => 'Name is required.'
            ]);
        }

        if (!$comments) {
            return $this->json([
                'success' => false,
                'message' => 'Comments is required.'
            ]);
        }

        $mimeType = $file->getMimeType();
        $isVideo = strpos($mimeType, 'video') === 0;
        $host = $storeInfoService->storeInfo()['storeHost'];
        $storeDomain = $entityManager->getRepository(StoreDomain::class)->findOneBy(['domain' => $host]);
        $store = $storeDomain?->getStore() ?? $entityManager->getReference(Store::class, 1);
        $photo = new CustomerPhotos();
        $photo->setStore($store);
        $photo->setStoreDomain($storeDomain);
        $photo->setPhotoFile($file);
        $photo->setName($name);
        $photo->setComment($comments);
        $photo->setIsEnabled(false);

        $entityManager->persist($photo);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => $isVideo ? 'Video uploaded successfully.' : 'Photo uploaded successfully.',
            'file' => [
                'id' => $photo->getId(),
                'name' => $photo->getName(),
                'photoUrl' => $photo->getPhotoUrl(),
            ]
        ]);
    }

}