import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateFrame = createAction('editor/updateFrame', prepare);

export default updateFrame;