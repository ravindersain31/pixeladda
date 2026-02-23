import { CaseReducer } from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";

const updateCustomOriginalArtworkCase: CaseReducer<AppState> = (state, action) => {
    const currentSide: 'front' | 'back' = action.payload.side ? action.payload.side : state.canvas.view;

    const itemId = state.canvas.item.id;

    let artwork = state.editor.items[itemId].customOriginalArtwork;

    if (
        !artwork ||
        typeof artwork !== "object" ||
        Array.isArray(artwork) ||
        !("front" in artwork) ||
        !("back" in artwork)
    ) {
        state.editor.items[itemId].customOriginalArtwork = { front: [], back: [] };
    }

    state.editor.items[itemId].customOriginalArtwork[currentSide] = action.payload.data;
};

export default updateCustomOriginalArtworkCase;
