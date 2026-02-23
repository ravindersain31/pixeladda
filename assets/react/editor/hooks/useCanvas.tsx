import {useContext, useCallback} from "react";
import {calculateCanvasDimensions, fitObjectsToCanvas} from "@react/editor/canvas";
import CanvasContext from "@react/editor/context/canvas.ts";
import FontFaceObserver from "fontfaceobserver";
import fabric from "@react/cart/fabric.ts";
import { CanvasProperties, lockAttrs } from "@react/editor/canvas/utils";
import { initCenteringGuidelines } from "../plugin/centering_guidelines";
import { initAligningGuidelines } from "../plugin/aligning_guidelines";
import { preloadFonts } from "@react/editor/canvas/utils";

const useCanvas = () => {

    const canvasContext = useContext(CanvasContext);

    const autoResizeCanvas = useCallback((templateSize: any, fitContents: boolean = false) => {
        const canvas = canvasContext.canvas;
        const dimensions = calculateCanvasDimensions(templateSize);
        canvas.setDimensions(dimensions);
        if (fitContents) {
            fitObjectsToCanvas(canvas);
        }
    }, []);

    const loadFromJSON = useCallback(async (json: any, templateSize: any, cb: (data: string | object) => void) => {
        const canvas = canvasContext.canvas;
        autoResizeCanvas(templateSize);
        
        if (json.objects) {
            await preloadFonts(json.objects);
        }

        canvas.loadFromJSON(json, () => {
            if (!canvas.backgroundColor) {
                canvas.backgroundColor = '#FFF'; // Set your desired default background color here
            }

            const objects = canvas.getObjects();
            const isAllLocked = objects.every(object => object.hasControls);
            canvas.selection = isAllLocked;

            objects.forEach((obj: any) => {
                lockAttrs.forEach((attr) => {
                    obj[attr] = obj[attr] ?? true;
                });
                if (obj.hasControls || obj.hasControls === undefined) {
                    obj.selectable = true;
                }
            });

            fitObjectsToCanvas(canvas);
            canvas.requestRenderAll();
            cb(canvas.toJSON(CanvasProperties));
            initCenteringGuidelines(canvas);
            initAligningGuidelines(canvas);
        }, (o: any) => {
            if (o.type === 'text') {
                o = {
                    ...o,
                    // type: 'i-text',
                    text: o.text.trim(),
                }
            }
        });
    }, []);

    return {
        loadFromJSON,
        autoResizeCanvas
    }
};

export default useCanvas;