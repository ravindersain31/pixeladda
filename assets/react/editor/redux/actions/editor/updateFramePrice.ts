import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateFramePrice = createAction("editor/updateFramePrice", prepare);

export default updateFramePrice;