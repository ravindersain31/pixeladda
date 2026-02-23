import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";


const changeLoaderCount: CaseReducer = (state: AppState, action) => {
    if (action.payload.count !== undefined) {
        state.canvas.loaderCount = action.payload.count;
    } else {
        state.canvas.loaderCount += 1;
    }
}

export default changeLoaderCount;