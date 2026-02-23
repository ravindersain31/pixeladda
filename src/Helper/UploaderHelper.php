<?php

namespace App\Helper;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\SmartUniqueNamer;

class UploaderHelper
{
    private string $uploadPath = '';

    private string $namePrefix = '';

    public array $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'ai', 'eps', 'gif', 'ppt', 'pptx', 'psd', "tiff", "tif", "heic", 'svg', 'csv', 'xlsx', 'xls', 'webp', 'zip'];

    public const ALLOWED_STORAGES = [
        'defaultStorage',
        'artworkStorage',
        'clipartStorage',
        'productTemplateStorage',
        'customDesignStorage',
        'blogFilesStorage',
        'editorStorage',
        'bannerStorage',
        'imageStorage',
    ];

    public const STORAGE_CONFIG = [
        'defaultStorage' => [
            'key' => 'defaultStorage',
            'label' => 'Default Storage',
            'path' => 'assets',
            'storage' => 'default.storage'
        ],
        'artworkStorage' => [
            'key' => 'artworkStorage',
            'label' => 'Artwork Storage',
            'path' => 'storage/artwork',
            'storage' => 'artwork.storage'
        ],
        'clipartStorage' => [
            'key' => 'clipartStorage',
            'label' => 'Clipart Storage',
            'path' => 'clipart',
            'storage' => 'clipart.storage'
        ],
        'productTemplateStorage' => [
            'key' => 'productTemplateStorage',
            'label' => 'Product Template Storage',
            'path' => 'product/template',
            'storage' => 'product.template.storage'
        ],
        'customDesignStorage' => [
            'key' => 'customDesignStorage',
            'label' => 'Custom Design Storage',
            'path' => 'storage/custom-design',
            'storage' => 'custom.design.storage'
        ],
        'blogFilesStorage' => [
            'key' => 'blogFilesStorage',
            'label' => 'Blog Files Storage',
            'path' => 'blog',
            'storage' => 'blog.files.storage'
        ],
        'editorStorage' => [
            'key' => 'editorStorage',
            'label' => 'Editor Storage',
            'path' => 'storage/editor',
            'storage' => 'editor.storage'
        ],
        'bannerStorage' => [
            'key' => 'bannerStorage',
            'label' => 'Banner Storage',
            'path' => 'storage/banners',
            'storage' => 'banner.storage'
        ],
        'imageStorage' => [
            'key' => 'imageStorage',
            'label' => 'Image Storage',
            'path' => 'storage/images',
            'storage' => 'image.storage'
        ]
    ];

    public function __construct(
        private readonly SmartUniqueNamer      $namer,
        private readonly ParameterBagInterface $parameterBag,
        private readonly FilesystemOperator    $defaultStorage,
        private readonly FilesystemOperator    $artworkStorage,
        private readonly FilesystemOperator    $clipartStorage,
        private readonly FilesystemOperator    $productTemplateStorage,
        private readonly FilesystemOperator    $customDesignStorage,
        private readonly FilesystemOperator    $blogFilesStorage,
        private readonly FilesystemOperator    $editorStorage,
        private readonly FilesystemOperator    $bannerStorage,
        private readonly FilesystemOperator    $imageStorage,
    )
    {
    }

    public function upload(UploadedFile $file, string $storageName = 'defaultStorage', bool $publicUrl = true): string|null
    {
        $storage = $this->$storageName;

        $path = $file->getRealPath();
        if (!$path || !file_exists($path)) {
            throw new \RuntimeException('Uploaded file path is empty or invalid');
        }

        $stream = fopen($path, 'r');
        try {
            $mapping = new PropertyMapping('file', 'originalName');
            $fileObject = (object)['file' => $file];
            $fileName = $this->namer->name($fileObject, $mapping);
            if (strlen($this->namePrefix) > 1) {
                $fileName = $this->namePrefix . '_' . $fileName;
            }
            if (strlen($this->uploadPath) > 1) {
                $fileName = $this->uploadPath . '/' . $fileName;
            }

            $storage->writeStream($fileName, $stream);
            if ($publicUrl) {
                return $this->publicUrl($storageName, $fileName);
            }
            return $fileName;

        } catch (FilesystemException $e) {
        }
        if (is_resource($stream)) {
            fclose($stream);
        }
        return null;
    }

    public function setUploadPath(string $path): void
    {
        $this->uploadPath = $path;
    }

    public function setUploadNamePrefix(string $prefix): void
    {
        $this->namePrefix = $prefix;
    }

    public function getUploadedFileFromUrl(string $url): UploadedFile
    {
        $contents = file_get_contents($url);
        return $this->createFileFromContents($contents, basename($url));
    }

    public function createFileFromContents(mixed $contents, string $fileName, ?string $mimeType = null): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'uploader_helper_');
        file_put_contents($path, $contents);
        if ($mimeType) {
            return new UploadedFile($path, $fileName, $mimeType);
        }
        return new UploadedFile($path, $fileName);
    }

    private function publicUrl(string $storageName, string $fileName): string
    {
        $baseUrl = $this->parameterBag->get('AWS_S3_BASE_URL');
        $path = match ($storageName) {
            'artworkStorage' => 'storage/artwork',
            'blogFilesStorage' => 'blog',
            'defaultStorage' => 'assets',
            'productTemplateStorage' => 'product/template',
            'customDesignStorage' => 'storage/custom-design',
            'clipartStorage' => 'fit-in/500x500/clipart',
            'editorStorage' => 'storage/editor',
            'bannerStorage' => 'storage/banners',
            'imageStorage' => 'storage/images'
        };
        return sprintf('%s/%s/%s', $baseUrl, $path, $fileName);
    }

    public function getAllowedTypes(): array
    {
        return $this->allowed;
    }
}