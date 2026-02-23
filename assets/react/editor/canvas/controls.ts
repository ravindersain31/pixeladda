import {
    Bottom,
    Left,
    MaximizeLeft,
    Right,
    Rotate,
    Top,
    Close,
    Trash,
    Copy,
    Resize
} from "@react/editor/canvas/icons.ts";
import { isMobile } from "react-device-detect";

const buildControls = (fabric: any) => {
    const controls = fabric.Object.prototype.controls;

    const cornerSize = isMobile ? 14 : 16;

    function renderControlIcon(svg: string, fadeOpacity: number = 1) {
        const img = document.createElement('img');
        img.src = `data:image/svg+xml,${encodeURIComponent(svg)}`;
        return function renderControlIcon(ctx: any, left: any, top: any, styleOverride: any, fabricObject: any) {
            const isHovered =
              fabricObject.__corner === "mt" ||
              fabricObject.__corner === "mb" ||
              fabricObject.__corner === "ml" ||
              fabricObject.__corner === "mr";
            const isActive =
              fabricObject.__corner === "tl" ||
              fabricObject.__corner === "tr" ||
              fabricObject.__corner === "bl" ||
              fabricObject.__corner === "br" ||
              fabricObject.__corner === "mtr";
            const isMoving =
              fabricObject.isControlVisible &&
              fabricObject.isControlVisible("tl") &&
              fabricObject.isMoving;

            const opacity = isHovered || isActive || isMoving ? 0.3 : fadeOpacity;

            ctx.save();
            ctx.globalAlpha = opacity;
            ctx.translate(left, top);
            ctx.rotate(fabric.util.degreesToRadians(fabricObject.angle));
            ctx.drawImage(img, -cornerSize / 2, -cornerSize / 2, cornerSize, cornerSize);
            ctx.restore();
        }
    }

    fabric.Object.prototype.controls.tr = new fabric.Control({
        x: controls.tr.x,
        y: controls.tr.y,
        cursorStyle: 'pointer',
        mouseUpHandler: function (eventData: any, transform: any, x: any, y: any) {
            const target = transform.target;
            const canvas = target.canvas;
            if (target._objects) {
                canvas.remove(...target._objects);
            } else {
                canvas.remove(target);
            }
            canvas.discardActiveObject();
            canvas.requestRenderAll();
        },
        render: renderControlIcon(Trash),
    });

    fabric.Object.prototype.controls.tl = new fabric.Control({
        ...controls.tl,
        render: renderControlIcon(MaximizeLeft),
    });

    fabric.Object.prototype.controls.bl = new fabric.Control({
        x: controls.bl.x,
        y: controls.bl.y,
        cursorStyle: 'pointer',
        mouseUpHandler: function (eventData: any, transform: any, x: any, y: any) {
            const target = transform.target;
            const canvas = target.canvas;
            if (target._objects) {
                for (const obj of target._objects) {
                    obj.clone((cloned: any) => {
                        cloned.left = 10;
                        cloned.top = 10;
                        if(target.custom) {
                            cloned.custom = {
                                id: target.custom.id,
                                type: obj.custom.type,
                                uid: target.custom.id,
                            };
                        }
                        canvas.add(cloned);
                    });
                }
            } else {
                target.clone((cloned: any) => {
                    cloned.left += 10;
                    cloned.top += 10;
                    if(target.custom) {
                        cloned.custom = {
                            id: target.custom.id,
                            type: target.custom.type,
                            uid: target.custom.id,
                        };
                    }
                    canvas.add(cloned);
                });
            }
            canvas.discardActiveObject();
            canvas.requestRenderAll();
        },
        render: renderControlIcon(Copy),
    });

    fabric.Object.prototype.controls.br = new fabric.Control({
        ...controls.br,
        render: renderControlIcon(MaximizeLeft),
    });

    fabric.Object.prototype.controls.mt = new fabric.Control({
        ...controls.mt,
        render: renderControlIcon(Top),
    });

    fabric.Object.prototype.controls.resize = new fabric.Control({
        x: 0,
        y: -0.5,
        offsetY: -20,
        offsetX: 0,
        cursorStyle: 'pointer',
        mouseUpHandler: function (eventData: any, transform: any) {
            const target = transform.target;
            if (target instanceof fabric.IText || target instanceof fabric.Text) {
                const text = target.text.trim() as string;
                if (text.includes("\n")) {
                    target.text = text.replace(/\n/g, " ").trim();
                    target.textType = "singleLine";
                } else {
                    target.text = text.split(" ").filter(word => word.length > 0).join("\n");
                    target.textType = "multiLine";
                }
                const canvas = target.canvas;
                target.set({
                    textAlign: "center",
                });
                if (canvas) {
                    canvas.requestRenderAll();
                }
            }
        },
        render: renderControlIcon(Resize),
    });

    fabric.Object.prototype.controls.mb = new fabric.Control({
        ...controls.mb,
        render: renderControlIcon(Bottom),
    });

    fabric.Object.prototype.controls.ml = new fabric.Control({
        ...controls.ml,
        render: renderControlIcon(Left),
    });

    fabric.Object.prototype.controls.mr = new fabric.Control({
        ...controls.mr,
        render: renderControlIcon(Right),
    });

    fabric.Object.prototype.controls.mtr = new fabric.Control({
        ...controls.mtr,
        render: renderControlIcon(Rotate),
    });

}

export default buildControls;