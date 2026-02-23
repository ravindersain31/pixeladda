export { default as initialize } from "@orderBlankSign/redux/reducer/cart/initialize.case.ts";
export { default as upsertCartItem } from "@orderBlankSign/redux/reducer/cart/upsertCartItem.case.ts";
export { default as updateComment } from "@orderBlankSign/redux/reducer/cart/updateComment.case.ts";
export { default as updateShipping } from "@orderBlankSign/redux/reducer/cart/updateShipping.case.ts";
export { default as updateDeliveryMethod } from "@orderBlankSign/redux/reducer/cart/updateDeliveryMethod.case.ts";
export { default as updateBlindShipping } from "@orderBlankSign/redux/reducer/cart/updateBlindShipping.case.ts";

import { DeliveryMethod } from "@orderBlankSign/redux/reducer/cart/interface";
import CartState from "@orderBlankSign/redux/reducer/cart/interface";

const initialState: CartState = {
    subTotalAmount: 0,
    totalAmount: 0,
    totalShipping: 0,
    totalShippingDiscount: 0,
    totalQuantity: 0,
    items: {},
    shipping: {
        day: 0,
        date: "",
        amount: 0,
    },
    deliveryMethod: {
        key: DeliveryMethod.DELIVERY,
        label: "Delivery",
        type: "percentage",
        discount: 0
    },
    deliveryDate: {
        day: 0,
        isSaturday: false,
        free: false,
        date: "",
        discount: 0,
        timestamp: 0,
    },
    isBlindShipping: false,
    readyForCart: false,
    uploadedArtworks: [],
    additionalNote: '',
}

export default initialState;