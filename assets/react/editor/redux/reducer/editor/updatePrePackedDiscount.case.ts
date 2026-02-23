import { CaseReducer } from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";
import { updateItemsPrePackedDiscount } from "@react/editor/helper/pricing";

const updatePrePackedDiscount: CaseReducer = (state: AppState, action) => {
    const productType = state.config.product.productType;
    const { items } = updateItemsPrePackedDiscount(state.editor.items, productType);

    state.editor.items = items;
}

export default updatePrePackedDiscount;