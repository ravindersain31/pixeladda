import ConfigState, { ProductConfig } from "./interface.ts"

export { default as initialize } from "./initialize.case.ts";
export { default as updateProduct } from "./updateProduct.case.ts";

const productConfig: ProductConfig = {
    id: '',
    sku: '',
    isCustomizable: false,
    isCustom: false,
    isYardLetters: false,
    isYardSign: false,
    isDieCut: false,
    isBigHeadCutouts: false,
    isHandFans: false,
    isSelling: false,
    variants: [],
    productImages: [],
    category: {
        id: null,
        name: null,
        slug: null,
    },
    productType: {
        id: null,
        name: null,
        slug: null,
        isCustomizable: false,
        allowCustomSize: false,
        quantityType: 'BY_SIZES'
    },
    productMetaData: {
        totalSigns: null,
        frameTypes: {},
    },
    pricing: {
        quantities: [],
        variants: {},
    },
    shipping: {},
    framePricing: {
        quantities: [],
        frames: {},
    },
    customVariant: [],
    deliveryMethods: {}
};

const initialState: ConfigState = {
    initialized: false,
    store: {
        currencyCode: "USD",
    },
    product: productConfig,
    wireStakeProduct: productConfig,
    addons: {},
    links: {
        add_to_cart: "",
        share_canvas: "",
    },
    categories: [],
    artwork: {
        categories: [],
    },
    initialData: {
        variant: '12x12',
        quantity: 1,
    },
    cart: {
        totalQuantity: 0,
        subTotal: 0,
        totalAmount: 0,
        totalShipping: 0,
        orderProtectionAmount: 0,
        orderProtection: false,
        currentItemQuantity: 0,
        currentItemSubtotal: 0,
        currentItemShipping: {},
        quantityBySizes: {},
        totalFrameQuantity: {},
        currentFrameQuantity: {},
        hasBiggerSizes: false,
        isBlindShipping: false,
        isFreeFreight: false,
        biggerSizes: {},
    }
}

export default initialState;