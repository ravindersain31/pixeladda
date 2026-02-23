import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";
import {calculatePricing} from "@react/editor/helper/pricing.ts";
import {CustomArtwork, Sides} from "@react/editor/redux/reducer/editor/interface.ts";
import {copyFrontToBackWhenEmpty} from "@react/editor/helper/canvas.ts";

const updateSidesCase: CaseReducer = (state: AppState, action) => {
    state.editor.sides = action.payload;

    if (state.editor.sides === Sides.SINGLE) {
        state.canvas.view = 'front';
        state.canvas.data.back = [];

        Object.values(state.editor.items).forEach((item: any) => {
            if (item.customArtwork?.[CustomArtwork.CUSTOM_DESIGN]) {
                item.customArtwork[CustomArtwork.CUSTOM_DESIGN].back = [];
            }
            if (item.customOriginalArtwork) {
                item.customOriginalArtwork.back = [];
            }
            item.canvasData.back = {};
        });
    }

    if (state.editor.sides === Sides.DOUBLE && !state.config.product.isCustom && state.canvas.data.back === null) {
        state.canvas.data.back = state.canvas.data.front;
    }

    const addon = state.config.addons.sides[action.payload];
    const {items, subTotalAmount} = calculatePricing(state.editor.items, 'sides', addon, state.config.product);

    state.editor.items = copyFrontToBackWhenEmpty(items, state.editor.sides);

    state.editor.subTotalAmount = subTotalAmount;
    state.editor.totalAmount = subTotalAmount + state.editor.totalShipping
}

export default updateSidesCase;