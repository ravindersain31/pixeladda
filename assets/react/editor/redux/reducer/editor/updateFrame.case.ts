import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";
import {calculatePricing} from "@react/editor/helper/pricing.ts";
import { updateFramePriceAccordingToShape } from "@react/editor/helper/template";

const updateFrame: CaseReducer = (state: AppState, action) => {
    const frameTypes = action.payload;
    state.editor.frame = frameTypes;
    let matchingAddons: any;

    if (Array.isArray(frameTypes)) {
        matchingAddons = [];
        frameTypes.forEach((frameType) => {
            const addon = state.config.addons.frame[frameType];
            if (addon) {
                matchingAddons.push(addon);
            }
        });
    } else {
        matchingAddons = state.config.addons.frame[frameTypes];
    }
    const {items, subTotalAmount} = calculatePricing(state.editor.items, 'frame', matchingAddons, state.config.product);
    state.editor.items = items;
    state.editor.items = updateFramePriceAccordingToShape(state, state.editor.items);

    state.editor.subTotalAmount = subTotalAmount;
    state.editor.totalAmount = subTotalAmount + state.editor.totalShipping
}

export default updateFrame;