import { CaseReducer } from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";
import { updateItemsYSPLogoDiscount } from "@react/editor/helper/editor";

const updateYSPLogoDiscount: CaseReducer = (state: AppState, action) => {
    const items = updateItemsYSPLogoDiscount(state.editor.items);

    state.editor.items = items;
}

export default updateYSPLogoDiscount;