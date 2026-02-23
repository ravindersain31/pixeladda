import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";

const updateBlindShipping: CaseReducer = (state: AppState, action) => {    
    const { editor, config } = state;
    editor.isBlindShipping = action.payload;
}

export default updateBlindShipping;