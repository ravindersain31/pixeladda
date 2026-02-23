import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateActiveObject = createAction('canvas/updateActiveObject', prepare);

export default updateActiveObject;