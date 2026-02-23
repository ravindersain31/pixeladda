import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";
import {identifyImprintColor, syncArtworkWithCanvasData} from "@react/editor/helper/canvas.ts";
import {calculatePricing} from "@react/editor/helper/pricing.ts";
import {Sides} from "@react/editor/redux/reducer/editor/interface.ts";

const updateCanvasData: CaseReducer = (state: AppState, action) => {
    const side = (action.payload.side ? action.payload.side : state.canvas.view) as 'front' | 'back';

    const {objects = []} = action.payload.data;
    state.canvas.data = {
        ...state.canvas.data,
        [side]: objects.length > 0 ? action.payload.data : null,
    };

    if (state.editor.sides === Sides.DOUBLE && state.canvas.data.back === null && !state.config.product.isCustom) {
        state.canvas.data.back = state.canvas.data.front;
    }

    if (state.editor.sides === Sides.DOUBLE && state.canvas.data.front === null && !state.config.product.isCustom) {
        state.canvas.data.front = state.canvas.data.back;
    }

    const item = state.editor.items[state.canvas.item.id];
    if (item) {
        syncArtworkWithCanvasData(item, side, state.canvas.data[side]);
        if (state.editor.sides === Sides.DOUBLE && (side === 'front' || side === 'back')) {
             const otherSide = side === 'front' ? 'back' : 'front';
             syncArtworkWithCanvasData(item, otherSide, state.canvas.data[otherSide]);
        }
    }


    // state.editor.imprintColor = identifyImprintColor(state.canvas.data);
    // const addon = state.config.addons.imprintColor[state.editor.imprintColor];
    // const {items, subTotalAmount} = calculatePricing(state.editor.items, 'imprintColor', addon);
    // state.editor.items = items;
    // state.editor.subTotalAmount = subTotalAmount;
    // state.editor.totalAmount = subTotalAmount + state.editor.totalShipping
};

export default updateCanvasData;