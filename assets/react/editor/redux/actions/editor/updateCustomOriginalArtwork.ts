import { createAction } from "@reduxjs/toolkit";

const prepare = (data: any, side: string) => {
    return {
        payload: {
            data,
            side
        }
    };
};

const updateCustomOriginalArtwork = createAction("editor/updateCustomOriginalArtwork", prepare);

export default updateCustomOriginalArtwork;
