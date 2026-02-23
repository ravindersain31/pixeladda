import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const upsertCartItem = createAction('cart/upsertCartItem', prepare);

export default upsertCartItem;