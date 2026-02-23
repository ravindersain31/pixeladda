import {
    Bottom,
    Left,
    MaximizeLeft,
    Right,
    Rotate,
    Top,
    Close,
    Trash,
    Copy
} from "@react/admin/editor/canvas/icons.ts";

const buildControls = (fabric: any) => {
    const controls = fabric.Object.prototype.controls;

    const cornerSize = 25;

    function renderControlIcon(svg: string) {
        const img = document.createElement('img');
        img.src = `data:image/svg+xml,${encodeURIComponent(svg)}`;
        return function renderControlIcon(ctx: any, left: any, top: any, styleOverride: any, fabricObject: any) {
            ctx.save();
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
                        canvas.add(cloned);
                    });
                }
            } else {
                target.clone((cloned: any) => {
                    cloned.left += 10;
                    cloned.top += 10;
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