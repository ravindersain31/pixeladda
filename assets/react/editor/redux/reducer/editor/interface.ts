import {CanvasDataProps} from "@react/editor/redux/reducer/canvas/interface.ts";
import { templateSizeProps } from "../config/interface";

export enum Sides {
    SINGLE = "SINGLE",
    DOUBLE = "DOUBLE",
}

export enum ImprintColor {
    ONE = "ONE",
    TWO = "TWO",
    THREE = "THREE",
    UNLIMITED = "UNLIMITED",
}

export enum Grommets {
    NONE = "NONE",
    TOP_CENTER = "TOP_CENTER",
    TOP_CORNERS = "TOP_CORNERS",
    FOUR_CORNERS = "ALL_FOUR_CORNERS",
    SIX_CORNERS = "SIX_CORNERS",
    CUSTOM_PLACEMENT = "CUSTOM_PLACEMENT",
}

export enum GrommetColor {
    SILVER = "SILVER",
    BLACK = "BLACK",
    GOLD = "GOLD",
}

export enum Frame {
    NONE = "NONE",
    WIRE_STAKE_10X30 = "WIRE_STAKE_10X30",
    WIRE_STAKE_10X24 = "WIRE_STAKE_10X24",
    WIRE_STAKE_10X30_PREMIUM = "WIRE_STAKE_10X30_PREMIUM",
    WIRE_STAKE_10X24_PREMIUM = "WIRE_STAKE_10X24_PREMIUM",
    WIRE_STAKE_10X30_SINGLE = "WIRE_STAKE_10X30_SINGLE",
}

export enum Flute {
    VERTICAL = "VERTICAL",
    HORIZONTAL = "HORIZONTAL",
}

export enum Frame {
    PERCENTAGE = 'PERCENTAGE',
    FIXED = 'FIXED'
}

export enum Flute {
    PERCENTAGE = 'PERCENTAGE',
    FIXED = 'FIXED'
}

export enum Shape {
    SQUARE = 'SQUARE',
    CIRCLE = 'CIRCLE',
    OVAL = 'OVAL',
    CUSTOM = 'CUSTOM',
    CUSTOM_WITH_BORDER = 'CUSTOM_WITH_BORDER'
}

export type HandFanVariantShape = "rectangle" | "hourglass" | "paddle" | "circle" | "square";

export enum DeliveryMethod {
    DELIVERY = 'DELIVERY',
    REQUEST_PICKUP = 'REQUEST_PICKUP'
}

export enum PreviewType {
    CANVAS = 'canvas',
    IMAGE = 'image'
}

export enum CustomArtwork {
    CUSTOM_DESIGN = "CUSTOM-DESIGN",
    YSP_LOGO = "YSP-LOGO"
}

export const YSP_LOGO_DISCOUNT = 5;
export const YSP_MAX_DISCOUNT_AMOUNT = 25;

export const PRE_PACKED_DISCOUNT = 20;
export const PRE_PACKED_MAX_DISCOUNT_AMOUNT = 100;

export const SHIPPING_MAX_DISCOUNT_AMOUNT = 50;
export const SHIPPING_MAX_DISCOUNT_AMOUNT_10 = 100;

export const AddOnPrices = {
    FRAME: {
        [Frame.NONE]: 0,
        [Frame.WIRE_STAKE_10X30]: 1.79,
        [Frame.WIRE_STAKE_10X24]: 1.79,
        [Frame.WIRE_STAKE_10X30_PREMIUM]: 1.79 * 2,
        [Frame.WIRE_STAKE_10X24_PREMIUM]: 1.79 * 2,
        [Frame.WIRE_STAKE_10X30_SINGLE]: 1.79 / 2,
    },
    Flute: {
        [Flute.VERTICAL]: 0.00,
        [Flute.HORIZONTAL]: 0.00,
    },
    SIDES: {
        [Sides.SINGLE]: 0,
        [Sides.DOUBLE]: 30,
    },
    GROMMETS: {
        [Grommets.NONE]: 0,
        [Grommets.TOP_CENTER]: 10,
        [Grommets.TOP_CORNERS]: 15,
        [Grommets.FOUR_CORNERS]: 20,
        [Grommets.SIX_CORNERS]: 25,
        [Grommets.CUSTOM_PLACEMENT]: 30
    },
    GROMMET_COLOR: {
        [GrommetColor.SILVER]: 0,
        [GrommetColor.BLACK]: 10,
        [GrommetColor.GOLD]: 5,
    },
    IMPRINT_COLOR: {
        [ImprintColor.ONE]: 0,
        [ImprintColor.TWO]: 10,
        [ImprintColor.THREE]: 20,
        [ImprintColor.UNLIMITED]: 30,
    },
    SHAPE: {
        [Shape.SQUARE]: 0,
        [Shape.CIRCLE]: 10,
        [Shape.OVAL]: 20,
        [Shape.CUSTOM]: 30,
        [Shape.CUSTOM_WITH_BORDER]: 35
    }
} as const;


export interface AddOnProps {
    key: string;
    label: string;
    displayText?: string;
    amount: number;
    unitAmount: number;
    type: 'FIXED' | 'PERCENTAGE';
    quantity: number;
}

export interface DeliveryMethodProps {
    key: string;
    label: string;
    type: string;
    discount: number,
}

export interface CustomSizeProps {
    isCustomSize: boolean;
    templateSize: {
        width: number;
        height: number;
    }
    productId: number | string;
    sku: string;
    image: string;
    category: string;
    closestVariant: string;
}

