import fabric from "@react/editor/canvas/fabric.ts";

export function initCenteringGuidelines(canvas: fabric.Canvas) {
    const centerLineMargin = 10,
        centerLineColor = "#3db2e7",
        centerLineWidth = 1.5;

    let viewportTransform = canvas.viewportTransform,
        isInVerticalCenter: boolean = false,
        isInHorizontalCenter: boolean = false;

    // Function to draw center lines
    function drawLine(x1: number, y1: number, x2: number, y2: number) {
        const ctx = canvas.getContext();
        if (!ctx || !viewportTransform) return;
        var originXY = fabric.util.transformPoint(new fabric.Point(x1, y1), viewportTransform),
            dimmensions = fabric.util.transformPoint(new fabric.Point(x2, y2), viewportTransform);
        ctx.save();
        ctx.strokeStyle = centerLineColor;
        ctx.lineWidth = centerLineWidth;

        // Transform coordinates based on the viewportTransform
        ctx.beginPath();
        ctx.moveTo(originXY.x, originXY.y);

        ctx.lineTo(dimmensions.x, dimmensions.y);
        ctx.stroke();
        ctx.restore();
    }

    function showVerticalCenterLine() {
        drawLine(canvas.getWidth() / 2 + 0.5, 0, canvas.getWidth() / 2 + 0.5, canvas.getHeight());
    }

    function showHorizontalCenterLine() {
        drawLine(0, canvas.getHeight() / 2 + 0.5, canvas.getWidth(), canvas.getHeight() / 2 + 0.5);
    }

    canvas.on("mouse:down", function () {
        viewportTransform = canvas.viewportTransform;
    });

    canvas.on("object:moving", function (e) {
        const object = e.target;
        if (!object) return;

        const objectCenter = object.getCenterPoint();

        // Calculate alignment margins
        const deltaX = Math.abs(objectCenter.x - canvas.getWidth() / 2),
            deltaY = Math.abs(objectCenter.y - canvas.getHeight() / 2);

        isInVerticalCenter = deltaX <= centerLineMargin;
        isInHorizontalCenter = deltaY <= centerLineMargin;

        // Snap to center if close enough
        if (isInVerticalCenter || isInHorizontalCenter) {
            object.setPositionByOrigin(
                new fabric.Point(
                    isInVerticalCenter ? canvas.getWidth() / 2 : objectCenter.x,
                    isInHorizontalCenter ? canvas.getHeight() / 2 : objectCenter.y
                ),
                "center",
                "center"
            );
        }
    });

    canvas.on("before:render", function () {
        const ctx = canvas.getContext();
        if (ctx) canvas.clearContext(ctx);
    });

    canvas.on("after:render", function () {
        if (isInVerticalCenter) {
            showVerticalCenterLine();
        }
        if (isInHorizontalCenter) {
            showHorizontalCenterLine();
        }
    });

    canvas.on("mouse:up", function () {
        isInVerticalCenter = false;
        isInHorizontalCenter = false;
        canvas.renderAll();
    });
}
