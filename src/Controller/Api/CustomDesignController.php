<?php

namespace App\Controller\Api;

use App\Helper\ImageHelper;
use App\Helper\UploaderHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/custom-design')]
class CustomDesignController extends AbstractController
{
    #[Route(path: '/upload', name: 'upload_custom_design', methods: ['POST'])]
    public function uploadArtwork(Request $request, UploaderHelper $uploader, ImageHelper $imageHelper): Response
    {
        $file = $request->get('url')
            ? $uploader->getUploadedFileFromUrl($request->get('url'))
            : $request->files->get('file');

        $ext = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        if (!in_array($ext, $uploader->getAllowedTypes())) {
            return $this->json([
                'success' => false,
                'message' => 'Please upload a valid file type.  Accepted files are PNG, JPEG, JPG, EPS, CSV, EXCEL, Ai & PDF. Files must be less than 50 MB in size.',
            ], 400);
        }

        // 1. ALWAYS upload the original file first (only one upload here)
        $originalFileUrl = $uploader->upload($file, 'customDesignStorage');
        if (!$originalFileUrl) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to upload original file',
            ], 400);
        }

        // 2. If NO conversion needed → return immediately (NO second upload)
        if (!in_array($ext, ['ai', 'eps', 'pdf'])) {
            $finalUrl = $originalFileUrl;

            $ext2 = strtolower(pathinfo($finalUrl, PATHINFO_EXTENSION));
            if (in_array($ext2, ['jpg', 'jpeg', 'png', 'gif'])) {
                $finalUrl = str_replace(
                    'https://static.yardsignplus.com/',
                    'https://static.yardsignplus.com/fit-in/1000x1000/',
                    $finalUrl
                );
            }

            return $this->json([
                'success' => true,
                'url' => $finalUrl,
                'ext' => 'https://static.yardsignplus.com/icon/ext/' . $ext2 . '.png',
                'originalFileUrl' => $originalFileUrl,
            ]);
        }

        // 3. ONLY FOR AI/EPS/PDF → Convert and upload second time
        $converted = $imageHelper->toPng($file->getRealPath());
        if (!$converted['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to upload your artwork [ERR_CONVERT]',
            ], 400);
        }
        $pngFile = $uploader->createFileFromContents($converted['blob'], $fileName . '.png');
        $convertedUrl = $uploader->upload($pngFile, 'customDesignStorage');

        if (!$convertedUrl) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to upload converted PNG',
            ], 400);
        }

        $convertedUrl = str_replace(
            'https://static.yardsignplus.com/',
            'https://static.yardsignplus.com/fit-in/1000x1000/',
            $convertedUrl
        );

        return $this->json([
            'success' => true,
            'url' => $convertedUrl,
            'ext' => 'https://static.yardsignplus.com/icon/ext/png.png',
            'originalFileUrl' => $originalFileUrl,
        ]);
    }
}
