import {
    AddTextButton,
    AddTextContainer,
    AddTextInput,
} from "./styled";
import {Label} from "../styled.tsx";
import {useContext, useEffect, useState} from "react";
import CanvasContext from "@react/admin/editor/context/canvas.ts";
import fabric from "@react/admin/editor/canvas/fabric.ts";

interface AddTextProps {
    textObjects: fabric.Text[] | fabric.IText[];
}

const AddText = ({textObjects}: AddTextProps) => {
    const canvasContext = useContext(CanvasContext);

    const [isEdit, setIsEdit] = useState(false);
    const [text, setText] = useState('');

    useEffect(() => {
        canvasContext.canvas.on('object:modified', onObjectModified);
    }, [canvasContext.canvas]);

    useEffect(() => {
        if (textObjects.length === 0) {
            setIsEdit(false);
            setText('');
        } else {
            for (const object of textObjects) {
                setIsEdit(true);
                setText(object.text || '');
            }
        }
    }, [textObjects]);

    const onObjectModified = (event: fabric.IEvent) => {
        const object = event.target as fabric.IText | fabric.Text;
        if (object.type === 'text' || object.type === 'i-text') {
            setText(object.text || '');
            setIsEdit(true);
        }
    }

    const onAddText = () => {
        if (isEdit) {
            for (const object of textObjects) {
                object.text = text.trim();
                object.dirty = true;
            }
        } else {
            const newText = new fabric.IText(text.trim(), {
                left: 10,
                top: 20,
                fontFamily: 'arial',
                fill: '#000000',
                fontSize: 50,
            });

            canvasContext.canvas.add(newText);
            canvasContext.canvas.setActiveObject(newText);
            setIsEdit(true);
        }
        canvasContext.canvas.requestRenderAll();
    }

    return <>
        <Label>{isEdit ? 'Edit' : 'Add'} Text</Label>
        <AddTextContainer>
            <AddTextInput
                size="large"
                value={text}
                onChange={(e: any) => setText(e.target.value)}
                placeholder="Add Text"
            />
            <AddTextButton type="primary" onClick={onAddText}>{isEdit ? 'Update' : 'Add'} Text</AddTextButton>
        </AddTextContainer>
    </>
}

export default AddText;