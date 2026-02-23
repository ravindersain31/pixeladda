<?php

namespace App\Controller\Admin;

use App\Helper\UploaderHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FileUploadController extends AbstractController
{

    public function __construct(
        private readonly UploaderHelper $uploaderHelper,
    ) {}

    #[Route('/file-upload', name: 'file_upload_manager')]
    public function upload()
    {
        return $this->render('admin/file-upload/index.html.twig', [
            'upload_multiple_file' => $this->generateUrl('admin_upload_multiple_files'),
            'storages' => UploaderHelper::STORAGE_CONFIG,
            'allowedFileTypes' => $this->uploaderHelper->getAllowedTypes(),
            'maxFileSize' => '6MB'
        ]);
    }

    #[Route('/api/upload/multiple', name: 'upload_multiple_files', methods: ['POST'])]
    public function uploadMultiple(Request $request): JsonResponse
    {
        try {
            $storageName = $request->request->get('storage', 'defaultStorage');

            if (!in_array($storageName, UploaderHelper::ALLOWED_STORAGES)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Invalid storage name.'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Get uploaded files
            $files = $request->files->get('files', []);

            if (empty($files)) {
                return $this->json([
                    'success' => false,
                    'error' => 'No files provided'
                ], Response::HTTP_BAD_REQUEST);
            }

            $uploadedFiles = [];
            $errors = [];
            $allowedTypes = $this->uploaderHelper->getAllowedTypes();

            foreach ($files as $index => $file) {
                if (!$file) {
                    continue;
                }

                $extension = strtolower($file->guessExtension() ?? $file->getClientOriginalExtension());
                if (!in_array($extension, $allowedTypes)) {
                    $errors[] = [
                        'file' => $file->getClientOriginalName(),
                        'error' => "Invalid file type. Allowed: " . implode(', ', $allowedTypes)
                    ];
                    continue;
                }

                // Validate file size (6MB max, matching React validation)
                $maxSize = 6 * 1024 * 1024; // 6MB
                if ($file->getSize() > $maxSize) {
                    $errors[] = [
                        'file' => $file->getClientOriginalName(),
                        'error' => sprintf(
                            'File size exceeds 6MB limit (current: %.2fMB)',
                            $file->getSize() / 1024 / 1024
                        )
                    ];
                    continue;
                }

                try {
                    $url = $this->uploaderHelper->upload($file, $storageName, true);

                    if ($url) {
                        $uploadedFiles[] = [
                            'originalName' => $file->getClientOriginalName(),
                            'url' => $url,
                            'size' => $file->getSize(),
                            'mimeType' => $file->getMimeType(),
                        ];
                    } else {
                        $errors[] = [
                            'file' => $file->getClientOriginalName(),
                            'error' => 'Upload failed'
                        ];
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'file' => $file->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ];
                }
            }

            $this->uploaderHelper->setUploadPath('');
            $this->uploaderHelper->setUploadNamePrefix('');

            return $this->json([
                'success' => true,
                'uploaded' => $uploadedFiles,
                'errors' => $errors,
                'summary' => [
                    'total' => count($files),
                    'successful' => count($uploadedFiles),
                    'failed' => count($errors),
                    'storage' => $storageName,
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
