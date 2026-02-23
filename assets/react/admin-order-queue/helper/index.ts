import { isNull, update } from "lodash";
import { sortOrders } from "./sort";
import ConfigState, { ListProps, Order, OrderDetails, OrdersShipBy } from "../redux/reducer/config/interface";
import { ASC, SortOrder } from "../constants/sort.constants";
import AppState, { OrderTagLabels, OrderTags } from "../redux/reducer/interface";
import axios, { AxiosError } from "axios";
import moment from "moment";
import { message } from "antd";


export const buildLists = (state: AppState, sortOrder: SortOrder = ASC): ListProps[] => {
    const { lists, ordersShipBy } = state.config;

    const updatedLists: ListProps[] = lists.map((list) => {
        const normalizedShipBy = normalizeDate(list.shipBy);
        const shipByOrders: OrderDetails[] = ordersShipBy[normalizedShipBy] || [];

        const warehouseOrders: OrderDetails[] = sortOrders(shipByOrders, sortOrder);

        return {
            ...list,
            warehouseOrders: warehouseOrders,
        };
    });

    return updatedLists;
};

export const normalizeDate = (date: string): string => {
    return new Date(date).toLocaleDateString("en-CA");
}

export const getActiveTags = (tags: { [key: string]: { name: string; active: boolean } }) => {
    return Object.entries(tags)
        .filter(([_, tag]) => tag.active)
        .map(([key, tag]) => ({
            key,
            name: tag.name,
            color: getTagColor(key),
        }));
};

export const getTagColor = (tagKey: string): string => {
    const colorMap: { [key: string]: string } = {
        BLIND_SHIPPING: '#f4a100',
        DELAYED: '#e81500',
        DIE_CUT: '#f4a100',
        FREIGHT: '#f4a100',
        REQUEST_PICKUP: '#6900c7',
        SAMPLE: '#0061f2',
        SCORING: '#f4a100',
        RUSH: '#0061f2',
        REPEAT_ORDER: '#0061f2',
    };

    return colorMap[tagKey] || '#d9d9d9';
};

export const activeTags = (tags: { [key: string]: { name: string; active: boolean } }) => {
    return Object.entries(tags)
        .filter(([_, tag]) => tag.active)
        .map(([key, tag]) => key);
};

export const buildOrderTagOptions = (): { label: string; value: OrderTags }[] => {
    return Object.values(OrderTags).map(tag => {
        const label = OrderTagLabels[tag];
        return label ? { label, value: tag } : null;
    })
    .filter((opt): opt is { label: string; value: OrderTags } => opt !== null);
};

interface RefreshResponse {
    lists: ListProps[];
    ordersShipBy: OrdersShipBy;
}

export const refresh = async (printer: string | null): Promise<RefreshResponse | null> => {
    if (!printer) {
        console.warn('Printer is null or undefined');
        return null;
    }

    try {
        const response = await axios.post<RefreshResponse>('/warehouse/queue-api/warehouse-orders/refresh', { printer });
        if (response.status === 200) {
            return response.data;
        } else {
            console.warn(`Unexpected status code: ${response.status}`);
            return null;
        }
    } catch (error) {
        console.error('Error fetching refreshed data:', error);
        return null;
    }
};

export const initializeEpAutomation = async (orderId: string|number): Promise<void> => {

    if (isNull(orderId)) {
        message.error('Order ID is null or undefined');
        return
    }

    try {

        const response = await axios.post('/easypost/initialize-ep-automation', { orderId });
        if (response.data.success === true) {
            message.success(response.data.message || 'Successfully initialized EP automation');
        } else if(response.data.success === false) {
            message.error(response.data.message || 'Something went wrong: ' + response.data.message);
        } else {
            console.warn(`Unexpected status code: ${response}`);
            message.error('Unexpected status code: ' + response.status);
        }

    } catch (error: any) {
        console.error('Error fetching refreshed data:', error);
        message.error('Error fetching refreshed data: ' + error);
    } finally {
        return;
    }
}

export const markDone = async (orderId: string|number, type: string): Promise<{ success: boolean, message: string }> => {

    if (isNull(orderId)) {
        return {
            'success': false,
            'message': 'Order ID is null or undefined'
        };
    }

    try {

        const response = await axios.post('/warehouse/queue-api/warehouse-orders/mark-done', { orderId, type });

        if (response.status === 200) {
            return {
                'success': true,
                'message': response.data.message
            };
        } else {
            console.warn(`Unexpected status code: ${response}`);
            return {
                'success': false,
                'message': 'Unexpected status code: ' + response.status
            };
        }

    } catch (error: any) {
        console.error('Error fetching refreshed data:', error);
        return {
            'success': false,
            'message': 'Error fetching refreshed data: ' + error
        };
    }
}

export const getShipByColor = (shipBy: string): string => {
    const normalizedDate = moment(shipBy).format('YYYY-MM-DD');
    const today = moment().startOf('day');

    if (moment(normalizedDate).isBefore(today)) {
        return '#ffe5df';
    } else if (moment(normalizedDate).isSame(today)) {
        return '#fff5df';
    } else {
        return '#f2f6fc';
    }
}

export const updateTabsPrintersCount = (printersArray: any[]) => {
    printersArray.forEach((printer) => {
        const element = document.querySelector(`[data-printer-key="${printer.label}"]`);
        const params = new URLSearchParams(window.location.search);
        const wq = params.get('wq') ?? null;
        if (element && isNull(wq)) {
            const badge = element.querySelector('.badge');
            if (badge) {
                badge.textContent = printer.orderCount;
            }
        }
    });
};