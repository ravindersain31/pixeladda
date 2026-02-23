import ConfigState from "./interface.ts"

export {default as initialize} from "@react/wire-stake/redux/reducer/config/initialize.case.ts";
export {default as updateProduct} from "@react/wire-stake/redux/reducer/config/updateProduct.case.ts";

const initialState: ConfigState = {
    initialized: false,
    store: {
        id: '',
        name: '',
        shortName: '',
        domainId: '',
        domainName: '',
        domain: '',
        currencyId: '',
        currencyName: '',
        currencySymbol: '',
        currencyCode: 'USD',
    },
    product: {
        id: '',
        sku: '',
        isWireStake: false,
        isBlankSign: false,
        isSelling: false,
        variants: [],
        pricing: {
            quantities: [],
            frames: {},
        },
        shipping: {},
        productType: {
            id: null,
            name: null,
            slug: null,
            isCustomizable: false,
            allowCustomSize: false,
            quantityType: 'BY_SIZES'
        },
        deliveryMethods: {}
    },
    links: {
        add_to_cart: '',
    },
    initialData: {
        variant: '',
        quantity: 0,
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