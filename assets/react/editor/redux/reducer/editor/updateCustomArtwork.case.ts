import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";

const updateCustomArtworkCase: CaseReducer = (state: AppState, action) => {
    const side = action.payload.side ? action.payload.side : state.canvas.view;
    const type = action.payload.type;

    if (!state.editor.items[state.canvas.item.id].customArtwork[type]) {
        state.editor.items[state.canvas.item.id].customArtwork = { [type] : {front: [], back: []} };
    }
        //@ts-ignore
        state.editor.items[state.canvas.item.id].customArtwork[type][side] = action.payload.data;

    const product = state.config.product;
    if(typeof state.storage.products[product.sku] === 'undefined') {
        state.storage.products[product.sku] = product;
    }
    state.storage.products[product.sku] = {
        ...state.storage.products[product.sku],
        variants: product.variants.map((variant) => {
            if (variant.name === state.canvas.item.name) {
                //@ts-ignore
                variant.customArtwork = state.editor.items[state.canvas.item.id].customArtwork;
                variant.canvasData = state.canvas.data;
            }
            return variant;
        }),
    };
};

export default updateCustomArtworkCase;