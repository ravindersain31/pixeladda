import fabric from "@react/editor/canvas/fabric.ts";
import { isMobile, isTablet } from "react-device-detect";

export const calculatePercentageChange = (initialValue: number, newValue: number) => {
    const difference = newValue - initialValue;
    return (difference / initialValue) * 100;
}
export const calculateAspectRatio = (width: number, height: number) => {
    const gcd: any = (a: number, b: number) => (b === 0 ? a : gcd(b, a % b));
    const aspectRatioGCD = gcd(width, height);
    return {
        width: width / aspectRatioGCD,
        height: height / aspectRatioGCD,
    };
}

export const calculateCanvasDimensions = (templateSize: any) => {
    const apr = calculateAspectRatio(templateSize.width, templateSize.height);

    const previewWrapper = document.querySelector("#editor-canvas-preview")?.parentNode as HTMLDivElement;
    const paddingAround = 15;
    const paddingBetweenBorder = 2;
    const borderWidth = 2;
    const additionalSpaceUsed = paddingAround + paddingBetweenBorder + borderWidth;
    const preview = {
        width: previewWrapper.offsetWidth - additionalSpaceUsed * 2,
        height: window.innerHeight - additionalSpaceUsed * 2,
    }

    if(isTablet && templateSize.width > templateSize.height) {
        preview.width = preview.width * 0.55;
    }

    // reduce canvas height 40% mobile / 20% desktop
    const reductionFactor = isMobile ? 0.4 : 0.25;
    preview.height *= 1 - reductionFactor;

    // Commented to due don't wanted fluctuate canvas height
    if(isMobile) {
      const canvasControls = previewWrapper?.querySelector("#canvas-view-controls");
      const canvasControlsHeight = canvasControls?.clientHeight || 0;
      if (canvasControlsHeight > 0) {
          preview.height -= canvasControlsHeight;
      }
    }

    let canvasWidth, canvasHeight;
    if (apr.width > apr.height) {
        canvasWidth = preview.width;
        canvasHeight = preview.width * (apr.height / apr.width);
    } else if (apr.height > apr.width) {
        canvasWidth = preview.height * (apr.width / apr.height);
        canvasHeight = preview.height;
    } else if (preview.height > preview.width) {
        canvasWidth = preview.width;
        canvasHeight = preview.width * (apr.width / apr.height);
    } else {
        canvasWidth = preview.height * (apr.width / apr.height);
        canvasHeight = preview.height;
    }

    return {
        width: canvasWidth,
        height: canvasHeight,
    };
}

export const deleteActiveObjects = (canvas: fabric.Canvas) => {
    const activeObjects = canvas.getActiveObjects();
    if (activeObjects.length > 0) {
        canvas.remove(...activeObjects);
    }
    canvas.requestRenderAll();
}

export const selectAllObjects = (canvas: fabric.Canvas) => {
    const objects = canvas.getObjects();
    canvas.discardActiveObject();
    canvas.setActiveObject(new fabric.ActiveSelection(objects, {
        canvas: canvas,
    }));
    canvas.requestRenderAll();
}

export const deselectAllObjects = (canvas: fabric.Canvas) => {
    canvas.discardActiveObject();
    canvas.requestRenderAll();
}

export type LockableObject = {
    lockMovementX: boolean;
    lockMovementY: boolean;
    lockRotation: boolean;
    lockScalingX: boolean;
    lockScalingY: boolean;
    hasControls: boolean;
    // selectable: boolean;
};

export const lockAttrs: (keyof LockableObject)[] = [
    "lockMovementX",
    "lockMovementY",
    "lockRotation",
    "lockScalingX",
    "lockScalingY",
    "hasControls",
    // "selectable"
];


export const CanvasProperties = [
    "custom",
    "hasControls",
    "selectable",
    "lockMovementX",
    "lockMovementY",
    "lockRotation",
    "lockScalingX",
    "lockScalingY",
    "lockUniScaling",
];

export const preloadFonts = async (objects: any[]) => {
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