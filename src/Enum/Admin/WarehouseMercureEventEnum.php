<?php

namespace App\Enum\Admin;

class WarehouseMercureEventEnum
{

    // WAREHOUSE HEARTBEAT EVENTS
    public const WAREHOUSE_HEARTBEAT = 'heartbeat';

    // WAREHOUSE PRINTERS EVENTS
    public const WAREHOUSE_PRINTERS_COUNT = 'printers_count';

    // WAREHOUSE ORDERS EVENTS
    public const WAREHOUSE_PRINTER_CONNECTION_STATUS = 'printer_connection_status';
    public const WAREHOUSE_ORDER_CREATED = 'order_created';
    public const WAREHOUSE_ORDER_UPDATED = 'order_updated';
    public const WAREHOUSE_ORDER_REMOVED = 'order_removed';
    public const WAREHOUSE_ORDER_STATUS_CHANGED = 'order_status_changed';
    public const WAREHOUSE_ORDER_PRINT_STATUS_CHANGED = 'order_print_status_changed';
    public const WAREHOUSE_ORDER_LOG_CREATED = 'order_log_created';
    public const WAREHOUSE_ORDER_LOG_DELETED = 'order_log_deleted';
    public const WAREHOUSE_ORDER_LOG_UPDATED = 'order_log_updated';
    public const WAREHOUSE_ORDER_PRINTED = 'order_printed';
    public const WAREHOUSE_ORDER_UPDATED_COMMENT = 'order_updated_comment';
    public const WAREHOUSE_ORDER_UPDATED_PRINTED = 'order_updated_printed';
    public const WAREHOUSE_ORDER_UPDATED_PRINT_STATUS = 'order_updated_print_status';
    public const WAREHOUSE_ORDER_UPDATED_NOTES = 'order_updated_notes';
    public const WAREHOUSE_ORDER_UPDATE_LOGS = 'order_update_logs';

    // WAREHOUSE LIST EVENTS
    public const WAREHOUSE_ORDER_UPDATED_SHIP_BY = 'order_updated_ship_by';
    public const WAREHOUSE_ORDER_UPDATED_SORT_INDEX = 'order_updated_sort_index';
    public const WAREHOUSE_ORDER_UPDATED_SHIP_BY_LIST = 'order_updated_ship_by_list';
    public const WAREHOUSE_ORDER_REMOVE_SHIP_BY_LIST = 'order_remove_ship_by_list';
    public const WAREHOUSE_ORDER_CREATED_SHIP_BY_LIST = 'order_created_ship_by_list';
    public const WAREHOUSE_ORDER_CHANGED_SHIP_BY = 'order_changed_ship_by';

    // WAREHOUSE ORDER MARK DONE EVENTS
    public const WAREHOUSE_ORDER_MARK_DONE = 'order_mark_done';
    public const WAREHOUSE_ORDER_FREIGHT_SHIPPING_DONE = 'order_freight_shipping_done';
    public const WAREHOUSE_ORDER_PICKUP_DONE = 'order_pickup_done';
    public const WAREHOUSE_ORDER_PUSH_TO_SE = 'order_push_to_se';
    public const WAREHOUSE_ORDER_MARK_DONE_READY_FOR_SHIPMENT = 'order_mark_done_ready_for_shipment';

    // MARK DONE ENUM TYPE
    public const PICKUP_DONE = 'PICKUP_DONE';
    public const FREIGHT_SHIPPING_DONE = 'FREIGHT_SHIPPING_DONE';
    public const PUSH_TO_SE = 'PUSH_TO_SE_MARK_DONE';
    public const MARK_DONE = 'MARK_DONE';
    public const MARK_DONE_READY_FOR_SHIPMENT = 'MARK_DONE_READY_FOR_SHIPMENT';

    public static function getTypes(): array
    {
        return [
            self::PICKUP_DONE => self::PICKUP_DONE,
            self::FREIGHT_SHIPPING_DONE => self::FREIGHT_SHIPPING_DONE,
            self::PUSH_TO_SE => self::PUSH_TO_SE,
            self::MARK_DONE => self::MARK_DONE,
            self::MARK_DONE_READY_FOR_SHIPMENT => self::MARK_DONE_READY_FOR_SHIPMENT,
        ];
    }


    public static function getTopics(string $domainPrefix): array
    {
        $reflection = new \ReflectionClass(self::class);
        $constants = $reflection->getConstants();

        $topics = array_filter($constants, function ($key) {
            return strpos($key, 'WAREHOUSE_') === 0;
        }, ARRAY_FILTER_USE_KEY);

        return array_map(fn($topic) => "{$domainPrefix}_{$topic}", array_values($topics));
    }


    public static function getEvent(string $key, string $domainPrefix): ?string
    {
        return defined("self::$key") ? "{$domainPrefix}_" . constant("self::$key") : null;
    }
}