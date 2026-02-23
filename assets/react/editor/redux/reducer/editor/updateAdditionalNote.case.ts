import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";

const updateAdditionalNote: CaseReducer = (state: AppState, action) => {
    state.editor.items[state.canvas.item.id].additionalNote = action.payload

    const product = state.config.product;
    if(typeof state.storage.products[product.sku] === 'undefined') {
        state.storage.products[product.sku] = product;
    }
    state.storage.products[product.sku] = {
        ...state.storage.products[product.sku],
        variants: product.variants.map((variant) => {
            if (variant.name === state.canvas.item.name) {
                variant.additionalNote = action.payload;
            }
            return variant;
        }),
    };
}

export default updateAdditionalNote;