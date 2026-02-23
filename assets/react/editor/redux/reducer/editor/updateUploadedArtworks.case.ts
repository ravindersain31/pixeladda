import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";

const updateUploadedArtworksCase: CaseReducer = (state: AppState, action) => {
    state.editor.uploadedArtworks = action.payload;
}

export default updateUploadedArtworksCase;