import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateQty = createAction('editor/updateQty', prepare);

export default updateQty;
