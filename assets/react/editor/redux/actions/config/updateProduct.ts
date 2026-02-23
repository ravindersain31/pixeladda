import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateProduct = createAction('config/updateProduct', prepare);

export default updateProduct;
