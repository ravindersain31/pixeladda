<?php

namespace App\Enum;

enum PaymentStatusEnum
{
    const INITIATED = 'INITIATED';

    const PENDING = 'PENDING';

    const PROCESSING = 'PROCESSING';

    const FAILED = 'FAILED';

    const CANCELLED = 'CANCELLED';

    const PENDING_CAPTURE = 'PENDING_CAPTURE';

    const COMPLETED = 'COMPLETED';

    const VOIDED = 'VOIDED';

    const REDIRECTED_TO_GATEWAY = 'REDIRECTED_TO_GATEWAY';

    const UNKNOWN = 'UNKNOWN';

    const REFUNDED = 'REFUNDED';

    const PARTIALLY_REFUNDED = 'PARTIALLY_REFUNDED';

    const LABELS = [
        self::INITIATED => 'Initiated',
        self::PENDING => 'Pending',
        self::PROCESSING => 'Processing',
        self::FAILED => 'Failed',
        self::CANCELLED => 'Cancelled',
        self::PENDING_CAPTURE => 'Pending Capture',
        self::COMPLETED => 'Completed',
        self::REDIRECTED_TO_GATEWAY => 'Redirected to Gateway',
        self::REFUNDED => 'Refunded',
        self::PARTIALLY_REFUNDED => 'Partially Refunded',
        self::UNKNOWN => 'Unknown',
    ];

    public static function getLabel(string $status): string
    {
        if (isset(self::LABELS[$status])) {
            return self::LABELS[$status];
        }
        return $status;
    }


}