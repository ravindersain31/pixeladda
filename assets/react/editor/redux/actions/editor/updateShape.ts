import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateShape = createAction("editor/updateShape", prepare);

export default updateShape;