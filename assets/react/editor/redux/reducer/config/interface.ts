import {CanvasDataProps} from "@react/editor/redux/reducer/canvas/interface.ts";
import {DeliveryMethodProps} from "@react/editor/redux/reducer/editor/interface";
import {Frame, Flute, GrommetColor, Grommets, ImprintColor, Sides} from "@react/editor/redux/interface.ts";
import { AddOnPrices, Shape } from "@react/editor/redux/reducer/editor/interface.ts";

export interface VariantProps {
    id: number;
    productId: string | number;
    name: string;
    label: string;
    template: string;
    itemId: string;
    additionalNote: string | null;
    canvasData: CanvasDataProps;
    templateJson: object | string | null;
    isCustomSize: boolean;
    image: string;
    previewType: string;
    customTemplate?: string;
    customTemplateLabel?: string;
}

export interface templateSizeProps {
    width: number;
    height: number;
}

export interface closestVariantProps {
    name: string;
    price: number;
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

export default interface ConfigState {
    initialized: boolean;
    store: {
        id?: number;
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
    product: ProductConfig,
    wireStakeProduct: ProductConfig,
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
    }
    links: {
        [key: string]: string
    },
    categories: {
        id: number;
        name: string;
        thumbnailUrl: string;
        slug: string;
    }[]
    artwork: {
        categories: {
            id: number;
            name: string;
        }[]
    }
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

export interface ProductConfig {
    id: string;
    isCustomizable: boolean;
    isCustom: boolean;
    isYardLetters: boolean;
    isYardSign: boolean;
    isDieCut: boolean;
    isBigHeadCutouts: boolean;
    isHandFans: boolean;
    isSelling: boolean;
    sku: string;
    productImages: string[];
    variants: VariantProps[];
    pricing: PricingProps;
    framePricing: FramePricingProps;
    customVariant: VariantProps[];
    productType: {
        id: number | null;
        name: string | null;
        slug: string | null;
        isCustomizable: boolean;
        allowCustomSize: boolean;
        quantityType: 'BY_SIZES' | 'BY_QUANTITY';
    },
    productMetaData: {
        totalSigns: number | null;
        frameTypes: {
            [key: string]: number;
        } | null;
    }
    category: {
        id: number | null;
        name: string | null;
        slug: string | null;
    },
    deliveryMethods: {
        [key: string]: DeliveryMethodProps
    },
    shipping: {
        [key: string]: object
    }
}

export const Addons = {
    sides: {
        [Sides.SINGLE]: {
            key: Sides.SINGLE,
            displayText: 'Single Sided',
            label: 'Choose Your Sides (Single Sided)',
            amount: AddOnPrices.SIDES[Sides.SINGLE],
            type: 'PERCENTAGE',
            quantity: 0,
        },
        [Sides.DOUBLE]: {
            key: Sides.DOUBLE,
            displayText: 'Double Sided',
            label: 'Choose Your Sides (Double Sided)',
            amount: AddOnPrices.SIDES[Sides.DOUBLE],
            type: 'PERCENTAGE',
            quantity: 0,
        },
    },
    grommets: {
        [Grommets.NONE]: {
            key: Grommets.NONE,
            displayText: 'No Grommets',
            label: 'Choose Your Grommets (3/8 Inch Hole) (None)',
            amount: AddOnPrices.GROMMETS[Grommets.NONE],
            type: 'PERCENTAGE',
            quantity: 0,
        },
        [Grommets.TOP_CENTER]: {
            key: Grommets.TOP_CENTER,
            displayText: 'Grommets (Top Center)',
            label: 'Choose Your Grommets (3/8 Inch Hole) (Top Center)',
            amount: AddOnPrices.GROMMETS[Grommets.TOP_CENTER],
            type: 'PERCENTAGE',
            quantity: 0,
        },
        [Grommets.TOP_CORNERS]: {
            key: Grommets.TOP_CORNERS,
            displayText: 'Grommets (Top Corners)',
            label: 'Choose Your Grommets (3/8 Inch Hole) (Top Corners)',
            amount: AddOnPrices.GROMMETS[Grommets.TOP_CORNERS],
            type: 'PERCENTAGE',
            quantity: 0,
        },
        [Grommets.FOUR_CORNERS]: {
            key: Grommets.FOUR_CORNERS,
            displayText: 'Grommets (Four Corners)',
            label: 'Choose Your Grommets (3/8 Inch Hole) (Four Corners)',
            amount: AddOnPrices.GROMMETS[Grommets.FOUR_CORNERS],
            type: 'PERCENTAGE',
            quantity: 0,
        },
        [Grommets.SIX_CORNERS]: {
            key: Grommets.SIX_CORNERS,
            displayText: 'Grommets (Six Corners)',
            label: 'Choose Your Grommets (3/8 Inch Hole) (Six Corners)',
            amount: AddOnPrices.GROMMETS[Grommets.SIX_CORNERS],
            type: 'PERCENTAGE',
            quantity: 0,
        },
        [Grommets.CUSTOM_PLACEMENT]: {
            key: Grommets.CUSTOM_PLACEMENT,
            displayText: 'Grommets (Custom Placement)',
            label: 'Choose Your Grommets (3/8 Inch Hole) (Custom Placement)',
            amount: AddOnPrices.GROMMETS[Grommets.CUSTOM_PLACEMENT],
            type: 'PERCENTAGE',
            quantity: 0,
        },
    },
    grommetColor: {
        [GrommetColor.SILVER]: {
            key: GrommetColor.SILVER,
            displayText: 'Silver Grommets',
            label: 'Choose Grommets Color (Silver)',
            amount: AddOnPrices.GROMMET_COLOR[GrommetColor.SILVER],
            type: 'PERCENTAGE',
            quantity: 0,
        },
        [GrommetColor.BLACK]: {
            key: GrommetColor.BLACK,
            displayText: 'Black Grommets',
            label: 'Choose Grommets Color (Black)',
            amount: AddOnPrices.GROMMET_COLOR[GrommetColor.BLACK],
            type: 'PERCENTAGE',
            quantity: 0,
        },
        [GrommetColor.GOLD]: {
            key: GrommetColor.GOLD,
            displayText: 'Gold Grommets',
            label: 'Choose Grommets Color (Gold)',
            amount: AddOnPrices.GROMMET_COLOR[GrommetColor.GOLD],
            type: 'PERCENTAGE',
            quantity: 0,
        },
    },
    imprintColor: {
        [ImprintColor.ONE]: {
            key: ImprintColor.ONE,
            displayText: '1 Imprint Color',
            label: 'Imprint Color (1 Imprint Colors)',
            amount: AddOnPrices.IMPRINT_COLOR[ImprintColor.ONE],
            type: 'PERCENTAGE',
            quantity: 0,
        },
        [ImprintColor.TWO]: {
            key: ImprintColor.TWO,
            displayText: '2 Imprint Color',
            label: 'Imprint Color (2 Imprint Colors)',
            amount: AddOnPrices.IMPRINT_COLOR[ImprintColor.TWO],
            type: 'PERCENTAGE',
            quantity: 0,
        },
        [ImprintColor.THREE]: {
            key: ImprintColor.THREE,
            displayText: '3 Imprint Color',
            label: 'Imprint Color (3 Imprint Colors)',
            amount: AddOnPrices.IMPRINT_COLOR[ImprintColor.THREE],
            type: 'PERCENTAGE',
            quantity: 0,
        },
        [ImprintColor.UNLIMITED]: {
            key: ImprintColor.UNLIMITED,
            displayText: 'Unlimited Imprint Color',
            label: 'Imprint Color (Unlimited Imprint Colors)',
            amount: AddOnPrices.IMPRINT_COLOR[ImprintColor.UNLIMITED],
            type: 'PERCENTAGE',
            quantity: 0,
        },
    },
    flute: {
        [Flute.VERTICAL]: {
            key: Flute.VERTICAL,
            displayText: 'Vertical Flutes',
            label: 'Choose Flutes Direction (Vertical)',
            amount: AddOnPrices.Flute[Flute.VERTICAL],
            type: 'PERCENTAGE',
            quantity: 0,
        },
        [Flute.HORIZONTAL]: {
            key: Flute.HORIZONTAL,
            displayText: 'Horizontal Flutes',
            label: 'Choose Flutes Direction (Horizontal)',
            amount: AddOnPrices.Flute[Flute.HORIZONTAL],
            type: 'PERCENTAGE',
            quantity: 0,
        },
    },
    frame: {
        [Frame.NONE]: {
            key: Frame.NONE,
            displayText: 'No Frame',
            label: 'Choose Your Frame (None)',
            amount: AddOnPrices.FRAME[Frame.NONE],
            type: Frame.FIXED,
            quantity: 0,
        },
        [Frame.WIRE_STAKE_10X30]: {
            key: Frame.WIRE_STAKE_10X30,
            displayText: 'Standard 10"W x 30"H Wire Stake Frame',
            label: 'Choose Your Frame (Standard 10"W x 30"H Wire Stake)',
            amount: AddOnPrices.FRAME[Frame.WIRE_STAKE_10X30],
            type: Frame.FIXED,
            quantity: 0,
        },
        [Frame.WIRE_STAKE_10X24]: {
            key: Frame.WIRE_STAKE_10X24,
            displayText: 'Standard 10"W x 24"H Wire Stake Frame',
            label: 'Choose Your Frame (Standard 10"W x 24"H Wire Stake)',
            amount: AddOnPrices.FRAME[Frame.WIRE_STAKE_10X24],
            type: Frame.FIXED,
            quantity: 0,
        },
        [Frame.WIRE_STAKE_10X30_PREMIUM]: {
            key: Frame.WIRE_STAKE_10X30_PREMIUM,
            displayText: 'Premium 10"W x 30"H Wire Stake Frame',
            label: 'Choose Your Frame (Premium 10"W x 30"H Wire Stake)',
            amount: AddOnPrices.FRAME[Frame.WIRE_STAKE_10X30_PREMIUM],
            type: Frame.FIXED,
            quantity: 0,
        },
        [Frame.WIRE_STAKE_10X24_PREMIUM]: {
            key: Frame.WIRE_STAKE_10X24_PREMIUM,
            displayText: 'Premium 10"W x 24"H Wire Stake Frame',
            label: 'Choose Your Frame (Premium 10"W x 24"H Wire Stake)',
            amount: AddOnPrices.FRAME[Frame.WIRE_STAKE_10X24_PREMIUM],
            type: Frame.FIXED,
            quantity: 0,
        },
        [Frame.WIRE_STAKE_10X30_SINGLE]: {
            key: Frame.WIRE_STAKE_10X30_SINGLE,
            displayText: 'Single 30"H Wire Stake Frame',
            label: 'Choose Your Frame (Single 30"H Wire Stake)',
            amount: AddOnPrices.FRAME[Frame.WIRE_STAKE_10X30_SINGLE],
            type: Frame.FIXED,
            quantity: 0,
        },
    },
    shape: {
        [Shape.SQUARE]: {
            key: Shape.SQUARE,
            displayText: 'Square / Rectangle Shape',
            label: 'Choose Your Shape (Square Shape)',
            amount: AddOnPrices.SHAPE[Shape.SQUARE],
            type: 'PERCENTAGE',
            quantity: 0,
        },
        [Shape.CIRCLE]: {
            key: Shape.CIRCLE,
            displayText: 'Circle Shape',
            label: 'Choose Your Shape (Circle Shape)',
            amount: AddOnPrices.SHAPE[Shape.CIRCLE],
            type: 'PERCENTAGE',
            quantity: 0,
        },
        [Shape.OVAL]: {
            key: Shape.OVAL,
            displayText: 'Oval Shape',
            label: 'Choose Your Shape (Oval Shape)',
            amount: AddOnPrices.SHAPE[Shape.OVAL],
            type: 'PERCENTAGE',
            quantity: 0,
        },
        [Shape.CUSTOM]: {
            key: Shape.CUSTOM,
            displayText: 'Custom Shape',
            label: 'Choose Your Shape (Custom Shape)',
            amount: AddOnPrices.SHAPE[Shape.CUSTOM],
            type: 'PERCENTAGE',
            quantity: 0,
        },
        [Shape.CUSTOM_WITH_BORDER]: {
            key: Shape.CUSTOM_WITH_BORDER,
            displayText: 'Custom with Border Shape',
            label: 'Choose Your Shape (Custom with Border Shape)',
            amount: AddOnPrices.SHAPE[Shape.CUSTOM_WITH_BORDER],
            type: 'PERCENTAGE',
            quantity: 0,
        },
    }
} as const;

export const AddonDisplayText = {
    sides: {
        [Sides.SINGLE]: 'Single Sided',
        [Sides.DOUBLE]: 'Double Sided',
    },
    grommets: {
        [Grommets.NONE]: 'None',
        [Grommets.TOP_CENTER]: 'Top Center',
        [Grommets.TOP_CORNERS]: 'Top Corners',
        [Grommets.FOUR_CORNERS]: 'Four Corners',
        [Grommets.SIX_CORNERS]: 'Six Corners',
    },
    grommetColor: {
        [GrommetColor.SILVER]: 'Silver',
        [GrommetColor.BLACK]: 'Black',
        [GrommetColor.GOLD]: 'Gold',
    },
    imprintColor: {
        [ImprintColor.ONE]: 'One Color',
        [ImprintColor.TWO]: 'Two Colors',
        [ImprintColor.THREE]: 'Three Colors',
        [ImprintColor.UNLIMITED]: 'Unlimited Colors',
    },
    flute: {
        [Flute.VERTICAL]: 'Vertical Flutes',
        [Flute.HORIZONTAL]: 'Horizontal Flutes',
    },
    frame: {
        [Frame.NONE]: 'No Wire Stake',
        [Frame.WIRE_STAKE_10X30]: 'Standard 10"W X 30"H',
        [Frame.WIRE_STAKE_10X24]: 'Standard 10"W X 24"H',
        [Frame.WIRE_STAKE_10X30_PREMIUM]: 'Premium 10"W X 30"H',
        [Frame.WIRE_STAKE_10X24_PREMIUM]: 'Premium 10"W X 24"H',
        [Frame.WIRE_STAKE_10X30_SINGLE]: 'Single 30"H',
    },
    shape: {
        [Shape.SQUARE]: 'Square / Rectangle',
        [Shape.CIRCLE]: 'Circle',
        [Shape.OVAL]: 'Oval',
        [Shape.CUSTOM]: 'Custom',
        [Shape.CUSTOM_WITH_BORDER]: 'Custom with Border',
    }
} as const;
