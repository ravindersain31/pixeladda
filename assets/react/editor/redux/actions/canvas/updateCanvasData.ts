import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any, side?: string) => {
    return {
        payload: {
            data: data,
            side: side
        }
    }
};

const updateCanvasData = createAction('canvas/updateCanvasData', prepare);

export default updateCanvasData;