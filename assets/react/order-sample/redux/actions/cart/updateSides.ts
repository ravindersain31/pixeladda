import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateSides = createAction('cart/updateSides', prepare);

export default updateSides;