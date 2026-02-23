<?php

namespace App\Service;

use App\SlackSchema\ErrorLogSchema;
use Google\Client;
use Google\Service\Drive;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;

final class GoogleDriveService
{
    private Drive $driveService;
    private string $ordersParentFolderId = '1UA7Zi1SyUeHVeKA0a03N-Hu32EbqDCX9';
    private array $ordersSubfolders = ['Designer Files', 'Print Files'];
    private array $nestedPrintSubfolders = ['L', 'S']; // "P1,P5,P6,P7,P8,P9,P10" folder names with L and "P3,P4" folder names with S.
    private string $serviceAccountPath = 'google-service-account-dev.txt';

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly KernelInterface       $kernel,
        private readonly RequestStack          $requestStack,
        private readonly SlackManager          $slackManager
    ) {
        $googleDriveEnv = $this->parameterBag->get('GOOGLE_DRIVE_ENV');
        $encryptionKey = $this->parameterBag->get('GOOGLE_SERVICE_ACCOUNT_ENCRYPTION_KEY');

        if ($googleDriveEnv === 'prod') {
            $this->ordersParentFolderId = '1WsEzalGk0b_xcwfyraZn_0VVfAXKNbOY';
            $this->serviceAccountPath = 'google-service-account-prod.txt';
        }

        $encryptedConfig = file_get_contents($kernel->getProjectDir() . '/' . $this->serviceAccountPath);
        $encryptionService = new EncryptionService($encryptionKey);
        $decryptedConfig = $encryptionService->decrypt($encryptedConfig);
        $decryptedConfigJson = json_decode($decryptedConfig, true);

        $client = new Client();
        $client->setAuthConfig($decryptedConfigJson);
        $client->addScope(Drive::DRIVE);
        $this->driveService = new Drive($client);
    }

    public function getExistingOrderFolderLink(string $orderNumber): ?string
    {
        try {
            $response = $this->driveService->files->listFiles([
                'q' => sprintf(
                    "name = '%s' and mimeType = 'application/vnd.google-apps.folder' and '%s' in parents and trashed = false",
                    addslashes($orderNumber),
                    $this->ordersParentFolderId
                ),
                'fields' => 'files(id, name, webViewLink)',
                'supportsAllDrives' => true,
                'includeItemsFromAllDrives' => true,
            ]);

            $files = $response->getFiles();
            if (count($files) > 0) {
                $folder = $files[0];
                if (method_exists($folder, 'getWebViewLink') && $folder->getWebViewLink()) {
                    return $folder->getWebViewLink();
                }
                $meta = $this->driveService->files->get($folder->getId(), [
                    'fields' => 'webViewLink',
                    'supportsAllDrives' => true,
                ]);
                return $meta->getWebViewLink();
            }
        } catch (\Exception $e) {
            $this->reportError($e);
            return null;
        }

        return null;
    }

    public function createOrderFolder(string $orderNumber): string
    {
        // 1. Check if folder already exists
        $existingLink = $this->getExistingOrderFolderLink($orderNumber);
        if ($existingLink !== null) {
            return $existingLink;
        }

        // 2. Create order folder
        $orderFolder = new Drive\DriveFile([
            'name' => $orderNumber,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$this->ordersParentFolderId],
            'supportsAllDrives' => true,
        ]);
        $orderFolder = $this->driveService->files->create($orderFolder, ['fields' => 'id,webViewLink']);
        $orderFolderId = $orderFolder->id;

        // 3. Create subfolders and capture Print Files folder ID
        $printFilesFolderId = null;

        foreach ($this->ordersSubfolders as $mainSubName) {
            $mainSub = new Drive\DriveFile([
                'name' => $mainSubName,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$orderFolderId],
            ]);

            $mainSubCreated = $this->driveService->files->create($mainSub, [
                'fields' => 'id, name',
                'supportsAllDrives' => true,
            ]);

            if (strtolower($mainSubCreated->getName()) === strtolower('Print Files')) {
                $printFilesFolderId = $mainSubCreated->getId();
            }
        }

        // 4. Now create nested subfolders inside Print Files
        if ($printFilesFolderId) {
            foreach ($this->nestedPrintSubfolders as $childName) {
                $childFolder = new Drive\DriveFile([
                    'name' => $childName,
                    'mimeType' => 'application/vnd.google-apps.folder',
                    'parents' => [$printFilesFolderId],
                ]);

                $this->driveService->files->create($childFolder, [
                    'supportsAllDrives' => true,
                ]);
            }
        }

        return $orderFolder->webViewLink;
    }

    public function getOrderFolderId(string $orderNumber): ?string
    {
        try {
            $response = $this->driveService->files->listFiles([
                'q' => sprintf(
                    "name = '%s' and mimeType = 'application/vnd.google-apps.folder' and '%s' in parents and trashed = false",
                    addslashes($orderNumber),
                    $this->ordersParentFolderId
                ),
                'fields' => 'files(id)',
                'supportsAllDrives' => true,
                'includeItemsFromAllDrives' => true,
            ]);

            $files = $response->getFiles();
            return count($files) > 0 ? $files[0]->getId() : null;
        } catch (\Exception $e) {
            $this->reportError($e);
            return null;
        }
    }

    public function getLatestProofFiles(string $orderNumber): array
    {
        try {
            $orderFolderId = $this->getOrderFolderId($orderNumber);
            if (!$orderFolderId) {
                return ['image' => null, 'pdf' => null, 'error' => 'Order folder not found'];
            }

            // Step 1: Get only files directly inside the root order folder (not subfolders)
            $filesResponse = $this->driveService->files->listFiles([
                'q' => sprintf(
                    "'%s' in parents and trashed = false and (mimeType contains 'image/' or mimeType = 'application/pdf')",
                    $orderFolderId
                ),
                'orderBy' => 'createdTime desc',
                'fields' => 'files(id, name, mimeType, webViewLink, thumbnailLink, createdTime)',
                'supportsAllDrives' => true,
                'includeItemsFromAllDrives' => true,
            ]);

            $files = $filesResponse->getFiles();
            $latestImage = null;
            $latestPdf = null;

            // Step 2: Pick latest created image and PDF
            foreach ($files as $file) {
                if (!$latestImage && str_starts_with($file->mimeType, 'image/')) {
                    $latestImage = $file;
                }
                if (!$latestPdf && $file->mimeType === 'application/pdf') {
                    $latestPdf = $file;
                }
                if ($latestImage && $latestPdf) {
                    break;
                }
            }

            // Step 3: Generate URLs
            $makeFileData = function ($file) {
                if (!$file) return null;
                $fileId = $file->getId();

                return [
                    'id' => $fileId,
                    'name' => $file->getName(),
                    'url' => "https://drive.google.com/uc?export=view&id={$fileId}",
                    'thumbnail' => "https://drive.google.com/thumbnail?id={$fileId}&sz=w800",
                    'download' => "https://drive.google.com/uc?export=download&id={$fileId}",
                    'preview' => "https://drive.google.com/file/d/{$fileId}/preview",
                    'web' => $file->getWebViewLink(),
                    'thumb' => $file->getThumbnailLink() ?: "https://drive.google.com/thumbnail?id={$fileId}&sz=w800",
                    'createdTime' => $file->getCreatedTime(),
                    'mimeType' => $file->getMimeType(),
                ];
            };

            return [
                'image' => $makeFileData($latestImage),
                'pdf' => $makeFileData($latestPdf),
            ];
        } catch (\Exception $e) {
            $this->reportError($e);
            return [
                'image' => null,
                'pdf' => null,
                'error' => 'Google Drive Error: ' . $e->getMessage()
            ];
        }
    }

    private function reportError(\Exception $exception): void
    {
        $message = sprintf(
            'Google Drive Error: "%s" with code "%s" in file "%s" at line "%s"',
            $exception->getMessage(),
            $exception->getCode(),
            $exception->getFile(),
            $exception->getLine()
        );

        $this->slackManager->send(SlackManager::ERROR_LOG, ErrorLogSchema::get($message));
    }

    /**
     * Download file content from Google Drive
     */
    public function downloadFile(string $fileId): string
    {
        try {
            $response = $this->driveService->files->get($fileId, [
                'alt' => 'media',
                'supportsAllDrives' => true,
            ]);

            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to download file from Google Drive: ' . $e->getMessage(), 0, $e);
        }
    }

    public function fileExists(string $fileId): bool
    {
        try {
            $file = $this->driveService->files->get($fileId, ['fields' => 'id']);
            return !empty($file->id);
        } catch (\Google\Service\Exception $e) {
            // 404 means file not found
            if ($e->getCode() === 404) {
                return false;
            }
            throw $e; // rethrow for other errors
        }
    }
}
