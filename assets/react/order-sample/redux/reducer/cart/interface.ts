import { Frame, GrommetColor, Grommets, ImprintColor, Shape, Sides } from "@react/editor/redux/interface";

export const ISides = Sides;
export type ISides = Sides;

export const IImprintColor = ImprintColor;
export type IImprintColor = ImprintColor;

export const IGrommets = Grommets;
export type IGrommets = Grommets;

export const IGrommetColor = GrommetColor;
export type IGrommetColor = GrommetColor;

export const IFrame = Frame;
export type IFrame = Frame;

export const IShape = Shape;
export type IShape = Shape;

export const IAddOnPrices = {
    FRAME: {
        [IFrame.NONE]: 0,
        [IFrame.WIRE_STAKE_10X30]: 1.79,
        [IFrame.WIRE_STAKE_10X24]: 1.79,
        [IFrame.WIRE_STAKE_10X30_PREMIUM]: 1.79 * 2,
        [IFrame.WIRE_STAKE_10X24_PREMIUM]: 1.79 * 2,
        [IFrame.WIRE_STAKE_10X30_SINGLE]: 1.79 / 2,
    },
    SIDES: {
        [ISides.SINGLE]: 0,
        [ISides.DOUBLE]: 0.50,
    },
    GROMMETS: {
        [IGrommets.NONE]: 0,
        [IGrommets.TOP_CENTER]: 10,
        [IGrommets.TOP_CORNERS]: 15,
        [IGrommets.FOUR_CORNERS]: 20,
        [IGrommets.SIX_CORNERS]: 25,
        [IGrommets.CUSTOM_PLACEMENT]: 30
    },
    GROMMET_COLOR: {
        [IGrommetColor.SILVER]: 0,
        [IGrommetColor.BLACK]: 10,
        [IGrommetColor.GOLD]: 5,
    },
    IMPRINT_COLOR: {
        [IImprintColor.ONE]: 0,
        [IImprintColor.TWO]: 10,
        [IImprintColor.THREE]: 20,
        [IImprintColor.UNLIMITED]: 30,
    },
    SHAPE: {
        [IShape.SQUARE]: 0,
        [IShape.CIRCLE]: 0.50,
        [IShape.OVAL]: 0.50,
        [IShape.CUSTOM]: 0.50,
        [IShape.CUSTOM_WITH_BORDER]: 0.50
    }
} as const;


export interface IAddOnProps {
    key: string;
    label: string;
    displayText?: string;
    amount: number;
    unitAmount: number;
    type: 'FIXED' | 'PERCENTAGE';
    quantity: number;
}


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

export interface ICustomSizeProps {
    isCustomSize: boolean;
    templateSize: {
        width: number;
        height: number;
    }
    productId: number | string;
    parentSku: string;
    sku: string;
    image: string;
    category: string;
    closestVariant: string;
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
    sides: ISides,
    imprintColor: IImprintColor,
    grommets: IGrommets,
    grommetColor: IGrommetColor,
    frame: IFrame,
    shape: IShape,
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
    addons: {
        [key: string]: IAddOnProps | { [key: string]: IAddOnProps };
    },
    customSize: ICustomSizeProps;
    isCustomSize: boolean;
    isCustom: boolean;
    isSample: boolean;
    isWireStake: boolean;
    isBlankSign: boolean;
    previewType: string;
    templateSize: {
        width: number;
        height: number;
    }
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
        addons?: any;
        sides?: ISides;
        shape?: IShape;
        id: string | number;
    };
}