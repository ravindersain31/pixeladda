import { createAction } from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateDeliveryMethod = createAction('cart/updateDeliveryMethod', prepare);

export default updateDeliveryMethod;