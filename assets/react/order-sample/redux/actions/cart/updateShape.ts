import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateShape = createAction("cart/updateShape", prepare);

export default updateShape;