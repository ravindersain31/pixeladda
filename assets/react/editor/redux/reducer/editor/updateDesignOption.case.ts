import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";
import { updateItemsDesignOption } from "@react/editor/helper/canvas";

const updateDesignOption: CaseReducer = (state: AppState, action) => {
    state.editor.items[state.canvas.item.id].isHelpWithArtwork = action.payload.isHelpWithArtwork;
    state.editor.items[state.canvas.item.id].isEmailArtworkLater = action.payload.isEmailArtworkLater;

    state.editor.isHelpWithArtwork = action.payload.isHelpWithArtwork;
    state.editor.isEmailArtworkLater = action.payload.isEmailArtworkLater;

    state.editor.items = updateItemsDesignOption(
      state.editor.items,
      action.payload.isHelpWithArtwork,
      action.payload.isEmailArtworkLater
    );
}

export default updateDesignOption;