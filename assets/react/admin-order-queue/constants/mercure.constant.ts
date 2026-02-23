export const MERCURE_HUB_URL = "https://mercure.yardsignplus.com/.well-known/mercure";

export const DOMAIN_PREFIX = window.location.hostname ?? 'local.yardsignplus.com';

export const BASE_MERCURE_TOPICS = {

    // Warehouse Heartbeat Events
    WAREHOUSE_HEARTBEAT: 'heartbeat',

    // Warehouse Printer Events
    WAREHOUSE_PRINTERS_COUNT: 'printers_count',

    // Warehouse Order Events
    WAREHOUSE_ORDER_CREATED: 'order_created',
    WAREHOUSE_ORDER_UPDATED: 'order_updated',
    WAREHOUSE_ORDER_REMOVED: 'order_removed',
    WAREHOUSE_ORDER_STATUS_CHANGED: 'order_status_changed',
    WAREHOUSE_ORDER_PRINT_STATUS_CHANGED: 'order_print_status_changed',
    WAREHOUSE_ORDER_LOG_CREATED: 'order_log_created',
    WAREHOUSE_ORDER_LOG_DELETED: 'order_log_deleted',
    WAREHOUSE_ORDER_LOG_UPDATED: 'order_log_updated',
    WAREHOUSE_ORDER_PRINTED: 'order_printed',
    WAREHOUSE_ORDER_UPDATED_COMMENT: 'order_updated_comment',
    WAREHOUSE_ORDER_UPDATED_PRINTED: 'order_updated_printed',
    WAREHOUSE_ORDER_UPDATED_PRINT_STATUS: 'order_updated_print_status',
    WAREHOUSE_ORDER_UPDATED_NOTES: 'order_updated_notes',
    WAREHOUSE_ORDER_UPDATE_LOGS: 'order_update_logs',

    // Warehouse List Events
    WAREHOUSE_ORDER_UPDATED_SHIP_BY: 'order_updated_ship_by',
    WAREHOUSE_ORDER_UPDATED_SORT_INDEX: 'order_updated_sort_index',
    WAREHOUSE_ORDER_UPDATED_SHIP_BY_LIST: 'order_updated_ship_by_list',
    WAREHOUSE_ORDER_REMOVE_SHIP_BY_LIST: 'order_remove_ship_by_list',
    WAREHOUSE_ORDER_CREATED_SHIP_BY_LIST: 'order_created_ship_by_list',
    WAREHOUSE_ORDER_CHANGED_SHIP_BY: 'order_changed_ship_by',

    // Warehouse Mark Done Events
    WAREHOUSE_ORDER_MARK_DONE: 'order_mark_done',
    WAREHOUSE_ORDER_FREIGHT_SHIPPING_DONE: 'order_freight_shipping_done',
    WAREHOUSE_ORDER_PICKUP_DONE: 'order_pickup_done',
    WAREHOUSE_ORDER_PUSH_TO_SE: 'order_push_to_se',
    WAREHOUSE_ORDER_MARK_DONE_READY_FOR_SHIPMENT: 'order_mark_done_ready_for_shipment',
} as const;

export const MERCURE_TOPICS = Object.fromEntries(
    Object.entries(BASE_MERCURE_TOPICS).map(([key, value]) => [key, `${DOMAIN_PREFIX}_${value}`])
)

export interface MercureEvent {
    topic: MercureTopics;
    domain: string
    data: any;
    type: string;
    triggeredBySession: string;
}

export type MercureTopics = typeof MERCURE_TOPICS[keyof typeof MERCURE_TOPICS];

export const PICKUP_DONE = 'PICKUP_DONE';
export const FREIGHT_SHIPPING_DONE = 'FREIGHT_SHIPPING_DONE';
export const PUSH_TO_SE = 'PUSH_TO_SE_MARK_DONE';
export const MARK_DONE = 'MARK_DONE';
export const MARK_DONE_READY_FOR_SHIPMENT = 'MARK_DONE_READY_FOR_SHIPMENT';

export const getTypes = (): Record<string, string> => {
    return {
        PICKUP_DONE: PICKUP_DONE,
        FREIGHT_SHIPPING_DONE: FREIGHT_SHIPPING_DONE,
        PUSH_TO_SE_MARK_DONE: PUSH_TO_SE,
        MARK_DONE: MARK_DONE,
        MARK_DONE_READY_FOR_SHIPMENT: MARK_DONE_READY_FOR_SHIPMENT,
    };
};

export type MarkDoneTypes = typeof PICKUP_DONE | typeof FREIGHT_SHIPPING_DONE | typeof PUSH_TO_SE | typeof MARK_DONE | typeof MARK_DONE_READY_FOR_SHIPMENT;