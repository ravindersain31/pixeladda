<?php 

namespace App\Enum;

enum PrintFilesStatus: string
{
    case PENDING = 'PENDING';
    case UPLOADED = 'UPLOADED';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::UPLOADED => 'Uploaded',
        };
    }
}