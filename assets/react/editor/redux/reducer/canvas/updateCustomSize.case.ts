import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";
import {getClosestVariantFromPricing} from "@react/editor/helper/size-calc.ts";

const updateCustomSize: CaseReducer = (state: AppState, action) => {
    const {editor, canvas, config} = state;
    canvas.customSize.templateSize = action.payload.templateSize;
    canvas.customSize.closestVariant = getClosestVariantFromPricing(
        action.payload.templateSize,
        config.product.pricing
    );
}

export default updateCustomSize;