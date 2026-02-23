import {createAction} from "@reduxjs/toolkit";

const prepare = (data?: any) => {
    return {
        payload: data
    }
};

const updateCustomSize = createAction("canvas/updateCustomSize", prepare);

export default updateCustomSize;