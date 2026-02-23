import {useEffect} from "react";
import {CanvasWrapper, PreviewContent} from "../styled.tsx";
import { autoResizeCanvas } from "../../utils.ts";

const Canvas = ({variant, canvasContext, templateJsonUrl}: any) => {

    const loadTemplate = async () => {
        let template: any = {};
        const ext = templateJsonUrl.split('.').pop();
        if (ext === 'json') {
            const response = await fetch(templateJsonUrl)
            template = await response.json();
            if (template.overlayImage) {
                delete template.overlayImage;
            }
        }

        if (canvasContext.canvas && template?.objects.length > 0) {
            canvasContext.canvas.loadFromJSON(template, (ob: any) => {
                autoResizeCanvas(canvasContext.canvas, variant.templateSize, true);
                canvasContext.canvas.requestRenderAll();
            }, (j: any, o: any) => {
                o.selection = false;
                o.selectable = false;
                return o;
            });
        }        
    }

    useEffect(() => {
        canvasContext.canvas = canvasContext.init(variant.class);
        loadTemplate();
        autoResizeCanvas(canvasContext.canvas, variant.templateSize, true);
    }, []);

    return (
        <>
            <PreviewContent id={`editor-canvas-preview-${variant.class}`}>
                <CanvasWrapper>
                    <canvas id={variant.class} className={variant.class}/>
                </CanvasWrapper>
            </PreviewContent>
        </>
    )
}

export default Canvas;