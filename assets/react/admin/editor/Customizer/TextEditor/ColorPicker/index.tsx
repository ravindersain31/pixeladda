import {Col} from "antd";
import Picker from "./Picker";
import {Label, TrimWidth} from "./styled.tsx";
import {useContext, useEffect, useState} from "react";
import CanvasContext from "@react/admin/editor/context/canvas.ts";
import fabric from "@react/admin/editor/canvas/fabric.ts";

interface ColorPickersProps {
    objects: fabric.Object[];
    textObjects: fabric.Text[] | fabric.IText[];
}

const ColorPickers = ({objects, textObjects}: ColorPickersProps) => {
    const canvasContent = useContext(CanvasContext);

    const [backgroundColor, setBackgroundColor] = useState<string>('#ddd');

    const [fillColor, setFillColor] = useState<string>('#ddd');

    const [trimColor, setTrimColor] = useState<string>('#ddd');

    const [trimWidth, setTrimWidth] = useState<number>(0);

    useEffect(() => {
        if (canvasContent.canvas.backgroundColor) {
            setBackgroundColor(canvasContent.canvas.backgroundColor as string);
        }
    }, [canvasContent.canvas]);

    useEffect(() => {
        if (objects.length === 0) {
            setFillColor('#ddd');
            setTrimColor('#ddd');
            setTrimWidth(0);
        } else {
            for (const object of objects) {
                if (['i-text', 'text'].includes(object.type as string)) {
                    setFillColor(object.fill as string);
                }
                if (['image'].includes(object.type as string)) {
                    const image = object as  fabric.Image;
                    for (const filter of image.filters as any || []) {
                        if (filter.mode === 'overlay') {
                            setFillColor(filter.color);
                        }
                    }
                }
                setTrimColor(object.stroke || '#ddd');
                setTrimWidth(object.strokeWidth || 0);
            }
        }
    }, [objects]);

    const onBackgroundChange = (color: string) => {
        canvasContent.canvas.setBackgroundColor(color, canvasContent.canvas.renderAll.bind(canvasContent.canvas));
        setBackgroundColor(color);
    }

    const onFillColorChange = (color: string) => {
        if (objects.length <= 0) return;
        for (const object of objects) {
            if (object.type === 'image') {
                const image = object as fabric.Image;
                image.filters = [
                    new fabric.Image.filters.BlendColor({
                        'mode': 'overlay',
                        'color': color,
                        'alpha': 1,
                    })
                ];
                image.applyFilters();
            } else {
                object.fill = color;
                object.dirty = true;
            }
        }
        canvasContent.canvas.requestRenderAll();
        setFillColor(color);
    }

    const onTrimColorChange = (color: string) => {
        for (const object of objects) {
            object.stroke = color;
            object.dirty = true;
        }
        canvasContent.canvas.requestRenderAll();
        setTrimColor(color);
    }

    const onTrimWidthChange = (width: number) => {
        for (const object of objects) {
            object.strokeWidth = width;
            object.dirty = true;
        }
        canvasContent.canvas.requestRenderAll();
        setTrimWidth(width);
    }

    return (
        <>
            <Col span={5} className="d-flex flex-column" style={{textAlign: 'center'}}>
                <Label>Color</Label>
                <Picker
                    disabled={objects.length <= 0}
                    value={fillColor}
                    onChange={(value) => onFillColorChange(value.toHexString())}
                />
            </Col>
            <Col span={5} className="d-flex flex-column" style={{textAlign: 'center'}}>
                <Label>Background</Label>
                <Picker
                    value={backgroundColor}
                    onChange={(value) => onBackgroundChange(value.toHexString())}
                />
            </Col>
            <Col span={5} className="d-flex flex-column" style={{textAlign: 'center'}}>
                <Label>Trim</Label>
                <Picker
                    disabled={objects.length <= 0}
                    value={trimColor}
                    onChange={(value) => onTrimColorChange(value.toHexString())}/>
            </Col>
            <Col span={9} className="d-flex flex-column" style={{textAlign: 'center'}}>
                <Label>Trim Width</Label>
                <TrimWidth
                    min={0}
                    max={10}
                    disabled={objects.length <= 0}
                    value={trimWidth}
                    onChange={(value: number) => onTrimWidthChange(value)}
                />
            </Col>
        </>
    )
}

export default ColorPickers;