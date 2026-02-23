<?php

namespace App\Enum;

enum OrderStatusEnum
{
    const CREATED = 'CREATED';

    const RECEIVED = 'RECEIVED';

    const PROCESSING = 'PROCESSING';

    const CHANGES_REQUESTED = 'CHANGES_REQUESTED';

    const PROOF_UPLOADED = 'PROOF_UPLOADED';

    const DESIGNER_ASSIGNED = 'DESIGNER_ASSIGNED';

    const SENT_FOR_PRODUCTION = 'SENT_FOR_PRODUCTION';

    const READY_FOR_SHIPMENT = 'READY_FOR_SHIPMENT';

    const PROOF_APPROVED = 'PROOF_APPROVED';

    const SHIPPED = 'SHIPPED';

    const COMPLETED = 'COMPLETED';

    const CANCELLED = 'CANCELLED';

    const REFUNDED = 'REFUNDED';

    const PARTIALLY_REFUNDED = 'PARTIALLY_REFUNDED';

    const ARCHIVE = 'ARCHIVE';

    const PROOF = 'PROOF';
    public const MAX_APPROVALS_COUNT = 10;
    public const MAX_REQUEST_CHANGES_COUNT_BEFORE_APPROVAL = null;
    public const MAX_REQUEST_CHANGES_COUNT_AFTER_APPROVAL = 3;
    public const CHARGE_FEE = 10;

    const LABELS = [
        self::CREATED => 'Created',
        self::RECEIVED => 'Upload Proof',
        self::CHANGES_REQUESTED => 'Changes Requested',
        self::PROOF_UPLOADED => 'Proof Uploaded',
        self::PROOF_APPROVED => 'Proof Approved',
        self::DESIGNER_ASSIGNED => 'Designer Assigned',
        self::SENT_FOR_PRODUCTION => 'Ready for Production',
        self::READY_FOR_SHIPMENT => 'Ready to Ship',
        self::SHIPPED => 'Shipped',
        self::COMPLETED => 'Completed',
        self::CANCELLED => 'Cancelled',
        self::REFUNDED => 'Refunded',
        self::PARTIALLY_REFUNDED => 'Partially Refunded',
    ];

    const CUSTOMER_LABELS = [
        self::CREATED => 'Created',
        self::RECEIVED => 'Upload Proof',
        self::CHANGES_REQUESTED => 'Changes Requested',
        self::PROOF_UPLOADED => 'Proof Uploaded',
        self::PROOF_APPROVED => 'Proof Approved',
        self::DESIGNER_ASSIGNED => 'Designer Assigned',
        self::SENT_FOR_PRODUCTION => 'Processing',
        self::READY_FOR_SHIPMENT => 'Processing',
        self::SHIPPED => 'Shipped',
        self::COMPLETED => 'Completed',
        self::CANCELLED => 'Cancelled',
        self::REFUNDED => 'Refunded',
        self::PARTIALLY_REFUNDED => 'Partially Refunded',
    ];

    const CHANGE_STATUS_LABELS = [
        self::RECEIVED => self::LABELS[self::RECEIVED],
        self::COMPLETED => self::LABELS[self::COMPLETED],
    ];

}