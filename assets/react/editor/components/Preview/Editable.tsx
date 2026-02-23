import { useEffect, useContext, useState } from "react";
import { useAppDispatch, useAppSelector } from "@react/editor/hook.ts";
import actions from "@react/editor/redux/actions";
import useCanvas from "@react/editor/hooks/useCanvas.tsx";
import Canvas from "./Canvas";
import fabric from "@react/editor/canvas/fabric.ts";
import CanvasContext from "@react/editor/context/canvas.ts";
import { fitObjectsToCanvas } from "@react/editor/canvas";
import { isProductEditable } from "@react/editor/helper/template";
import { consolidateAllArtworks } from "@react/editor/helper/canvas.ts";

const Editable = () => {

    const config = useAppSelector(state => state.config);
    const editor = useAppSelector(state => state.editor);
    const canvas = useAppSelector(state => state.canvas);
    const canvasContext = useContext(CanvasContext);

    const dispatch = useAppDispatch();

    const canvasHook = useCanvas();

    useEffect(() => {
        (async () => {
            if (isProductEditable(config)) {
                let templateJson = canvas.data[canvas.view];
                if (!templateJson && !config.product.isCustom) {
                    // console.log('Canvas data not found, using default template json');
                    const currentProductTemplate = editor.items[canvas.item.productId]?.canvasData[canvas.view];
                    if (currentProductTemplate !== null) {
                        templateJson = currentProductTemplate;
                    } else {
                        templateJson = canvas.item.templateJson;
                    }
                }
                await loadTemplate(templateJson);
            }
        })()
    }, [canvas.templateSize, canvas.view]);

    const updateCanvasData = (canvas: fabric.Canvas, canvasData: any, side: string) => {
        requestAnimationFrame(() => {
            canvas.loadFromJSON(canvasData, () => {
                canvas.renderAll();
            });
        });
        dispatch(actions.canvas.updateCanvasData(canvasData));
        dispatch(actions.canvas.updateCanvasLoader(false));
    }

    useEffect(() => {
        if (config.product.variants) {
            (async () => {
                const variant = config.product.variants.find((v: any) => v.productId === canvas.item.productId);
                // @ts-ignore
                const objects = canvas.data[canvas.view]?.objects ?? [];
                if (variant && objects.length <= 0) {
                    await loadTemplate(variant.templateJson);
                }
            })()
        }
    }, [config.product.variants]);

    const loadTemplate = async (templateJson: string | object | null = {}) => {
        dispatch(actions.canvas.updateCanvasLoader(true));
        if (templateJson) {
            canvasHook.loadFromJSON(templateJson, canvas.templateSize, (data: any) => {
                dispatch(actions.canvas.updateCanvasData(data));
                if (data.objects.length > 0) {
                    dispatch(actions.canvas.updateCanvasLoader(false));
                    identifyArtworkInObjects(data.objects);
                }
                if(config.product.isCustom) {
                    dispatch(actions.canvas.updateCanvasLoader(false));
                }
            })
        } else {
            dispatch(actions.canvas.updateCanvasLoader(false));
        }
    }

    const identifyArtworkInObjects = (objects: any) => {
        const uniqueArtworks = consolidateAllArtworks(
            canvas.data?.front,
            canvas.data?.back,
            objects || []
        );
        dispatch(actions.editor.updateUploadedArtworks(uniqueArtworks));
    }

    return <Canvas />;
}

export default Editable;