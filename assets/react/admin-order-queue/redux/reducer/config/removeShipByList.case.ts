import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/admin-order-queue/redux/reducer/interface.ts";

const removeShipByList: CaseReducer = (state: AppState, action) => {

    const { id, isDeleted } = action.payload;

    if (isDeleted) {
        state.config.lists = state.config.lists.filter(
            (list) => list.id !== id
        );
    }

};

export default removeShipByList;