import fabric from "../fabric.ts";
import { useEffect, useLayoutEffect, useState } from "react";
import fitObjectsToCanvas from "@react/editor/canvas/fitObjectsToCanvas.ts";
import { calculateCanvasDimensions } from "../utils.ts";
import { PreviewContainer } from "./styled.tsx";
import FontFaceObserver from "fontfaceobserver";
import React from "react";
import { preloadFonts } from "@react/editor/canvas/utils.ts";

interface PreviewProps {
    itemId: string;
    item: any;
    side: string;
    canvasData: any;
    templateSize: {
        width: number;
        height: number;
    }
}

const Preview = ({ itemId, templateSize, canvasData, item, side }: PreviewProps) => {
    const [canvas, setCanvas] = useState<fabric.Canvas | null>(null);
    const [isCanvasReady, setIsCanvasReady] = useState<boolean>(false);
    const hasObjects = canvasData?.objects && canvasData.objects.length > 0 && canvasData.objects.some((obj: any) => {
        if (obj?.fill === "transparent") {
            return false;
        }
        return true;
    });

    useEffect(() => {
        if (!hasObjects) return;

        const newCanvas = new fabric.Canvas(`canvas_preview_${itemId}_${side}`, {
            width: 300,
            height: 300,
            perPixelTargetFind: true,
            allowTouchScrolling: true,
        });

        setCanvas(newCanvas);
        setIsCanvasReady(true);

        return () => {
            newCanvas.dispose();
        };
    }, [hasObjects]);

    useLayoutEffect(() => {
        if (canvas && hasObjects && isCanvasReady) {
            // Use requestAnimationFrame to ensure DOM is ready
            requestAnimationFrame(async () => {
                autoResizeCanvas(templateSize, true);

                if (canvasData.objects) {
                    await preloadFonts(canvasData.objects);
                }

                canvas.loadFromJSON(
                    canvasData,
                    () => {
                        const objects = canvas.getObjects();
                        objects.forEach((obj: any) => {
                            obj.selection = false;
                            obj.selectable = false;
                        });

                        canvas.renderAll();
                        setTimeout(() => {
                            fitObjectsToCanvas(canvas);
                            canvas.requestRenderAll();
                        }, 50);
                    },
                    (o: any) => {
                        o.selection = false;
                        o.selectable = false;
                        if (o.type === 'text') {
                            o = {
                                ...o,
                                text: o.text.trim(),
                            }
                        }
                        return o;
                    }
                );
            });
        }
    }, [canvas, isCanvasReady, canvasData, hasObjects]);

    const autoResizeCanvas = (templateSize: any, fitContents: boolean = false) => {
        if (canvas) {
            const dimensions = calculateCanvasDimensions(canvas.getElement(), templateSize);
            canvas.setDimensions(dimensions);
            if (fitContents) {
                fitObjectsToCanvas(canvas);
            }
        }
    };

    const getTextWidth = (text: string, fontSize: string, fontFamily: string): number => {
        const canvasEl = document.createElement('canvas');
        const context = canvasEl.getContext('2d');
        if (context) {
            context.font = fontSize + 'px ' + fontFamily;
            return context.measureText(text).width;
        }
        return 0;
    };

    return (
        <PreviewContainer>
            {hasObjects ? (
                <canvas id={`canvas_preview_${itemId}_${side}`} />
            ) : (
                <img
                    src={item.image}
                    alt="Preview"
                    style={{ width: "100%", height: "auto" }}
                />
            )}
        </PreviewContainer>
    );
};

export default Preview;