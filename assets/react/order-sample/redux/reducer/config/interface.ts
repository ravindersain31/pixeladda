import { IAddOnPrices, IDeliveryMethodProps, IShape, ISides } from "@orderSample/redux/reducer/cart/interface";

export interface ItemplateSizeProps {
    width?: number;
    height?: number;
}

export interface VariantProps {
    id: number | string;
    productId: string | number;
    name: string;
    label: string;
    template: string;
    itemId: string;
    additionalNote: string | null;
    templateJson: object | string | null;
    isCustomSize: boolean;
    image: string;
    sku: string;
    previewType: string;
    customTemplate?: string;
}

export interface FramePricingProps {
    quantities: number[];
    frames: {
        [key: string]: {
            label: string;
            pricing: {
                [key: string]: {
                    [key: string]: number;
                };
            }
        }
    }
}

export interface PricingProps {
    quantities: number[];
    variants: {
        [key: string]: {
            label: string;
            pricing: {
                [key: string]: {
                    [key: string]: number;
                };
            }
        }
    }
}

export default interface ConfigState {
    initialized: boolean;
    store: {
        id?: string;
        name?: string;
        shortName?: string;
        domainId?: string;
        domainName?: string;
        domain?: string;
        currencyId?: string;
        currencyName?: string;
        currencySymbol?: string;
        currencyCode?: string;
    },
    product: {
        id: string;
        isWireStake: boolean;
        isBlankSign: boolean;
        isSelling: boolean;
        sku: string;
        variants: VariantProps[]
        customVariant: VariantProps[]
        pricing: PricingProps;
        shipping: {
            [key: string]: object
        },
        productType: {
            id: number | null;
            name: string | null;
            slug: string | null;
            isCustomizable: boolean;
            allowCustomSize: boolean;
            quantityType: 'BY_SIZES' | 'BY_QUANTITY';
        },
        category: {
            id: number | null;
            name: string | null;
            slug: string | null;
        },
        deliveryMethods: {
            [key: string]: IDeliveryMethodProps
        },
    },
    links: {
        [key: string]: string
    },
    initialData: {
        variant: string;
        quantity: number;
    },
    addons: {
        [key: string]: {
            [key: string]: {
                key: string;
                displayText: string;
                label: string;
                amount: number;
                type: 'FIXED' | 'PERCENTAGE';
                quantity: number;
            }
        }
    },
    cart: {
        totalQuantity: number;
        subTotal: number;
        totalAmount: number;
        totalShipping: number;
        orderProtectionAmount: number;
        orderProtection: boolean;
        currentItemQuantity: number;
        currentItemSubtotal: number;
        currentItemShipping: any;
        totalFrameQuantity: {[frameType: string]: number};
        currentFrameQuantity: {[frameType: string]: number};
        hasBiggerSizes: boolean;
        isBlindShipping: boolean;
        isFreeFreight: boolean;
        biggerSizes: {
            [key: string]: string
        }
        quantityBySizes: {
            [key: string]: number
        };
    }
}

export const IAddons = {
    sides: {
        [ISides.SINGLE]: {
            key: ISides.SINGLE,
            displayText: 'Single Sided',
            label: 'Choose Your Sides (Single Sided)',
            amount: IAddOnPrices.SIDES[ISides.SINGLE],
            type: 'FIXED',
            quantity: 0,
        },
        [ISides.DOUBLE]: {
            key: ISides.DOUBLE,
            displayText: 'Double Sided',
            label: 'Choose Your Sides (Double Sided)',
            amount: IAddOnPrices.SIDES[ISides.DOUBLE],
            type: 'FIXED',
            quantity: 0,
        },
    },
    shape: {
        [IShape.SQUARE]: {
            key: IShape.SQUARE,
            displayText: 'Square / Rectangle Shape',
            label: 'Choose Your Shape (Square Shape)',
            amount: IAddOnPrices.SHAPE[IShape.SQUARE],
            type: 'FIXED',
            quantity: 0,
        },
        [IShape.CUSTOM]: {
            key: IShape.CUSTOM,
            displayText: 'Custom Shape',
            label: 'Choose Your Shape (Custom Shape)',
            amount: IAddOnPrices.SHAPE[IShape.CUSTOM],
            type: 'FIXED',
            quantity: 0,
        },
    }
} as const;

export const IAddonDisplayText = {
    sides: {
        [ISides.SINGLE]: 'Single Sided',
        [ISides.DOUBLE]: 'Double Sided',
    },
    shape: {
        [IShape.SQUARE]: 'Square / Rectangle',
        [IShape.CIRCLE]: 'Circle',
        [IShape.OVAL]: 'Oval',
        [IShape.CUSTOM]: 'Custom',
    }
} as const;

