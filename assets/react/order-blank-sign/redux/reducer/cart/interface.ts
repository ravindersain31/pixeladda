
export interface DeliverDateProp {
    day: number;
    isSaturday: boolean;
    free: boolean;
    date: string;
    discount: number;
    timestamp: number;
}

export interface IDeliveryMethodProps {
    key: string;
    label: string;
    type: string;
    discount: number,
}

export default interface CartState {
    deliveryMethod: IDeliveryMethodProps,
    deliveryDate: DeliverDateProp,
    isBlindShipping: boolean;
    subTotalAmount: number;
    totalAmount: number;
    totalShipping: number;
    totalShippingDiscount: number;
    totalQuantity: number;
    items: {
        [key: string]: ItemProps
    };
    shipping: {
        day: number | null;
        date: string | null;
        amount: number | null;
    };
    readyForCart: boolean;
    uploadedArtworks: [];
    additionalNote: string;
}
export interface ItemProps {
    id: number;
    productId: number | string;
    itemId: number | string | null;
    label?: string;
    name: string;
    image: string;
    sku: string;
    quantity: number;
    price: number;
    unitAmount: number;
    unitAddOnsAmount: number;
    totalAmount: number;
    addons: {},
    additionalNote: string|null;
}

export enum DeliveryMethod {
    DELIVERY = 'DELIVERY',
    REQUEST_PICKUP = 'REQUEST_PICKUP'
}

export const SHIPPING_MAX_DISCOUNT_AMOUNT = 50;

export interface IDeliveryMethodProps {
    key: string;
    label: string;
    type: string;
    discount: number,
}

export interface ICartItem {
    quantity: number | string;
    data: {
        totalAmount: number | string;
        shipping?: {
            amount: number | string;
        };
        isWireStake?: boolean;
        isBlankSign?: boolean;
        name?: string;
    };
}