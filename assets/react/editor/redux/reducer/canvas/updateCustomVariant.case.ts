import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";
import { CanvasDataProps } from "./interface";
import { getClosestVariantCanvasData } from "@react/editor/helper/template";

const updateCustomVariant: CaseReducer = (state: AppState, action) => {
    const {editor, canvas, config} = state;

    if(canvas.item.isCustomSize){
        const data = getClosestVariantCanvasData(canvas.templateSize, state.config.product.variants);
        const canvasData = config.product.customVariant[0].canvasData;
        const canvasDataJson: CanvasDataProps = {
            front: data || null,
            back: data || null
        }

        state.canvas.item.canvasData = canvasData || canvasDataJson;
        state.canvas.data = canvasData || canvasDataJson;
    }
}

export default updateCustomVariant;