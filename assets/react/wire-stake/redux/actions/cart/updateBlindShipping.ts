import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateBlindShipping = createAction("cart/updateBlindShipping", prepare);

export default updateBlindShipping;