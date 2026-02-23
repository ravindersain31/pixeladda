import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";
import {getDiscountedPrice, getShippingRateByDayNumber} from "@react/editor/helper/shipping.ts";

const updateShippingCase: CaseReducer = (state: AppState, action) => {
    state.editor.deliveryDate = action.payload;

    const totalAmount = state.editor.totalAmount;
    const totalShipping = state.editor.totalShipping;
    const subTotalAmount = state.editor.subTotalAmount;
    const totalQuantity = state.editor.totalQuantity;

    const updatedTotalShippingDiscount = getDiscountedPrice(state.editor.subTotalAmount, state.editor.deliveryDate.discount);
    state.editor.totalShippingDiscount = updatedTotalShippingDiscount;

    const updatedShippingAmount = getShippingRateByDayNumber(action.payload.day, state.config.product.shipping, state);

    state.editor.totalAmount = totalQuantity <= 0 ? 0 : (totalAmount - totalShipping) + updatedShippingAmount;
    state.editor.totalShipping = updatedShippingAmount;
    state.editor.shipping = {
        amount: updatedShippingAmount,
        date: action.payload.date,
        day: action.payload.day,
    }
}

export default updateShippingCase;