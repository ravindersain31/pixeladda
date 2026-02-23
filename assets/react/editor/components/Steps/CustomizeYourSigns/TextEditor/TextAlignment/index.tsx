import { useContext, useEffect, useState } from "react";
import CanvasContext from "@react/editor/context/canvas.ts";
import { fabric } from "fabric";
import { AlignLeftOutlined, AlignCenterOutlined, AlignRightOutlined } from "@ant-design/icons";
import { TextAlignmentContainer, AlignLeft, AlignCenter, AlignRight } from "./styled.tsx";
import { Label } from "../styled.tsx";

interface CanvasContextType {
    canvas: fabric.Canvas;
}

interface TextAlignmentProps {
    textObjects: fabric.Text[] | fabric.IText[];
    objects: fabric.Object[];
    optionClassName?: string;
}

const TextAlignment: React.FC<TextAlignmentProps> = ({ textObjects, objects, optionClassName }) => {
    const [alignLeft, setAlignLeft] = useState(false);
    const [alignCenter, setAlignCenter] = useState(false);
    const [alignRight, setAlignRight] = useState(false);

    const canvasContext = useContext(CanvasContext) as CanvasContextType;

    useEffect(() => {
        if (textObjects.length === 0 || !canvasContext.canvas) {
            setAlignLeft(false);
            setAlignCenter(false);
            setAlignRight(false);
            return;
        }

        const canvasWidth = canvasContext.canvas.getWidth();
        const canvasHeight = canvasContext.canvas.getHeight();
        const firstObject = textObjects[0]; // Use the first selected object
        const boundingRect = firstObject.getBoundingRect();
        const width = firstObject.width || 0;
        const scaleX = firstObject.scaleX || 1;
        const objectWidth = width * scaleX;
        const left = firstObject.left || 0;
        const angle = firstObject.angle || 0;
        const textAlign = firstObject.textAlign;

        const effectiveLeft = angle ? left - (objectWidth * Math.abs(Math.cos(angle * Math.PI / 180))) / 2 : left;

        const isWithinBounds =
            boundingRect.left >= 0 &&
            boundingRect.left + boundingRect.width <= canvasWidth &&
            boundingRect.top >= 0 &&
            boundingRect.top + boundingRect.height <= canvasHeight;

        setAlignLeft(false);
        setAlignCenter(false);
        setAlignRight(false);

        if (isWithinBounds) {
            if (objectWidth >= canvasWidth - 20) {
                setAlignCenter(true);
            } else if (textAlign) {
                if (textAlign === "left" && Math.abs(effectiveLeft) < 10) setAlignLeft(true);
                else if (textAlign === "center" && Math.abs(effectiveLeft - (canvasWidth / 2) + (objectWidth / 2)) < 10) setAlignCenter(true);
                else if (textAlign === "right" && Math.abs(effectiveLeft + objectWidth - canvasWidth) < 10) setAlignRight(true);
            } else {
                const tolerance = 10;
                if (Math.abs(effectiveLeft) < tolerance) setAlignLeft(true);
                else if (Math.abs(effectiveLeft - (canvasWidth / 2) + (objectWidth / 2)) < tolerance) setAlignCenter(true);
                else if (Math.abs(effectiveLeft + objectWidth - canvasWidth) < tolerance) setAlignRight(true);
            }
        }
    }, [textObjects, objects, canvasContext.canvas]);

    const onAlignLeft = () => {
        if (textObjects.length <= 0 || !canvasContext.canvas) return;
        const canvasWidth = canvasContext.canvas.getWidth();
        const canvasHeight = canvasContext.canvas.getHeight();

        textObjects.forEach(object => {
            const boundingRect = object.getBoundingRect();
            let newLeft = 0;
            let newTop = object.top || 0;

            if (newTop + boundingRect.height > canvasHeight) newTop = canvasHeight - boundingRect.height;
            if (newTop < 0) newTop = 0;

            object.set({
                textAlign: "left",
                left: newLeft,
                top: newTop
            });
            object.setCoords();
        });
        setAlignLeft(true);
        setAlignCenter(false);
        setAlignRight(false);
        canvasContext.canvas.requestRenderAll();
    };

    const onAlignCenter = () => {
        if (textObjects.length <= 0 || !canvasContext.canvas) return;
        const canvasWidth = canvasContext.canvas.getWidth();
        const canvasHeight = canvasContext.canvas.getHeight();

        textObjects.forEach(object => {
            const width = object.width || 0;
            const scaleX = object.scaleX || 1;
            const calculatedWidth = width * scaleX;
            const boundingRect = object.getBoundingRect();
            let newLeft = canvasWidth / 2 - calculatedWidth / 2;
            let newTop = object.top || 0;

            if (newLeft < 0) newLeft = 0;
            if (newLeft + boundingRect.width > canvasWidth) newLeft = canvasWidth - boundingRect.width;
            if (newTop + boundingRect.height > canvasHeight) newTop = canvasHeight - boundingRect.height;
            if (newTop < 0) newTop = 0;

            object.set({
                textAlign: "center",
                left: newLeft,
                top: newTop
            });
            object.setCoords();
        });
        setAlignLeft(false);
        setAlignCenter(true);
        setAlignRight(false);
        canvasContext.canvas.requestRenderAll();
    };

    const onAlignRight = () => {
        if (textObjects.length <= 0 || !canvasContext.canvas) return;
        const canvasWidth = canvasContext.canvas.getWidth();
        const canvasHeight = canvasContext.canvas.getHeight();

        textObjects.forEach(object => {
            const width = object.width || 0;
            const scaleX = object.scaleX || 1;
            const calculatedWidth = width * scaleX;
            const boundingRect = object.getBoundingRect();
            let newLeft = canvasWidth - calculatedWidth;
            let newTop = object.top || 0;

            if (newLeft < 0) newLeft = 0;
            if (newLeft + boundingRect.width > canvasWidth) newLeft = canvasWidth - boundingRect.width;
            if (newTop + boundingRect.height > canvasHeight) newTop = canvasHeight - boundingRect.height;
            if (newTop < 0) newTop = 0;

            object.set({
                textAlign: "right",
                left: newLeft,
                top: newTop
            });
            object.setCoords();
        });
        setAlignLeft(false);
        setAlignCenter(false);
        setAlignRight(true);
        canvasContext.canvas.requestRenderAll();
    };

    return (
        <>
            <Label>Text Alignment</Label>
            <TextAlignmentContainer>
                <AlignLeft
                    className={optionClassName}
                    onChange={onAlignLeft}
                    checked={alignLeft}
                    disabled={textObjects.length <= 0}
                    checkedChildren={<AlignLeftOutlined />}
                    unCheckedChildren={<AlignLeftOutlined />}
                />
                <AlignCenter
                    className={optionClassName}
                    onChange={onAlignCenter}
                    checked={alignCenter}
                    disabled={textObjects.length <= 0}
                    checkedChildren={<AlignCenterOutlined />}
                    unCheckedChildren={<AlignCenterOutlined />}
                />
                <AlignRight
                    className={optionClassName}
                    onChange={onAlignRight}
                    checked={alignRight}
                    disabled={textObjects.length <= 0}
                    checkedChildren={<AlignRightOutlined />}
                    unCheckedChildren={<AlignRightOutlined />}
                />
            </TextAlignmentContainer>
        </>
    );
};

export default TextAlignment;