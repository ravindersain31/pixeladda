import {
    Bold,
    Italic,
    Underline,
    Overline,
    LineThrough, FontStyleContainer,
} from "./styled";
import {
    BoldOutlined,
    ItalicOutlined,
    UnderlineOutlined,
    LineOutlined,
    StrikethroughOutlined,
} from "@ant-design/icons";
import {Label} from "../styled.tsx";
import {useContext, useState, useEffect} from "react";
import CanvasContext from "@react/admin/editor/context/canvas.ts";
import fabric from "@react/admin/editor/canvas/fabric.ts";

interface FontStyleProps {
    textObjects: fabric.Text[] | fabric.IText[];
}

const FontStyle = ({textObjects}: FontStyleProps) => {

    const [bold, setBold] = useState(false);
    const [italic, setItalic] = useState(false);
    const [underline, setUnderline] = useState(false);
    const [overline, setOverline] = useState(false);
    const [lineThrough, setLineThrough] = useState(false);

    const canvasContext = useContext(CanvasContext);

    useEffect(() => {
        if (textObjects.length === 0) {
            setBold(false);
            setItalic(false);
            setUnderline(false);
            setOverline(false);
            setLineThrough(false);
        } else {
            for (const object of textObjects) {
                setBold(object.fontWeight === 'bold');
                setItalic(object.fontStyle === 'italic');
                setUnderline(object.underline || false);
                setOverline(object.overline || false);
                setLineThrough(object.linethrough || false);
            }
        }
    }, [textObjects]);

    const onBold = () => {
        if (textObjects.length <= 0) return;
        for (const object of textObjects) {
            object.fontWeight = bold ? 'normal' : 'bold';
            setBold(!bold);
        }
        canvasContext.canvas.requestRenderAll();
    }

    const onItalic = () => {
        if (textObjects.length <= 0) return;
        for (const object of textObjects) {
            object.fontStyle = italic ? 'normal' : 'italic';
            setItalic(!italic);
        }
        canvasContext.canvas.requestRenderAll();
    }

    const onUnderline = () => {
        if (textObjects.length <= 0) return;
        for (const object of textObjects) {
            object.underline = !underline;
            object.dirty = true;
            setUnderline(!underline);
        }
        canvasContext.canvas.requestRenderAll();
    }

    const onOverline = () => {
        if (textObjects.length <= 0) return;
        for (const object of textObjects) {
            object.overline = !overline;
            object.dirty = true;
            setOverline(!overline);
        }
        canvasContext.canvas.requestRenderAll();
    }

    const onLineThrough = () => {
        if (textObjects.length <= 0) return;
        for (const object of textObjects) {
            object.linethrough = !lineThrough;
            object.dirty = true;
            setLineThrough(!lineThrough);
        }
        canvasContext.canvas.requestRenderAll();
    }

    return <>
        <Label>Font Style</Label>
        <FontStyleContainer>
            <Bold
                onChange={onBold}
                checked={bold}
                disabled={textObjects.length <= 0}
                checkedChildren={<BoldOutlined/>}
                unCheckedChildren={<BoldOutlined/>}
            />
            <Italic
                onChange={onItalic}
                checked={italic}
                disabled={textObjects.length <= 0}
                checkedChildren={<ItalicOutlined/>}
                unCheckedChildren={<ItalicOutlined/>}
            />
            <Underline
                onChange={onUnderline}
                checked={underline}
                disabled={textObjects.length <= 0}
                checkedChildren={<UnderlineOutlined/>}
                unCheckedChildren={<UnderlineOutlined/>}
            />
            <Overline
                onChange={onOverline}
                checked={overline}
                disabled={textObjects.length <= 0}
                checkedChildren={<LineOutlined/>}
                unCheckedChildren={<LineOutlined/>}
            />
            <LineThrough
                onChange={onLineThrough}
                checked={lineThrough}
                disabled={textObjects.length <= 0}
                checkedChildren={<StrikethroughOutlined/>}
                unCheckedChildren={<StrikethroughOutlined/>}
            />
        </FontStyleContainer>
    </>
}

export default FontStyle;