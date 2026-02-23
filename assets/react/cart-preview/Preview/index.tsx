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
    };
}

const Preview = ({ itemId, templateSize, canvasData, side, item }: PreviewProps) => {
    const [canvas, setCanvas] = useState<fabric.Canvas | null>(null);
    const [isCanvasReady, setIsCanvasReady] = useState<boolean>(false);
    const [isCustomProduct, setIsCustomProduct] = useState<boolean>(false);
    const hasObjects = canvasData?.objects && canvasData.objects.length > 0 && canvasData.objects.some((obj: any) => {
        if (obj?.fill === "transparent") {
            return false;
        }
        return true;
    });

    useEffect(() => {
        if (hasObjects) {
            const newCanvas = new fabric.Canvas(`canvas_preview_${itemId}_${side}`, {
                width: 500,
                height: 500,
                perPixelTargetFind: true,
                allowTouchScrolling: true,
                selection: false,
                interactive: false,
                backgroundColor: '#fff'
            });

            setCanvas(newCanvas);
            setIsCanvasReady(true);

            return () => {
                newCanvas.dispose();
            };
        } else {
            setIsCustomProduct(true);
        }
    }, [hasObjects]);

    const LoadCanvas = () => {
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
                            obj.evented = false;
                        });

                        // Re-render all objects with loaded fonts
                        canvas.renderAll();
                        // Give browser time to render with loaded fonts
                        setTimeout(() => {
                            fitObjectsToCanvas(canvas);
                            canvas.requestRenderAll();
                        }, 50);
                    },
                    (o: any) => {
                        o.selection = false;
                        o.selectable = false;
                        o.evented = false;
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
    };

    useLayoutEffect(() => {
        LoadCanvas();
    }, [isCanvasReady]);

    useEffect(() => {
        function handleModalShown() {
            if (canvas) {
                autoResizeCanvas(templateSize, true);
                canvas.requestRenderAll();
            }
        }

        const modalEl = document.getElementById("designPreviewModal");
        if (modalEl) {
            modalEl.addEventListener("shown.bs.modal", handleModalShown);
        }

        return () => {
            if (modalEl) {
                modalEl.removeEventListener("shown.bs.modal", handleModalShown);
            }
        };
    }, [canvas, templateSize]);


    const autoResizeCanvas = (templateSize: any, fitContents: boolean = false) => {
        if (canvas) {
            const canvasElement = canvas.getElement();
            const previewWrapper = canvasElement?.parentNode?.parentNode as HTMLDivElement;

            // Only calculate if parent container is visible and has dimensions
            if (previewWrapper && previewWrapper.offsetWidth > 0 && previewWrapper.offsetHeight > 0) {
                const dimensions = calculateCanvasDimensions(canvasElement, templateSize);
                canvas.setDimensions(dimensions);
                if (fitContents) {
                    fitObjectsToCanvas(canvas);
                }
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
                isCustomProduct && Array.isArray(canvasData) && canvasData.length > 0 ? (
                    <div className="custom-product">
                        {Array.isArray(canvasData) &&
                            canvasData.map((file: string, index: number) => (
                                <a href={file.replaceAll('/fit-in/1000x1000', '/fit-in/2000x2000')} key={file} target="_blank">
                                    File #{index + 1}
                                </a>
                            ))}
                    </div>
                ) :
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