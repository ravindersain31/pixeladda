import {useContext, useEffect, useState} from "react";
import {CanvasWrapper, PreviewContent} from "../styled.tsx";
import CanvasContext from "@react/admin/editor/context/canvas.ts";
import useCanvas from "@react/admin/editor/hooks/useCanvas.tsx";
import {deleteActiveObjects, deselectAllObjects, selectAllObjects} from "@react/admin/editor/canvas/utils.ts";

const Canvas = () => {

    const [windowDimensions, setWindowDimensions] = useState({
        width: window.innerWidth,
        height: window.innerHeight,
    });

    const canvasContext = useContext(CanvasContext);

    const canvas = useCanvas();

    useEffect(() => {
        window.addEventListener("resize", () => {
            setWindowDimensions({
                width: window.innerWidth,
                height: window.innerHeight,
            });
        });

        canvasContext.canvas = canvasContext.init('editor-canvas');
        document.addEventListener('keydown', onWindowKeyUp);
    }, []);

    useEffect(() => {
        const templateSize = {
            width: 12,
            height: 12,
        }
        canvas.autoResizeCanvas(templateSize, true);
    }, [windowDimensions]);

    const onWindowKeyUp = (event: any) => {
        // on delete/backspace
        if (['Backspace', 'Delete'].includes(event.code)) {
            deleteActiveObjects(canvasContext.canvas);
        }
        // on ctrl/cmd + A
        if ((event.ctrlKey || event.metaKey) && event.code === 'KeyA') {
            selectAllObjects(canvasContext.canvas);
        }
        // on esc
        if (event.code === 'Escape') {
            deselectAllObjects(canvasContext.canvas);
        }
    }

    return (
        <>
            <PreviewContent id="editor-canvas-preview">
                <CanvasWrapper>
                    <canvas id="editor-canvas" className="editor-canvas"/>
                </CanvasWrapper>
            </PreviewContent>
        </>
    )
}

export default Canvas;