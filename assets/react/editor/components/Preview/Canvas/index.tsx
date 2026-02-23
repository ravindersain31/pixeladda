import { useContext, useEffect, useMemo, useState } from "react";
import { CanvasWrapper, PreviewContent, SwiperPreview } from "../styled.tsx";
import ViewControls from "./Controls";
import CanvasContext from "@react/editor/context/canvas.ts";
import { useAppDispatch, useAppSelector } from "@react/editor/hook.ts";
import useCanvas from "@react/editor/hooks/useCanvas.tsx";
import { deleteActiveObjects, deselectAllObjects, selectAllObjects } from "@react/editor/canvas/utils.ts";
import actions from "@react/editor/redux/actions";
import fabric from "@react/editor/canvas/fabric";
import { isMobile } from "react-device-detect";
import { CanvasProperties } from "@react/editor/canvas/utils.ts";
import Swiper from "../Swiper/index.tsx";
import useShowCanvas from "@react/editor/hooks/useShowCanvas.tsx";
import { CustomArtwork } from "@react/editor/redux/reducer/editor/interface.ts";
import _ from "lodash";
import { consolidateAllArtworks } from "@react/editor/helper/canvas.ts";

const Canvas = () => {
    const canvas = useAppSelector(state => state.canvas);
    const editor = useAppSelector(state => state.editor);

    const [windowDimensions, setWindowDimensions] = useState({
        width: window.innerWidth,
        height: window.innerHeight,
    });

    let clipboard: fabric.Object[] = [];

    const canvasContext = useContext(CanvasContext);
    const showCanvas = useShowCanvas();
    const canvasHook = useCanvas();

    const dispatch = useAppDispatch();

    useEffect(() => {
        window.addEventListener("resize", () => {
            setWindowDimensions({
                width: window.innerWidth,
                height: window.innerHeight,
            });
        });

        canvasContext.canvas = canvasContext.init('editor-canvas');
        if (canvasContext.canvas instanceof fabric.Canvas) {
            canvasContext.canvas.on('object:modified', preventOutflowOfObject);
            canvasContext.canvas.on('object:added', debouncedUpdateCanvasData);
            canvasContext.canvas.on('object:removed', debouncedUpdateCanvasData);
        }
        document.addEventListener('keydown', onWindowKeyUp);
    }, []);

    useEffect(() => {
        canvasHook.autoResizeCanvas(canvas.templateSize, true);
    }, [canvas.templateSize]);

    useEffect(() => {
        if (!canvas.data) return;

        const uniqueArtworks = consolidateAllArtworks(
            canvas.data.front,
            canvas.data.back
        );

        dispatch(actions.editor.updateUploadedArtworks(uniqueArtworks));
    }, [canvas.data]);

    const debouncedUpdateCanvasData = useMemo(
        () => _.debounce(() => {
            if (canvasContext.canvas instanceof fabric.Canvas) {
                dispatch(actions.canvas.updateCanvasData(canvasContext.canvas.toJSON(CanvasProperties)));
            }
        }, 300),
        [canvasContext.canvas, dispatch]
    );

    const updateCanvasData = () => {
        if (canvasContext.canvas instanceof fabric.Canvas) {
            dispatch(actions.canvas.updateCanvasData(canvasContext.canvas.toJSON(CanvasProperties)));
        }
    }

    const copySelectedObjects = () => {
        if (canvasContext.canvas instanceof fabric.Canvas) {
            const activeObjects = canvasContext.canvas.getActiveObjects();
            clipboard = [];

            activeObjects.forEach((obj) => {
                obj.clone((clonedObj: fabric.Object) => {
                    clipboard.push(clonedObj);
                });
            });
        }
    };

    const pasteCopiedObjects = () => {
        if (canvasContext.canvas instanceof fabric.Canvas && clipboard.length > 0) {
            clipboard.forEach((obj) => {
                obj.clone((clonedObj: fabric.Object) => {
                    // if (clonedObj instanceof fabric.Text && !(clonedObj instanceof fabric.IText)) {
                    //     clonedObj = convertTextToIText(clonedObj as fabric.Text);
                    // }
                    canvasContext.canvas.add(clonedObj);
                    clonedObj.left = (clonedObj.left || 0) + 10;
                    clonedObj.top = (clonedObj.top || 0) + 10;
                    clonedObj.setCoords();
                    canvasContext.canvas.setActiveObject(clonedObj);
                    canvasContext.canvas.requestRenderAll();
                    debouncedUpdateCanvasData();
                });
            });
        }
    };

    const preventOutflowOfObject = (e: any) => {
        const obj = e.target;
        if (!obj || !canvasContext?.canvas) return;

        const canvas = canvasContext.canvas;
        const canvasWidth = canvas.getWidth();
        const canvasHeight = canvas.getHeight();

        const bounding = obj.getBoundingRect(true);
        if (bounding.width > canvasWidth || bounding.height > canvasHeight) {
            const scaleX = canvasWidth / bounding.width;
            const scaleY = canvasHeight / bounding.height;
            const finalScale = Math.min(scaleX, scaleY);

            obj.scaleX *= finalScale;
            obj.scaleY *= finalScale;
            obj.setCoords();
        }

        const br = obj.getBoundingRect(true);

        let newLeft = obj.left;
        let newTop = obj.top;

        // --- LEFT boundary ---
        if (br.left < 0) {
            newLeft = obj.left - br.left;
        }

        // --- TOP boundary ---
        if (br.top < 0) {
            newTop = obj.top - br.top;
        }

        // --- RIGHT boundary ---
        if (br.left + br.width > canvasWidth) {
            newLeft = obj.left - ((br.left + br.width) - canvasWidth);
        }

        // --- BOTTOM boundary ---
        if (br.top + br.height > canvasHeight) {
            newTop = obj.top - ((br.top + br.height) - canvasHeight);
        }

        obj.set({
            left: newLeft,
            top: newTop,
        });

        obj.setCoords();
        canvas.requestRenderAll();
    }

    const onWindowKeyUp = (event: any) => {
        if (event.target !== document.body) {
            return;
        }

        // on delete
        if (['Delete', 'Backspace'].includes(event.code)) {
            deleteActiveObjects(canvasContext.canvas);
            if (canvasContext.canvas instanceof fabric.Canvas) {
                dispatch(actions.canvas.updateCanvasData(canvasContext.canvas.toJSON(CanvasProperties)));
            }
        }
        // on ctrl/cmd + A
        if ((event.ctrlKey || event.metaKey) && event.code === 'KeyA') {
            selectAllObjects(canvasContext.canvas);
        }
        // on esc
        if (event.code === 'Escape') {
            deselectAllObjects(canvasContext.canvas);
        }

        if ((event.ctrlKey || event.metaKey) && event.code === 'KeyC') {
            copySelectedObjects();
        }

        if ((event.ctrlKey || event.metaKey) && event.code === 'KeyV') {
            pasteCopiedObjects();
        }
    }

    return (
        <>
            {isMobile && <ViewControls />}
            <PreviewContent id="editor-canvas-preview" $show={showCanvas}>
                <CanvasWrapper>
                    <canvas id="editor-canvas" className="editor-canvas" />
                </CanvasWrapper>
            </PreviewContent>
            <SwiperPreview>
                <Swiper />
            </SwiperPreview>
            {!isMobile && <ViewControls />}
        </>
    )
}

export default Canvas;