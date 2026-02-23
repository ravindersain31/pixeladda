import fabric from "@react/admin/editor/canvas/fabric.ts";

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

    const pageHeader = document?.querySelector("#page-header");
    const pageHeaderHeight = pageHeader?.clientHeight || 0;
    if (pageHeaderHeight > 0) {
        preview.height -= pageHeaderHeight;
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
    // canvas.discardActiveObject();
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

export const sanitizeFontFamily = (fontFamily: string) => {
    const defaultFontFamily = 'aardvarkcaferegular';

    if (!fontFamily) return defaultFontFamily;  

    const match = fontFamily.match(/^(['"]?[^,'"]+['"]?)/);  
    const fontName = match?.[1]?.trim() || defaultFontFamily;
  
    return fontName.replace(/[^A-Za-z0-9]/gi, '').toLowerCase();
};
