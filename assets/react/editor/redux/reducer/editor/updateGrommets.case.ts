import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";
import {calculatePricing} from "@react/editor/helper/pricing.ts";
import {GrommetColor, Grommets} from "@react/editor/redux/interface.ts";

const updateGrommetsCase: CaseReducer = (state: AppState, action) => {
    state.editor.grommets = action.payload;

    const addon = state.config.addons.grommets[action.payload];
    let {items, subTotalAmount} = calculatePricing(state.editor.items, 'grommets', addon, state.config.product);

    if(action.payload === Grommets.NONE) {
        const addon = state.config.addons.grommetColor[GrommetColor.SILVER];
        const {items: _item, subTotalAmount: _subTotalAmount} = calculatePricing(state.editor.items, 'grommetColor', addon, state.config.product);
        items = _item;
        subTotalAmount = _subTotalAmount;
    }

    state.editor.items = items;

    state.editor.subTotalAmount = subTotalAmount;
    state.editor.totalAmount = subTotalAmount + state.editor.totalShipping
}

export default updateGrommetsCase;