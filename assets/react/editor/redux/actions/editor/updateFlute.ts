import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateFlute = createAction('editor/updateFlute', prepare);

export default updateFlute;