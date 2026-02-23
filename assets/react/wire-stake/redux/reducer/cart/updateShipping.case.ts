import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@wireStake/redux/reducer/interface.ts";
import { ItemProps } from "./interface";
import { getShippingRateByDayNumber } from "@wireStake/utils/helper";
import { getDiscountedPrice } from "@wireStake/utils/helper";

const updateShipping: CaseReducer = (state: AppState, action) => {

    const shipping = action.payload;

    const totalAmount = state.cartStage.totalAmount;
    const totalShipping = state.cartStage.totalShipping;
    const subTotalAmount = state.cartStage.subTotalAmount;
    const totalQuantity = state.cartStage.totalQuantity;

    state.cartStage.deliveryDate = shipping;

    const updatedTotalShippingDiscount = getDiscountedPrice(state.cartStage.subTotalAmount, state.cartStage.deliveryDate.discount);
    state.cartStage.totalShippingDiscount = updatedTotalShippingDiscount;

    const updatedShippingAmount = Number(getShippingRateByDayNumber(action.payload.day, state.config.product.shipping, state));

    // TODO: Need to check
    state.cartStage.totalAmount = totalQuantity <= 0 ? 0 : (totalAmount - totalShipping) + updatedShippingAmount;
    state.cartStage.totalShipping = Number(updatedShippingAmount);
    state.cartStage.shipping = {
        amount: Number(updatedShippingAmount),
        date: action.payload.date,
        day: action.payload.day,
    }
}

export default updateShipping;
