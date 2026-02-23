import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";
import {calculatePricing} from "@react/editor/helper/pricing.ts";
import {GrommetColor, Grommets} from "@react/editor/redux/interface.ts";
import { updateFramePriceAccordingToShape } from "@react/editor/helper/template";

const updateShape: CaseReducer = (state: AppState, action) => {
    state.editor.shape = action.payload;

    const addon = state.config.addons.shape[action.payload];
    state.editor.items = updateFramePriceAccordingToShape(state, state.editor.items);
    const {items, subTotalAmount} = calculatePricing(state.editor.items, 'shape', addon, state.config.product);
    state.editor.items = items;

    state.editor.subTotalAmount = subTotalAmount;
    state.editor.totalAmount = subTotalAmount + state.editor.totalShipping
}

export default updateShape;