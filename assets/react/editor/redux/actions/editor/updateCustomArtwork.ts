import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any, side: string, type: string) => {
    return {
        payload: {
            data: data,
            side: side,
            type: type,
        }
    }
};

const updateCustomArtwork = createAction("editor/updateCustomArtwork", prepare);

export default updateCustomArtwork;