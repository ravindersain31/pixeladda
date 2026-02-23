import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateFreeFreight = createAction("editor/updateFreeFreight", prepare);

export default updateFreeFreight;