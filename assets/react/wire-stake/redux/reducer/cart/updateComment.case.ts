import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@wireStake/redux/reducer/interface.ts";
import { ItemProps } from "./interface";

const updateComment: CaseReducer = (state: AppState, action) => {

    const comment = action.payload || '';

    state.cartStage.additionalNote = comment;

    let items: { [key: string]: ItemProps } = JSON.parse(JSON.stringify(state.cartStage.items));

    for (const key in items) {
        items[key].additionalNote = comment;
    }

    state.cartStage.items = items;

}

export default updateComment;