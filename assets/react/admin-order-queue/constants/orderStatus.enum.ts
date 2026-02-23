
export const CREATED = 'CREATED';
export const RECEIVED = 'RECEIVED';
export const PROCESSING = 'PROCESSING';
export const CHANGES_REQUESTED = 'CHANGES_REQUESTED';
export const PROOF_UPLOADED = 'PROOF_UPLOADED';
export const DESIGNER_ASSIGNED = 'DESIGNER_ASSIGNED';
export const SENT_FOR_PRODUCTION = 'SENT_FOR_PRODUCTION';
export const READY_FOR_SHIPMENT = 'READY_FOR_SHIPMENT';
export const PROOF_APPROVED = 'PROOF_APPROVED';
export const SHIPPED = 'SHIPPED';
export const COMPLETED = 'COMPLETED';
export const CANCELLED = 'CANCELLED';
export const REFUNDED = 'REFUNDED';
export const PARTIALLY_REFUNDED = 'PARTIALLY_REFUNDED';
export const ARCHIVE = 'ARCHIVE';

export const ORDER_STATUS_LABELS: Record<string, string> = {
    [CREATED]: 'Created',
    [RECEIVED]: 'Upload Proof',
    [CHANGES_REQUESTED]: 'Changes Requested',
    [PROOF_UPLOADED]: 'Proof Uploaded',
    [PROOF_APPROVED]: 'Proof Approved',
    [DESIGNER_ASSIGNED]: 'Designer Assigned',
    [SENT_FOR_PRODUCTION]: 'Ready for Production',
    [READY_FOR_SHIPMENT]: 'Ready for Shipment',
    [SHIPPED]: 'Shipped',
    [COMPLETED]: 'Completed',
    [CANCELLED]: 'Cancelled',
    [REFUNDED]: 'Refunded',
    [PARTIALLY_REFUNDED]: 'Partially Refunded',
};

export const CUSTOMER_LABELS: Record<string, string> = {
    [CREATED]: 'Created',
    [RECEIVED]: 'Upload Proof',
    [CHANGES_REQUESTED]: 'Changes Requested',
    [PROOF_UPLOADED]: 'Proof Uploaded',
    [PROOF_APPROVED]: 'Proof Approved',
    [DESIGNER_ASSIGNED]: 'Designer Assigned',
    [SENT_FOR_PRODUCTION]: 'Processing',
    [READY_FOR_SHIPMENT]: 'Processing',
    [SHIPPED]: 'Shipped',
    [COMPLETED]: 'Completed',
    [CANCELLED]: 'Cancelled',
    [REFUNDED]: 'Refunded',
    [PARTIALLY_REFUNDED]: 'Partially Refunded',
};
