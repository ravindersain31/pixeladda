import { createAction } from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateShipping = createAction('cart/updateShipping', prepare);

export default updateShipping;