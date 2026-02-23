import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateVariant = createAction('canvas/updateVariant', prepare);

export default updateVariant;