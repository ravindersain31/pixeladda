import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@orderSample/redux/reducer/interface.ts";
import { calculatePricing } from "@react/order-sample/utils/helper";

const updateSidesCase: CaseReducer = (state: AppState, action) => {
    state.cartStage.sides = action.payload;

    const addon = state.config.addons.sides[action.payload];
    const { items, subTotalAmount } = calculatePricing(state.cartStage.items, 'sides', addon, state.config.product);

    state.cartStage.items = items;

    state.cartStage.subTotalAmount = subTotalAmount;
    state.cartStage.totalAmount = subTotalAmount + state.cartStage.totalShipping
}

export default updateSidesCase;