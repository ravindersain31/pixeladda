import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";
import {calculatePricing} from "@react/editor/helper/pricing.ts";

const updateImprintColorCase: CaseReducer = (state: AppState, action) => {
    state.editor.imprintColor = action.payload;

    const addon = state.config.addons.imprintColor[action.payload];

    const {items, subTotalAmount} = calculatePricing(state.editor.items, 'imprintColor', addon, state.config.product);
    state.editor.items = items;

    state.editor.subTotalAmount = subTotalAmount;
    state.editor.totalAmount = subTotalAmount + state.editor.totalShipping
}

export default updateImprintColorCase;