export interface ItemProps {
    id: number;
    productId: number | string;
    itemId: number | string | null;
    name: string;
    label: string;
    image: string;
    sku: string;
    quantity: number;
    price: number;
    unitAmount: number;
    unitAddOnsAmount: number;
    totalAmount: number;
    addons: {
        [key: string]: AddOnProps | { [key: string]: AddOnProps };
    },
    canvasData: CanvasDataProps;
    additionalNote: string|null;
    notes?: {
        [type: string]: {
            [key: string]: string;
        } | string;
    };
    templateJson?: object;
    isHelpWithArtwork: boolean;
    isEmailArtworkLater: boolean;
    YSPLogoDiscount: {
        hasLogo : boolean;
        discount : number;
        type : 'FIXED' | 'PERCENTAGE';
        discountAmount: number;
    };
    isFreeFreight: boolean;
    isCustom: boolean;
    customSize: CustomSizeProps;
    isCustomSize: boolean;
    isSample: boolean;
    isSelling: boolean;
    isWireStake: boolean;
    templateSize: {
        width: number;
        height: number;
    }
    previewType: PreviewType.CANVAS | PreviewType.IMAGE;
    customArtwork: customArtworkProps;
    customOriginalArtwork: customOriginalArtworkProps;
    prePackedDiscount?: {
        hasPrePacked : boolean;
        discount: number;
        type: 'FIXED' | 'PERCENTAGE';
        discountAmount: number;
    };
}

export interface customArtworkProps {
    [key: string] : {
        front: [];
        back: [];
    }
}

export interface originalArtworkImageObject {
    id: string;
    url: string;
    originalFileUrl: string;
}

export interface customOriginalArtworkProps {
    front: originalArtworkImageObject[];
    back: originalArtworkImageObject[];
}

export interface PricingChartProps {
    [key: string]: {
        [key: string]: string | object
    }
}

export interface DeliverDateProp {
    day: number;
    isSaturday: boolean;
    free: boolean;
    date: string;
    discount: number;
    timestamp: number;
    pricing?: PricingChartProps;
}

export default interface EditorState {
    sides: Sides,
    imprintColor: ImprintColor,
    grommets: Grommets,
    grommetColor: GrommetColor,
    frame: Frame | Frame[],
    flute: Flute,
    shape: Shape,
    deliveryMethod: DeliveryMethodProps,
    deliveryDate: DeliverDateProp,
    isBlindShipping: boolean;
    isFreeFreight: boolean;
    subTotalAmount: number;
    totalAmount: number;
    totalShipping: number;
    totalShippingDiscount: number;
    totalQuantity: number;
    isHelpWithArtwork: boolean;
    isEmailArtworkLater: boolean;
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
}


export interface StepConfig {
    show: boolean;
    stepNumber: number;
}

export type ProductTypeSlug = 'yard-sign' | 'yard-letters' | 'die-cut' | 'big-head-cutouts' | 'hand-fans';

export interface ProductTypeStepsConfig {
    [key: string]: { [key: string]: StepConfig };
}

export interface StepConfigProps {
    [stepName: string]: {
        show: boolean;
        stepNumber: number;
    }
}

export const productTypeStepsConfig: ProductTypeStepsConfig = {
    default: {
        ChooseYourSizes: { show: true, stepNumber: 1 },
        ChooseYourSides: { show: true, stepNumber: 2 },
        ChooseDesignOption: { show: true, stepNumber: 3 },
        CustomizeYourSigns: { show: true, stepNumber: 4 },
        ChooseYourShape: { show: true, stepNumber: 5 },
        ChooseImprintColor: { show: true, stepNumber: 6 },
        ChooseYourGrommets: { show: true, stepNumber: 7 },
        ChooseGrommetColor: { show: false, stepNumber: 8 },
        ChooseYourFlute: { show: false, stepNumber: 9 },
        ChooseYourFrame: { show: false, stepNumber: 10 },
        ChooseDeliveryDate: { show: true, stepNumber: 11 },
        ReviewOrderDetails: { show: true, stepNumber: 12 },
    },
    'yard-sign': {
        ChooseYourSizes: { show: true, stepNumber: 1 },
        ChooseYourSides: { show: true, stepNumber: 2 },
        ChooseDesignOption: { show: true, stepNumber: 3 },
        CustomizeYourSigns: { show: true, stepNumber: 4 },
        ChooseYourShape: { show: true, stepNumber: 5 },
        ChooseImprintColor: { show: true, stepNumber: 6 },
        ChooseYourGrommets: { show: true, stepNumber: 7 },
        ChooseGrommetColor: { show: false, stepNumber: 8 },
        ChooseYourFlute: { show: false, stepNumber: 9 },
        ChooseYourFrame: { show: false, stepNumber: 10 },
        ChooseDeliveryDate: { show: true, stepNumber: 11 },
        ReviewOrderDetails: { show: true, stepNumber: 12 },
    },
    'yard-letters': {
        ChooseYourSizes: { show: true, stepNumber: 1 },
        ChooseYourSides: { show: true, stepNumber: 2 },
        ChooseDesignOption: { show: true, stepNumber: 3 },
        CustomizeYourSigns: { show: false, stepNumber: 4 },
        ChooseYourShape: { show: false, stepNumber: 5 },
        ChooseImprintColor: { show: false, stepNumber: 6 },
        ChooseYourGrommets: { show: false, stepNumber: 7 },
        ChooseGrommetColor: { show: false, stepNumber: 8 },
        ChooseYourFrame: { show: false, stepNumber: 9 },
        ChooseDeliveryDate: { show: true, stepNumber: 10 },
        ReviewOrderDetails: { show: true, stepNumber: 11 },
    },
};

export interface EditorItems {
    name: string,
    quantity: number,
    isCustomSize: boolean,
    closestVariantSize: string,
    templateSize: templateSizeProps,
    itemId: number | string | null;
    id: number;
}