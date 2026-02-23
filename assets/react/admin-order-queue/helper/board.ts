import { ColumnMap } from "@react/admin-order-queue/Types/BoardTypes";
import { ListProps, type OrderDetails } from "@react/admin-order-queue/redux/reducer/config/interface";
import { normalizeDate } from "@react/admin-order-queue/helper";
import { FiltersState } from "../redux/reducer/interface";
import { message } from "antd";

export function getBoardState(lists: ListProps[], filters: FiltersState) {

    if (!lists || lists.length === 0) {
        return {
            columnMap: {},
            orderedColumnIds: [],
            lastOperation: null,
        };
    }

    const filteredLists = lists.map((list) => ({
        ...list,
        warehouseOrders: getFilteredOrders(list.warehouseOrders, filters)
    }));

    const isDateRangeApplied = Boolean(filters.dateRange && (filters.dateRange[0] || filters.dateRange[1]));

    const columnMap: ColumnMap = buildColumnMap(filteredLists, filters, isDateRangeApplied);

    const orderedColumnIds = buildOrderedColumnIds(columnMap);

    const isEmpty = orderedColumnIds.every(columnId => {
        const column = columnMap[columnId];
        return !column || column.items.length === 0;
    });

    if (isEmpty) {
        message.error('No orders found for the selected filters.');
    }

    return {
        columnMap,
        orderedColumnIds,
    };
}


export function buildColumnMap(lists: ListProps[], filters: FiltersState, isDateRangeApplied: boolean): ColumnMap {
    return lists.reduce((map, list) => {

        if (isDateRangeApplied) {
            const [startDate, endDate] = filters.dateRange || [null, null];
            const shipByDate = new Date(normalizeDate(list.shipBy));

            const matchesDateRange =
                (!startDate || shipByDate >= new Date(startDate)) &&
                (!endDate || shipByDate <= new Date(endDate));

            if (!matchesDateRange) {
                return map;
            }
        }

        map[list.sortIndex] = {
            title: normalizeDate(list.shipBy),
            columnId: list.sortIndex,
            listId: list.id,
            shipBy: normalizeDate(list.shipBy),
            items: list.warehouseOrders,
        };
        return map;
    }, {} as ColumnMap);
}

export const buildOrderedColumnIds = (columnMap: ColumnMap) => {
    return Object.keys(columnMap);
}

export const getFilteredOrders = (
    orders: OrderDetails[],
    filters: FiltersState,
): OrderDetails[] => {
    return orders.filter((order) => {
        // Existing filters
        const matchesOrderId = filters.orderId ? order.order.orderId.toLowerCase().includes(filters.orderId.toLowerCase()) : true;

        const matchesStatus = filters.status && filters.status.length > 0 ? filters.status.includes(order.printStatus) : true;

        const [startDate, endDate] = filters.dateRange || [null, null];

        const shipByDate = new Date(normalizeDate(order.shipBy));

        const matchesDateRange = (!startDate || shipByDate >= new Date(startDate)) && (!endDate || shipByDate <= new Date(endDate));

        // Global search logic
        const globalSearch = filters.globalSearch || '';
        const matchesGlobalSearch = globalSearch && globalSearch.length > 0
            ? recursiveSearch(order, globalSearch)
            : true;

        return matchesOrderId && matchesStatus && matchesDateRange && matchesGlobalSearch;
    });
};


const recursiveSearch = (obj: any, query: string): boolean => {
    if (!obj || !query) return false;

    const normalizedQuery = query.toLowerCase();

    // Check if the current object is a string
    if (typeof obj === 'string') {
        return obj.toLowerCase().includes(normalizedQuery);
    }

    // If the object is an array, recursively search each element
    if (Array.isArray(obj)) {
        return obj.some((item) => recursiveSearch(item, query));
    }

    // If the object is an object, recursively search each key-value pair
    if (typeof obj === 'object') {
        return Object.values(obj).some((value) => recursiveSearch(value, query));
    }

    return false;
};