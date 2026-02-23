import fabric from "@react/editor/canvas/fabric.ts";
import {ILineOptions} from "fabric/fabric-impl";

export function initAligningGuidelines(canvas: fabric.Canvas) {
    var ctx = canvas.getSelectionContext(),
        aligningLineOffset = 5,
        aligningLineMargin = 7,
        aligningLineWidth = 1,
        aligningLineColor = "black",
        viewportTransform = canvas.viewportTransform,
        zoom = 1;

    function drawVerticalLine(coords: ILineOptions) {
        drawLine(
            coords.x1! + 0.5,
            coords.y1! > coords.y2! ? coords.y2! : coords.y1!,
            coords.x1! + 0.5,
            coords.y2! > coords.y1! ? coords.y2! : coords.y1!
        );
    }

    function drawHorizontalLine(coords: ILineOptions) {
        drawLine(
            coords.x1! > coords.x2! ? coords.x2! : coords.x1!,
            coords.y1! + 0.5,
            coords.x2! > coords.x1! ? coords.x2! : coords.x1!,
            coords.y1! + 0.5
        );
    }

    function drawLine(x1: number, y1: number, x2: number, y2: number) {
        ctx.save();
        ctx.lineWidth = aligningLineWidth;
        ctx.strokeStyle = aligningLineColor;
        ctx.setLineDash([5, 5]);
        ctx.beginPath();
        if (viewportTransform) {
            ctx.moveTo((x1 + viewportTransform[4]) * zoom, (y1 + viewportTransform[5]) * zoom);
            ctx.lineTo((x2 + viewportTransform[4]) * zoom, (y2 + viewportTransform[5]) * zoom);
        }
        ctx.stroke();
        ctx.restore();
    }

    function isInRange(value1: number, value2: number) {
        value1 = Math.round(value1);
        value2 = Math.round(value2);
        for (var i = value1 - aligningLineMargin, len = value1 + aligningLineMargin; i <= len; i++) {
            if (i === value2) {
                return true;
            }
        }
        return false;
    }

    var verticalLines: ILineOptions[] = [],
        horizontalLines: ILineOptions[] = [];

    canvas.on("mouse:down", function () {
        viewportTransform = canvas.viewportTransform;
        zoom = canvas.getZoom();
    });

    canvas.on("object:moving", function (e) {
        let activeObject = e.target;
        if (!activeObject || !viewportTransform) return;

        let canvasObjects = canvas.getObjects();
        let activeObjectCenter = activeObject.getCenterPoint(),
            activeObjectLeft = activeObjectCenter.x,
            activeObjectTop = activeObjectCenter.y,
            activeObjectBoundingRect = activeObject.getBoundingRect(),
            activeObjectHeight = activeObjectBoundingRect.height / viewportTransform[3],
            activeObjectWidth = activeObjectBoundingRect.width / viewportTransform[0],
            horizontalInTheRange = false,
            verticalInTheRange = false,
            transform = canvas.viewportTransform;

        if (!transform) return;

        // It should be trivial to DRY this up by encapsulating (repeating) creation of x1, x2, y1, and y2 into functions,
        // but we're not doing it here for perf. reasons -- as this a function that's invoked on every mouse move

        for (var i = canvasObjects.length; i--; ) {
            if (canvasObjects[i] === activeObject) continue;

            var objectCenter = canvasObjects[i].getCenterPoint(),
                objectLeft = objectCenter.x,
                objectTop = objectCenter.y,
                objectBoundingRect = canvasObjects[i].getBoundingRect(),
                objectHeight = objectBoundingRect.height / viewportTransform[3],
                objectWidth = objectBoundingRect.width / viewportTransform[0];

            // snap by the horizontal center line
            if (isInRange(objectLeft, activeObjectLeft)) {
                verticalInTheRange = true;
                verticalLines.push({
                    x1: objectLeft,
                    y1:
                        objectTop < activeObjectTop
                            ? objectTop - objectHeight / 2 - aligningLineOffset
                            : objectTop + objectHeight / 2 + aligningLineOffset,
                    y2:
                        activeObjectTop > objectTop
                            ? activeObjectTop + activeObjectHeight / 2 + aligningLineOffset
                            : activeObjectTop - activeObjectHeight / 2 - aligningLineOffset,
                });
                activeObject.setPositionByOrigin(new fabric.Point(objectLeft, activeObjectTop), "center", "center");
            }

            // snap by the left edge
            if (isInRange(objectLeft - objectWidth / 2, activeObjectLeft - activeObjectWidth / 2)) {
                verticalInTheRange = true;
                verticalLines.push({
                    x1: objectLeft - objectWidth / 2,
                    y1:
                        objectTop < activeObjectTop
                            ? objectTop - objectHeight / 2 - aligningLineOffset
                            : objectTop + objectHeight / 2 + aligningLineOffset,
                    y2:
                        activeObjectTop > objectTop
                            ? activeObjectTop + activeObjectHeight / 2 + aligningLineOffset
                            : activeObjectTop - activeObjectHeight / 2 - aligningLineOffset,
                });
                activeObject.setPositionByOrigin(
                    new fabric.Point(objectLeft - objectWidth / 2 + activeObjectWidth / 2, activeObjectTop),
                    "center",
                    "center"
                );
            }

            // snap by the right edge
            if (isInRange(objectLeft + objectWidth / 2, activeObjectLeft + activeObjectWidth / 2)) {
                verticalInTheRange = true;
                verticalLines.push({
                    x1: objectLeft + objectWidth / 2,
                    y1:
                        objectTop < activeObjectTop
                            ? objectTop - objectHeight / 2 - aligningLineOffset
                            : objectTop + objectHeight / 2 + aligningLineOffset,
                    y2:
                        activeObjectTop > objectTop
                            ? activeObjectTop + activeObjectHeight / 2 + aligningLineOffset
                            : activeObjectTop - activeObjectHeight / 2 - aligningLineOffset,
                });
                activeObject.setPositionByOrigin(
                    new fabric.Point(objectLeft + objectWidth / 2 - activeObjectWidth / 2, activeObjectTop),
                    "center",
                    "center"
                );
            }

            // snap by the vertical center line
            if (isInRange(objectTop, activeObjectTop)) {
                horizontalInTheRange = true;
                horizontalLines.push({
                    y1: objectTop,
                    x1:
                        objectLeft < activeObjectLeft
                            ? objectLeft - objectWidth / 2 - aligningLineOffset
                            : objectLeft + objectWidth / 2 + aligningLineOffset,
                    x2:
                        activeObjectLeft > objectLeft
                            ? activeObjectLeft + activeObjectWidth / 2 + aligningLineOffset
                            : activeObjectLeft - activeObjectWidth / 2 - aligningLineOffset,
                });
                activeObject.setPositionByOrigin(new fabric.Point(activeObjectLeft, objectTop), "center", "center");
            }

            // snap by the top edge
            if (isInRange(objectTop - objectHeight / 2, activeObjectTop - activeObjectHeight / 2)) {
                horizontalInTheRange = true;
                horizontalLines.push({
                    y1: objectTop - objectHeight / 2,
                    x1:
                        objectLeft < activeObjectLeft
                            ? objectLeft - objectWidth / 2 - aligningLineOffset
                            : objectLeft + objectWidth / 2 + aligningLineOffset,
                    x2:
                        activeObjectLeft > objectLeft
                            ? activeObjectLeft + activeObjectWidth / 2 + aligningLineOffset
                            : activeObjectLeft - activeObjectWidth / 2 - aligningLineOffset,
                });
                activeObject.setPositionByOrigin(
                    new fabric.Point(activeObjectLeft, objectTop - objectHeight / 2 + activeObjectHeight / 2),
                    "center",
                    "center"
                );
            }

            // snap by the bottom edge
            if (isInRange(objectTop + objectHeight / 2, activeObjectTop + activeObjectHeight / 2)) {
                horizontalInTheRange = true;
                horizontalLines.push({
                    y1: objectTop + objectHeight / 2,
                    x1:
                        objectLeft < activeObjectLeft
                            ? objectLeft - objectWidth / 2 - aligningLineOffset
                            : objectLeft + objectWidth / 2 + aligningLineOffset,
                    x2:
                        activeObjectLeft > objectLeft
                            ? activeObjectLeft + activeObjectWidth / 2 + aligningLineOffset
                            : activeObjectLeft - activeObjectWidth / 2 - aligningLineOffset,
                });
                activeObject.setPositionByOrigin(
                    new fabric.Point(activeObjectLeft, objectTop + objectHeight / 2 - activeObjectHeight / 2),
                    "center",
                    "center"
                );
            }
        }

        if (!horizontalInTheRange) {
            horizontalLines.length = 0;
        }

        if (!verticalInTheRange) {
            verticalLines.length = 0;
        }
    });

    canvas.on("before:render", function () {
        canvas.clearContext(ctx);
    });

    canvas.on("after:render", function () {
        for (var i = verticalLines.length; i--; ) {
            drawVerticalLine(verticalLines[i]);
        }
        for (var i = horizontalLines.length; i--; ) {
            drawHorizontalLine(horizontalLines[i]);
        }

        verticalLines.length = horizontalLines.length = 0;
    });

    canvas.on("mouse:up", function () {
        verticalLines.length = horizontalLines.length = 0;
        canvas.renderAll();
    });
}
