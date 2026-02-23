import {createReducer} from "@reduxjs/toolkit";
import actions from "@wireStake/redux/actions";

import configInitialState from "./config";
import cartInitialState from "./cart";

import * as configCase from "./config";
import * as cartCase from "./cart";

const initialState = {
    config: configInitialState,
    cartStage: cartInitialState
}

const reducer = createReducer(initialState, (builder) => {
    // Config cases
    builder.addCase(actions.config.initialize, configCase.initialize);
    builder.addCase(actions.config.updateProduct, configCase.updateProduct);

    // Cart cases
    builder.addCase(actions.cartStage.initialize, cartCase.initialize);
    builder.addCase(actions.cartStage.upsertCartItem, cartCase.upsertCartItem);
    builder.addCase(actions.cartStage.updateComment, cartCase.updateComment);
    builder.addCase(actions.cartStage.updateShipping, cartCase.updateShipping);
    builder.addCase(actions.cartStage.updateDeliveryMethod, cartCase.updateDeliveryMethod);
    builder.addCase(actions.cartStage.updateBlindShipping, cartCase.updateBlindShipping);

});

export default reducer;