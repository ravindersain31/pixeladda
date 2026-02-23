import React, { useState } from "react";
import Preview from "./Preview";
import SVGLoader from "./SVGLoader";
import fabric from "@react/admin/editor/canvas/fabric.ts";

interface CanvasContext {
    canvas: fabric.Canvas,
    preview: HTMLDivElement | null,
    init: (element: HTMLCanvasElement | string, options?: any) => fabric.Canvas,
}

const SVGUploader = (props: any) => {

  const [canvasContext, setCanvasContext] = useState<CanvasContext>({
    canvas: {} as fabric.Canvas,
    preview: null,
    init: (element: HTMLCanvasElement | string, options: any = {}) => {
        return new fabric.Canvas(element, {
            width: 200,
            height: 200,
            preserveObjectStacking: true,
            perPixelTargetFind: true,
            backgroundColor: '#FFF',
            ...options,
        })
    },
  });

  return (
    <div className="svg-uploader">
        <SVGLoader {...props} canvasContext={canvasContext}/>
        <Preview
            variant={props.variant}
            templateJsonUrl={props.templateJsonUrl}
            canvasContext={canvasContext}
        />
    </div>
  ); 
};

export default SVGUploader;