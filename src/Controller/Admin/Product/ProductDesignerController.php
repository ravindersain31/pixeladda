<?php

namespace App\Controller\Admin\Product;

use App\Entity\Product;
use App\Helper\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/product')]
class ProductDesignerController extends AbstractController
{

    #[Route('/{productId}/variant/{variantId}/design', name: 'product_variant_design')]
    public function variant(Request $request, EntityManagerInterface $entityManager): Response
    {
        $productId = $request->get('productId');
        $variantId = $request->get('variantId');

        $template = $entityManager->getRepository(Product::class)->findOneBy(['parent' => $productId, 'id' => $variantId]);

        $templateJsonUrl = 'https://yardsignplus-static.s3.amazonaws.com/product/template/' . $template->getMetaDataKey('templateJson');

        $variantNameArr = explode('x', $template->getName());

        $product = $template->getParent();

        $category = $product->getPrimaryCategory();
        $productType = $product->getProductType();

        $variantData = [
            'name' => $template->getName(),
            'sku' => $product->getSku(),
            'editorUrl' => $this->generateUrl('editor', ['variant' => $template->getName(), 'category' => $category->getSlug(), 'productType' => $productType->getSlug(), 'sku' => $product->getSku()]),
            'templateSize' => [
                'width' => $variantNameArr[0] ?? 1,
                'height' => $variantNameArr[1] ?? 1,
            ],
        ];

        return $this->render('admin/product/edit/design.html.twig', [
            'template' => $template,
            'variant' => $variantData,
            'saveUrl' => $this->generateUrl('admin_product_variant_design_save', ['productId' => $productId, 'variantId' => $variantId]),
            'uploadDataImage' => $this->generateUrl('admin_product_variant_design_save_data_image_upload', ['productId' => $productId, 'variantId' => $variantId]),
            'templateJsonUrl' => $templateJsonUrl,
        ]);
    }

    #[Route('/{productId}/variant/{variantId}/design/save', name: 'product_variant_design_save')]
    public function variantSave(Request $request, UploaderHelper $uploaderHelper, EntityManagerInterface $entityManager): Response
    {
        $canvasData = $request->get('canvasData');
        $productId = $request->get('productId');
        $variantId = $request->get('variantId');

        $template = $entityManager->getRepository(Product::class)->findOneBy(['parent' => $productId, 'id' => $variantId]);
        if (!$template instanceof Product) {
            return $this->json([
                'success' => false,
                'message' => 'You are trying to save an invalid variant. Please reload the page for open the designer again from admin'
            ]);
        }

        $file = $request->get('imageDataURL');
        $imageData = explode(',', $file);
        if(isset($imageData[1])) {
            $image = new \Imagick();
            $image->readImageBlob(base64_decode($imageData[1]));
            $fileName = uniqid() . '.png';
            $uploadedFile = $uploaderHelper->createFileFromContents($image->getImageBlob(), $fileName, 'image/png');
            $template->setImageFile($uploadedFile);
            $entityManager->persist($template);
            $entityManager->flush();
        }

        $product = $template->getParent();

        $fileName = uniqid() . '.json';

        $file = $uploaderHelper->createFileFromContents(json_encode($canvasData), $fileName);
        $uploaderHelper->setUploadNamePrefix($template->getSlug());
        $uploaderHelper->setUploadPath($product->getSku());
        $filePath = $uploaderHelper->upload($file, 'productTemplateStorage', false);
        if (!$filePath) {
            return $this->json([
                'success' => false,
                'message' => 'Design is not updated. Please contact Devs and share the screenshot of this page. [AWS_S3_CONNECTION_ISSUE]'
            ]);
        }
        $template->setMetaDataKey('templateJson', $filePath);

        $entityManager->persist($template);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Updated design has been saved successfully.'
        ]);
    }

    #[Route('/{productId}/variant/{variantId}/data-image/upload', name: 'product_variant_design_save_data_image_upload')]
    public function variantDesignDataImageUpload(Request $request, UploaderHelper $uploaderHelper, EntityManagerInterface $entityManager): Response
    {
        $productId = $request->get('productId');
        $variantId = $request->get('variantId');

        $template = $entityManager->getRepository(Product::class)->findOneBy(['parent' => $productId, 'id' => $variantId]);
        if (!$template instanceof Product) {
            return $this->json([
                'success' => false,
                'message' => 'You are trying to save an invalid variant. Please reload the page for open the designer again from admin'
            ]);
        }

        $product = $template->getParent();

        try {
            $file = $request->get('file');
            $imageData = explode(',', $file);
            $image = new \Imagick();
            $image->readImageBlob(base64_decode($imageData[1]));
            $fileName = uniqid() . '.png';
            $uploadedFile = $uploaderHelper->createFileFromContents($image->getImageBlob(), $fileName, 'image/png');
            $uploaderHelper->setUploadNamePrefix($template->getSlug());
            $uploaderHelper->setUploadPath($product->getSku() . '/files');

            $fileUrl = $uploaderHelper->upload($uploadedFile, 'productTemplateStorage');
            if (!$fileUrl) {
                return $this->json([
                    'success' => false,
                    'message' => 'Data image file is not uploaded. Please contact Devs and share the screenshot of this page. [AWS_S3_CONNECTION_ISSUE]'
                ]);
            }


            return $this->json([
                'success' => true,
                'imageUrl' => $fileUrl,
                'message' => 'Data image file has been uploaded successfully.'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Data image file is not uploaded. Please contact Devs and share the screenshot of this page. [AWS_S3_CONNECTION_ISSUE]'
            ]);
        }
    }

}
