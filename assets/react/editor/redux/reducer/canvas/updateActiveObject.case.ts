import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";

const updateActiveObject: CaseReducer = (state: AppState, action) => {
    state.canvas.activeObject = action.payload;
}

export default updateActiveObject;