import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";
import {getDiscountedPrice, getShippingRateByDayNumber} from "@react/editor/helper/shipping.ts";

const refreshShippingCase: CaseReducer = (state: AppState, action) => {
    const editor = state.editor;

    const totalAmount = editor.totalAmount;
    const totalShipping = editor.totalShipping;
    const totalQuantity = state.editor.totalQuantity;
    const subTotalAmount = state.editor.subTotalAmount;

    const updatedTotalShippingDiscount = getDiscountedPrice(state.editor.subTotalAmount, state.editor.deliveryDate.discount);
    state.editor.totalShippingDiscount = updatedTotalShippingDiscount;

    const updatedShippingAmount = getShippingRateByDayNumber(editor.deliveryDate.day, state.config.product.shipping, state);

    state.editor.totalAmount = totalQuantity <= 0 ? 0 : (totalAmount - totalShipping) + updatedShippingAmount;
    state.editor.totalShipping = updatedShippingAmount;
    state.editor.shipping = {
        amount: updatedShippingAmount,
        date: editor.shipping.date,
        day: editor.shipping.day,
    };
}

export default refreshShippingCase;