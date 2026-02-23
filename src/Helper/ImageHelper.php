<?php

namespace App\Helper;

class ImageHelper
{
    private \Imagick $image;

    public function __construct()
    {
        $this->image = new \Imagick();
    }

    public function toPng(string $filePath): array
    {
        try {
            $this->image->readImage($filePath);
            $this->image->setImageFormat('png');
            return [
                'success' => true,
                'blob' => $this->image->getImageBlob(),
                'mimeType' => $this->image->getImageMimeType(),
            ];
        } catch (\ImagickException $e) {
            return [
                'success' => false,
                'message' => 'Failed to convert image',
                'trace' => $e->getMessage(),
            ];
        }
    }

}