<?php

namespace App\Helper;

use App\Entity\UserFile;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class VichS3Helper
{
    private array $filters = [
        'resize' => '/fit-in/[VALUE]/',
        'quality' => '/filters:quality([VALUE])/',
        'blur' => '/filters:blur([VALUE])/',
        'rotate' => '/filters:rotate([VALUE])/',
        'background_color' => '/filters:background_color([VALUE])/',
    ];

    private string $s3BaseUrl;

    public function __construct(
        private readonly UploaderHelper        $uploaderHelper,
        private readonly ParameterBagInterface $parameterBag
    )
    {
        $this->s3BaseUrl = $this->parameterBag->get('AWS_S3_BASE_URL');
    }

    public function asset($obj, ?string $fieldName = null, ?string $dimension = null): ?string
    {
        if ($obj instanceof UserFile && $obj->getVersion() === 'V1') {
            return $this->s3BaseUrl . '/' . $obj->getFile()->getName();
        }
        $bucketLocation = $this->uploaderHelper->asset($obj, $fieldName);
        if (!$bucketLocation) {
            return null;
        }

        if ($dimension && !strpos($bucketLocation, '.svg')) {
            if (!strpos($dimension, 'x')) {
                $dimension = $dimension . 'x' . $dimension;
            }

            return $this->applyFilter($bucketLocation, [
                'resize' => $dimension,
            ]);
//
//            $finalUrl = $this->s3BaseUrl;
//            $finalUrl .= '/fit-in/' . $dimension;
//            $finalUrl .= $bucketLocation;
//
//            return $finalUrl;
        }

        return $this->s3BaseUrl . $bucketLocation;
    }

    public function filter($obj, ?string $fieldName = null, ?array $filters = []): ?string
    {
        $bucketLocation = $this->uploaderHelper->asset($obj, $fieldName);
        if (!$bucketLocation) {
            return null;
        }
        if (count($filters) > 0) {
            return $this->applyFilter($bucketLocation, $filters);
        }

        return $this->s3BaseUrl . $bucketLocation;
    }

    private function applyFilter($bucketLocation, $filters): string
    {
        $filterUrl = '';
        foreach ($filters as $filter => $value) {
            if (!$value) {
                continue;
            }
            if ($filter === 'resize') {
                if (!strpos($value, 'x')) {
                    $value = $value . 'x' . $value;
                }
            }
            $filter = $this->filters[$filter];
            $filter = str_replace('[VALUE]', $value, $filter);
            $filterUrl .= substr($filter, 0, -1);
        }

        return $this->s3BaseUrl . $filterUrl . $bucketLocation;

    }

    public function asset_video($obj, ?string $fieldName = null): ?string
    {
        $bucketLocation = $this->uploaderHelper->asset($obj, $fieldName);
        if (!$bucketLocation) {
            return null;
        }
        $fileType = $this->getFileType($bucketLocation);
        if ($fileType === 'video') {
            return $this->s3BaseUrl . $bucketLocation;
        } else {
            return null;
        }
    }

    private function getFileType(string $bucketLocation): string
    {
        $extension = pathinfo($bucketLocation, PATHINFO_EXTENSION);
        if (in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'tiff', 'webp', 'svg'])) {
            return 'image';
        } elseif (in_array(strtolower($extension), ['mp4', 'webm', 'mov', 'ogg'])) {
            return 'video';
        }
        return 'unknown';
    }

}