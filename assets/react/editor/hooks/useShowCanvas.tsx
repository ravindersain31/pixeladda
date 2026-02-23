import { useContext } from "react";
import fabric from "@react/editor/canvas/fabric";
import store from "../redux/store";
import CanvasContext from "../context/canvas";
import { PreviewType } from "../redux/reducer/editor/interface";

const useShowCanvas = (): boolean => {
    const canvasContext = useContext(CanvasContext);

    const shouldShowCanvas = (canvasData: fabric.Canvas): boolean => {
        const state = store.getState();
        const { config, editor, canvas } = state;

        const isCustomProduct: boolean = config.product.isCustom;
        const canvasHasArtwork: boolean = !!canvasData?.getObjects && canvasData.getObjects().some(
            (object) => object.custom?.type === "custom-design"
        );
        const previewType: PreviewType | undefined = editor.items?.[canvas.item.id]?.previewType;

        if (isCustomProduct) {
            return previewType === PreviewType.CANVAS && canvasHasArtwork;
        } else {
            return previewType === PreviewType.CANVAS;
        }
    };

    return shouldShowCanvas(canvasContext.canvas);
};

export default useShowCanvas;
