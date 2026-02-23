import {createAction} from "@reduxjs/toolkit";

const prepare = (view: string, canvasData?: string | null | object) => {
    return {
        payload: {
            view,
            canvasData,
        }
    }
};

const updateView = createAction('canvas/updateView', prepare);

export default updateView;