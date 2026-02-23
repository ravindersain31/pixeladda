import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const initialize = createAction('cart/initialize', prepare);

export default initialize;
