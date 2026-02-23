import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";

const updateViewCase: CaseReducer = (state: AppState, action) => {
    const otherView = action.payload.view === 'front' ? 'back' : 'front';
    if (action.payload.canvasData) {
        state.canvas.data[otherView] = action.payload.canvasData;
    }
    state.canvas.view = action.payload.view;

    if (state.canvas.view === 'back' && state.canvas.data.back === null) {
        const variant = state.config.product.variants.find((variant) => variant.productId === state.canvas.item.productId);
        state.canvas.data.back = variant?.templateJson || null;
    }
}

export default updateViewCase;