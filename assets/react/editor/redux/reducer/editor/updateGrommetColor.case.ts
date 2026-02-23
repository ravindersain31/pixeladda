import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";
import {calculatePricing} from "@react/editor/helper/pricing.ts";

const updateGrommetColorCase: CaseReducer = (state: AppState, action) => {
    state.editor.grommetColor = action.payload;

    const addon = state.config.addons.grommetColor[action.payload];
    const {items, subTotalAmount} = calculatePricing(state.editor.items, 'grommetColor', addon, state.config.product);
    state.editor.items = items;

    state.editor.subTotalAmount = subTotalAmount;
    state.editor.totalAmount = subTotalAmount + state.editor.totalShipping
}

export default updateGrommetColorCase;