import fabric from "./fabric.ts";
import {centerActiveObject} from "@react/admin/editor/canvas/index.ts";

/**
 * It will transform the objects to fit the canvas size
 * @param canvas
 */
const fitObjectsToCanvas = (canvas: fabric.Canvas) => {
    const group = new fabric.Group(canvas.getObjects());
    const groupWidth = group.width || canvas.getWidth();
    const groupHeight = group.height || canvas.getHeight();

    const scalingMargin = 0;
    const scaleFactorX = canvas.getWidth() / (groupWidth + scalingMargin);
    const scaleFactorY = canvas.getHeight() / (groupHeight + scalingMargin);


    group.scaleX = scaleFactorX;
    group.scaleY = scaleFactorY;
    canvas.setActiveObject(group);
    centerActiveObject(canvas);
    canvas.discardActiveObject();
    group.destroy();
    canvas.requestRenderAll();
};

export default fitObjectsToCanvas;