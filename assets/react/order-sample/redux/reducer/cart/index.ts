export { default as initialize } from "@orderSample/redux/reducer/cart/initialize.case.ts";
export { default as upsertCartItem } from "@orderSample/redux/reducer/cart/upsertCartItem.case.ts";
export { default as updateComment } from "@orderSample/redux/reducer/cart/updateComment.case.ts";
export { default as updateShipping } from "@orderSample/redux/reducer/cart/updateShipping.case.ts";
export { default as updateDeliveryMethod } from "@orderSample/redux/reducer/cart/updateDeliveryMethod.case.ts";
export { default as updateBlindShipping } from "@orderSample/redux/reducer/cart/updateBlindShipping.case.ts";

export { default as updateSides } from "@orderSample/redux/reducer/cart/updateSides.case.ts";
export { default as updateShape } from "@orderSample/redux/reducer/cart/updateShape.case.ts";

import { DeliveryMethod, IFrame, IGrommetColor, IGrommets, IImprintColor, IShape, ISides } from "@orderSample/redux/reducer/cart/interface";
import CartState from "@orderSample/redux/reducer/cart/interface";

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
    sides: ISides.SINGLE,
    imprintColor: IImprintColor.UNLIMITED,
    grommets: IGrommets.NONE,
    grommetColor: IGrommetColor.SILVER,
    frame: IFrame.NONE,
    shape: IShape.SQUARE,
    isBlindShipping: false,
    readyForCart: false,
    uploadedArtworks: [],
    additionalNote: '',
}

export default initialState;