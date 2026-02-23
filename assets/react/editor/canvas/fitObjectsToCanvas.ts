import fabric from "./fabric.ts";
import {centerActiveObject} from "@react/editor/canvas/index.ts";

/**
 * It will transform the objects to fit the canvas size
 * @param canvas
 */
const fitObjectsToCanvas = (canvas: fabric.Canvas) => {
    const group = new fabric.Group(canvas.getObjects());
    const groupWidth = group.getScaledWidth() || canvas.getWidth();
    const groupHeight = group.getScaledHeight() || canvas.getHeight();

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

export const fitImageToCanvas = (image: fabric.Image, canvas: fabric.Canvas, oImg: HTMLImageElement) => {
    const scaleW = canvas.getWidth() / oImg.width;
    const scaleH = canvas.getHeight() / oImg.height;
    image.set({
        left: 0,
        top: 0,
    });
    image.scaleX = scaleW;
    image.scaleY = scaleH;
};

export const isObjectPartiallyOutsideCanvas = (
    obj: fabric.Object,
    canvasWidth: number,
    canvasHeight: number
) => {
    const rect = obj.getBoundingRect(true);
    return (
        rect.left < 0 ||
        rect.top < 0 ||
        rect.left + rect.width > canvasWidth ||
        rect.top + rect.height > canvasHeight
    );
};

const isObjectFullyOutsideCanvas = (obj: any, canvasWidth: number, canvasHeight: number) => {
    // Check if all corners of the object are outside the canvas boundaries
    return (
        obj.left + obj.width * obj.scaleX < 0 ||
        obj.top + obj.height * obj.scaleY < 0 ||
        obj.left > canvasWidth ||
        obj.top > canvasHeight
    );
};

export default fitObjectsToCanvas;