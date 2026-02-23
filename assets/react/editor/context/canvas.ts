import {createContext, useContext} from "react";
import fabric from "@react/editor/canvas/fabric.ts";

interface CanvasContext {
    canvas: fabric.Canvas,
    preview: HTMLDivElement | null,
    init: (element: HTMLCanvasElement | string, options?: any) => fabric.Canvas,
}

const CanvasContext = createContext<CanvasContext>({
    canvas: {} as fabric.Canvas,
    preview: null,
    init: (element: HTMLCanvasElement | string, options: any = {}) => {
        return new fabric.Canvas(element, {
            width: 100,
            height: 100,
            preserveObjectStacking: true,
            perPixelTargetFind: true,
            backgroundColor: '#FFF',
            ...options,
        })
    },
});

export function useCanvasContext() {
    const context = useContext(CanvasContext);

    if (!context) {
        throw new Error("useCanvasContext must be used within CanvasProvider");
    }

    return context;
}

export default CanvasContext;