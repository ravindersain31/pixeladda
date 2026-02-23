import { IDeliveryMethodProps } from "@orderBlankSign/redux/reducer/cart/interface";

export interface VariantProps {
    id: number;
    productId: string | number;
    name: string;
    label: string;
    template: string;
    itemId: string;
    additionalNote: string | null;
    templateJson: object | string | null;
    isCustomSize: boolean;
    image: string;
    previewType: string;
    customTemplate?: string;
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