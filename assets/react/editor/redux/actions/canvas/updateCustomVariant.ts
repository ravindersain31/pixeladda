import {createAction} from "@reduxjs/toolkit";

const prepare = (data?: any) => {
    return {
        payload: data
    }
};

const updateCustomVariant = createAction("canvas/updateCustomVariant", prepare);

export default updateCustomVariant;