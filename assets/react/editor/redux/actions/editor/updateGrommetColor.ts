import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateGrommetColor = createAction('editor/updateGrommetColor', prepare);

export default updateGrommetColor;