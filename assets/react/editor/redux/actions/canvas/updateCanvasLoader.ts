import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateCanvasLoader = createAction('canvas/updateCanvasLoader', prepare);

export default updateCanvasLoader;