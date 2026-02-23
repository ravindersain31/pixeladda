import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateShipping = createAction('editor/updateShipping', prepare);

export default updateShipping;