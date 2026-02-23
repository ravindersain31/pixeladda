import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";

const changeUpdateCount: CaseReducer = (state: AppState, action) => {
    state.canvas.updateCount += 1;
}

export default changeUpdateCount;