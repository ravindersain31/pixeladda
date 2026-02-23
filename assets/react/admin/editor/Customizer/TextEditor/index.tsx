import {Row, Col} from "antd";

import FontFamily from "./FontFamily";
import FontStyle from "./FontStyle";
import Alignment from "./Alignment";
import ColorPicker from "./ColorPicker";
import AddText from "./AddText";

import {
    FontSize,
    Label, TrimWidth
} from "./styled.tsx"
import {useContext, useEffect, useState} from "react";
import CanvasContext from "@react/admin/editor/context/canvas.ts";
import fabric from "@react/admin/editor/canvas/fabric.ts";
import LockObject from "./LockObject";

const TextEditor = () => {

    const [objects, setObjects] = useState<fabric.Object[]>([]);
    const [textObjects, setTextObjects] = useState<fabric.Text[] | fabric.IText[]>([]);
    const [fontSize, setFontSize] = useState<number | string | null>(null);

    const canvasContext = useContext(CanvasContext);

    useEffect(() => {
        canvasContext.canvas.on('selection:created', onSelection);
        canvasContext.canvas.on('selection:updated', onSelection);
        canvasContext.canvas.on('selection:cleared', onSelection);
    }, [canvasContext.canvas]);

    useEffect(() => {
        if (textObjects.length === 0) {
            setFontSize(null);
        } else {
            for (const object of textObjects) {
                const fontSize = object.fontSize || 0;
                setFontSize(parseInt(fontSize.toFixed(0)));
            }
        }
    }, [textObjects]);

    const onSelection = (event: fabric.IEvent) => {
        const selectedObjects = event.selected as fabric.Text[] | fabric.IText[] || [];
        setObjects(selectedObjects);
        if (selectedObjects) {
            const textObjects = selectedObjects.filter(selectedObject => ['text', 'i-text'].includes(selectedObject.type as string));
            setTextObjects(textObjects);
        }
    }

    const onFontSizeChange = (fontSize: number) => {
        textObjects.forEach(textObject => {
            textObject.fontSize = fontSize || 12;
        });
        setFontSize(fontSize)
        canvasContext.canvas.renderAll();
    }

    return (
        <>
            <Row style={{marginBottom: 20}}>
                <Col span={16}>
                    <FontFamily textObjects={textObjects}/>
                </Col>
                <Col span={8}>
                    <Label>Font Size</Label>
                    <FontSize
                        min={5}
                        value={fontSize}
                        disabled={textObjects.length === 0}
                        onChange={(value: number | string | null) => onFontSizeChange(parseInt((value as number).toFixed(0)))}
                        placeholder="Font Size"
                    />
                </Col>
            </Row>
            <Row style={{marginBottom: 20}}>
                <Col span={16} style={{textAlign: 'center'}}>
                    <FontStyle textObjects={textObjects || []}/>
                </Col>
                <Col span={8} style={{textAlign: 'center'}}>
                    <Alignment objects={objects}/>
                </Col>
            </Row>
            <Row style={{marginBottom: 20}}>
                <ColorPicker
                    objects={objects}
                    textObjects={textObjects}
                />
            </Row>
            {/* <Row style={{marginBottom: 20}}>
                <LockObject
                    objects={objects}
                />
            </Row> */}
            <Row style={{marginBottom: 20}}>
                <Col span={24}>
                    <AddText textObjects={textObjects}/>
                </Col>
            </Row>
        </>
    );
}

export default TextEditor;