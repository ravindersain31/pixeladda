import { createReducer } from "@reduxjs/toolkit";
import configInitialState from "./config";
import editorInitialState from "./editor";
import canvasInitialState from "./canvas";
import storageInitialState from "./storage";
import actions from "@react/editor/redux/actions";
import * as configCase from "./config";
import * as editorCase from "./editor";
import * as canvasCase from "./canvas";
import * as storageCase from "./storage";

const initialState = {
    config: configInitialState,
    editor: editorInitialState,
    canvas: canvasInitialState,
    storage: storageInitialState
}

const reducer = createReducer(initialState, (builder) => {
    // Config cases
    builder.addCase(actions.config.initialize, configCase.initialize);
    builder.addCase(actions.config.updateProduct, configCase.updateProduct);

    // Editor cases
    builder.addCase(actions.editor.initialize, editorCase.initialize);
    builder.addCase(actions.editor.updateGrommets, editorCase.updateGrommets);
    builder.addCase(actions.editor.updateGrommetColor, editorCase.updateGrommetColor);
    builder.addCase(actions.editor.updateImprintColor, editorCase.updateImprintColor);
    builder.addCase(actions.editor.updateQty, editorCase.updateQty);
    builder.addCase(actions.editor.updateShipping, editorCase.updateShipping);
    builder.addCase(actions.editor.refreshShipping, editorCase.refreshShipping);
    builder.addCase(actions.editor.refreshPricing, editorCase.refreshPricing);
    builder.addCase(actions.editor.updateSides, editorCase.updateSides);
    builder.addCase(actions.editor.updateAdditionalNote, editorCase.updateAdditionalNote);
    builder.addCase(actions.editor.updateDesignOption, editorCase.updateDesignOption);
    builder.addCase(actions.editor.prepareCartData, editorCase.prepareCartData);
    builder.addCase(actions.editor.updateFrame, editorCase.updateFrame);
    builder.addCase(actions.editor.updateFramePrice, editorCase.updateFramePrice);
    builder.addCase(actions.editor.clearItems, editorCase.clearItems);
    builder.addCase(actions.editor.updateUploadedArtworks, editorCase.updateUploadedArtworks);
    builder.addCase(actions.editor.updateShape, editorCase.updateShape);
    builder.addCase(actions.editor.updateDeliveryMethod, editorCase.updateDeliveryMethod);
    builder.addCase(actions.editor.updateBlindShipping, editorCase.updateBlindShipping);
    builder.addCase(actions.editor.updateFreeFreight, editorCase.updateFreeFreight);
    builder.addCase(actions.editor.updateYSPLogoDiscount, editorCase.updateYSPLogoDiscount);
    builder.addCase(actions.editor.updateCustomArtwork, editorCase.updateCustomArtwork);
    builder.addCase(actions.editor.updateCustomOriginalArtwork, editorCase.updateCustomOriginalArtwork);
    builder.addCase(actions.editor.updatePrePackedDiscount, editorCase.updatePrePackedDiscount);
    builder.addCase(actions.editor.updateNotes, editorCase.updateNotes);
    builder.addCase(actions.editor.updateFlute, editorCase.updateFlute);

    // Canvas cases
    builder.addCase(actions.canvas.updateVariant, canvasCase.updateVariant);
    builder.addCase(actions.canvas.updateCustomVariant, canvasCase.updateCustomVariant);
    builder.addCase(actions.canvas.updateCanvasData, canvasCase.updateCanvasData);
    builder.addCase(actions.canvas.updateView, canvasCase.updateView);
    builder.addCase(actions.canvas.updateActiveObject, canvasCase.updateActiveObject);
    builder.addCase(actions.canvas.updateCanvasLoader, canvasCase.updateCanvasLoader);
    builder.addCase(actions.canvas.changeUpdateCount, canvasCase.changeUpdateCount);
    builder.addCase(actions.canvas.changeLoaderCount, canvasCase.changeLoaderCount);
    builder.addCase(actions.canvas.updateCustomSize, canvasCase.updateCustomSize);

    // Storage cases
    builder.addCase(actions.storage.saveProduct, storageCase.saveProduct);
});

export default reducer;