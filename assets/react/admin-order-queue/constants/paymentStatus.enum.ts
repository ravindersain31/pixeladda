export const INITIATED = 'INITIATED';
export const PENDING = 'PENDING';
export const PROCESSING = 'PROCESSING';
export const FAILED = 'FAILED';
export const CANCELLED = 'CANCELLED';
export const PENDING_CAPTURE = 'PENDING_CAPTURE';
export const COMPLETED = 'COMPLETED';
export const VOIDED = 'VOIDED';
export const REDIRECTED_TO_GATEWAY = 'REDIRECTED_TO_GATEWAY';
export const UNKNOWN = 'UNKNOWN';
export const REFUNDED = 'REFUNDED';
export const PARTIALLY_REFUNDED = 'PARTIALLY_REFUNDED';

export const PAYMENT_STATUS_LABELS: Record<string, string> = {
    [INITIATED]: 'Initiated',
    [PENDING]: 'Pending',
    [PROCESSING]: 'Processing',
    [FAILED]: 'Failed',
    [CANCELLED]: 'Cancelled',
    [PENDING_CAPTURE]: 'Pending Capture',
    [COMPLETED]: 'Completed',
    [REDIRECTED_TO_GATEWAY]: 'Redirected to Gateway',
    [REFUNDED]: 'Refunded',
    [PARTIALLY_REFUNDED]: 'Partially Refunded',
    [UNKNOWN]: 'Unknown',
};

export function getLabel(status: string): string {
    return PAYMENT_STATUS_LABELS[status] || status;
}
