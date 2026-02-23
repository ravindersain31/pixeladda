import ConfigState from "@react/admin-order-queue/redux/reducer/config/interface";

export default interface AppState {
    config: ConfigState;
}

export enum OrderTags {
    BLIND_SHIPPING = 'BLIND_SHIPPING',
    FREIGHT = 'FREIGHT',
    SAMPLE = 'SAMPLE',
    REQUEST_PICKUP = 'REQUEST_PICKUP',
    DIE_CUT = 'DIE_CUT',
    DELAYED = 'DELAYED',
    SCORING = 'SCORING',
    SATURDAY_DELIVERY = 'SATURDAY_DELIVERY',
    SPLIT_ORDER = 'SPLIT_ORDER',
    RUSH = 'RUSH',
    SUPER_RUSH = 'SUPER_RUSH',
    REPEAT_ORDER = 'REPEAT_ORDER',
}

export const OrderTagLabels: Partial<Record<OrderTags, string>> = {
    [OrderTags.BLIND_SHIPPING]: 'Blind Shipping',
    [OrderTags.FREIGHT]: 'Freight',
    [OrderTags.SAMPLE]: 'Sample',
    [OrderTags.REQUEST_PICKUP]: 'Request Pickup',
    [OrderTags.DIE_CUT]: 'Die Cut',
    // [OrderTags.DELAYED]: 'Delayed',
    [OrderTags.SCORING]: 'Scoring',
    [OrderTags.SPLIT_ORDER]: 'Split Order',
    [OrderTags.SATURDAY_DELIVERY]: 'Saturday Delivery',
    // [OrderTags.RUSH]: 'Rush',
    // [OrderTags.SUPER_RUSH]: 'Super Rush',
    [OrderTags.REPEAT_ORDER]: 'Repeat Order',
};

export interface FilterCategory {
    id: string;
    name: string;
    type: 'checkbox' | 'select' | 'date-range';
    options: FilterOption[];
}

export interface FilterOption {
    id: string;
    label: string;
    value: string | number | boolean;
    selected: boolean;
}

export interface DateRangeFilter {
    startDate: string | null;
    endDate: string | null;
}

export interface ActiveFilter {
    categoryId: string;
    optionId: string;
    value: string | number | boolean;
}

export interface FiltersState {
    orderId?: string;
    status?: string[];
    dateRange?: [string, string] | [null, null];
    reset?: boolean;
    globalSearch?: string;
}