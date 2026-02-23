import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";
import { ItemProps } from "./interface";
import { updateItemsFreeFreight } from "@react/editor/helper/editor";

const updateFreeFreight: CaseReducer = (state: AppState, action) => {    
    const { editor, config } = state;
    editor.isFreeFreight = action.payload;
    state.editor.items = updateItemsFreeFreight(editor.items, action.payload);
}

export default updateFreeFreight;