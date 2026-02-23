import {useContext, useCallback} from "react";
import {calculateCanvasDimensions, fitObjectsToCanvas} from "@react/admin/editor/canvas";
import CanvasContext from "@react/admin/editor/context/canvas.ts";
import FontFaceObserver from "fontfaceobserver";
import { CanvasProperties, lockAttrs } from "../canvas/utils";

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

    const getTextWidth = (text: string, fontSize: string, fontFamily: string) => {
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');
        if (context) {
            context.font = fontSize + 'px ' + fontFamily;
            return context.measureText(text).width;
        }
        return 0;
    }

    const preloadFonts = async (objects: any[]) => {
        if (!Array.isArray(objects)) return;
        await Promise.all(objects.map(async (obj: any) => {
            if (['text', 'i-text'].includes(obj.type)) {
                const fontSpec = `${obj.fontSize}px "${obj.fontFamily}"`;
                try {
                    await document.fonts.load(fontSpec);
                } catch (e) {
                    console.warn("Font preload failed", fontSpec);
                }
            }
        }));
    }

    const loadFromJSON = useCallback(async (json: any, templateSize: any, cb: (data: string | object) => void) => {
        const canvas = canvasContext.canvas;
        autoResizeCanvas(templateSize);

        if (json.objects) {
            await preloadFonts(json.objects);
        }

        canvas.loadFromJSON(json, () => {
            if (!canvas.backgroundColor) {
                // canvas.backgroundColor = '#FFF'; // Set your desired default background color here
            }

            const objects = canvas.getObjects();
            objects.forEach((obj: any) => {
                lockAttrs.forEach((attr) => {
                    obj[attr] = obj[attr] ?? true;
                });
                if(obj.hasControls || obj.hasControls === undefined) {
                    obj.selectable = true;
                }
            });

            fitObjectsToCanvas(canvas);
            canvas.requestRenderAll();
            cb(canvas.toJSON(CanvasProperties));

        }, (o: any) => {
            if (o.type === 'text') {
                o = {
                    ...o,
                    type: 'i-text',
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