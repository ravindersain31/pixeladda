import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";

const updateCanvasLoader: CaseReducer = (state: AppState, action) => {
    state.canvas.loading = action.payload;
}

export default updateCanvasLoader;