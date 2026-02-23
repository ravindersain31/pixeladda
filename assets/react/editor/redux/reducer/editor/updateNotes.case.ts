import { CaseReducer } from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";

const updateNotes: CaseReducer<AppState> = (state, action) => {
    const { type, subType, value, itemId } = action.payload;

    const item = state.editor.items[itemId];
    if (!item) return;

    if (!item.notes) item.notes = {};

    // If subType is provided, treat as nested object structure
    if (subType !== undefined) {
        if (!item.notes[type] || typeof item.notes[type] === 'string') {
            item.notes[type] = {};
        }
        (item.notes[type] as { [subType: string]: string })[subType] = value;

        if (!value || value.trim() === '') {
            delete (item.notes[type] as { [subType: string]: string })[subType];
        }

        if (Object.keys(item.notes[type] as object).length === 0) {
            delete item.notes[type];
        }
    } else {
        item.notes[type] = value;

        if (!value || value.trim() === '') {
            delete item.notes[type];
        }
    }

    if (Object.keys(item.notes).length === 0) {
        delete item.notes;
    }
};

export default updateNotes;