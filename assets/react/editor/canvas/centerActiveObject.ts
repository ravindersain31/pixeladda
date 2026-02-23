import fabric from "./fabric.ts";

const centerActiveObject = (canvas: fabric.Canvas) => {
    const activeObject = canvas.getActiveObject();

    if (activeObject) {
        const canvasWidth = canvas.getWidth();
        const canvasHeight = canvas.getHeight();
        const activeObjectWidth = activeObject.getScaledWidth();
        const activeObjectHeight = activeObject.getScaledHeight();

        // Calculate the position to move the active object to the center of the canvas
        const leftPosition = (canvasWidth - activeObjectWidth) / 2;
        const topPosition = (canvasHeight - activeObjectHeight) / 2;

        // Set the new position for the active object
        activeObject.set({
            left: leftPosition,
            top: topPosition,
        });

        canvas.requestRenderAll();
    }
};

export default centerActiveObject;