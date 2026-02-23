import {createAction} from "@reduxjs/toolkit";

const prepare = (data: any) => {
    return {
        payload: data
    }
};

const updateUploadedArtworks = createAction('editor/updateUploadedArtworks', prepare);

export default updateUploadedArtworks;