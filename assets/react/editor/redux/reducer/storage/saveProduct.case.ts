import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";

const storageCase: CaseReducer = (state: AppState, action) => {
    const {product, currentItem, canvasData} = action.payload;

    if (typeof state.storage.products[product.sku] === 'undefined') {
        state.storage.products[product.sku] = product;
    }

    state.storage.products[product.sku] = {
        ...state.storage.products[product.sku],
        variants: JSON.parse(JSON.stringify(product.variants)).map((variant: any) => {
            if (variant.name === currentItem.name) {
                variant.canvasData = canvasData;
            }
            return variant;
        }),
        customVariant: JSON.parse(JSON.stringify(product.customVariant)).map((variant: any) => {
            variant.canvasData = canvasData;
            return variant;
        }),
    };
}

export default storageCase;