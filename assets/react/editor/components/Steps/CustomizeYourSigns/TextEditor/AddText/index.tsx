import {
    AddTextButton,
    AddTextContainer,
    AddTextInput,
} from "./styled";
import { Label } from "../styled.tsx";
import { useContext, useEffect, useState } from "react";
import CanvasContext from "@react/editor/context/canvas.ts";
import fabric from "@react/editor/canvas/fabric.ts";
import { isMobile } from "react-device-detect";
import { isObjectPartiallyOutsideCanvas } from "@react/editor/canvas/fitObjectsToCanvas.ts";
import { deselectAllObjects } from "@react/editor/canvas/utils.ts";

interface AddTextProps {
    textObjects: fabric.Text[] | fabric.IText[];
    objects: fabric.Object[];
    fontFamily: string;
}

const AddText = ({ textObjects, objects, fontFamily }: AddTextProps) => {
    const canvasContext = useContext(CanvasContext);

    const [isEdit, setIsEdit] = useState(false);
    const [text, setText] = useState('');

    useEffect(() => {
        if (canvasContext.canvas instanceof fabric.Canvas) {
            canvasContext.canvas.on('object:modified', onObjectModified);
            canvasContext.canvas.on("text:changed", onObjectModified);
            canvasContext.canvas.on('text:editing:exited', onTextEditingExited);
            canvasContext.canvas.on('mouse:down', convertText);
            canvasContext.canvas.on("text:editing:entered", onTextEditingEntered);
        }

        return () => {
            if (canvasContext.canvas instanceof fabric.Canvas) {
                canvasContext.canvas.off('object:modified', onObjectModified);
                canvasContext.canvas.off("text:changed", onObjectModified);
                canvasContext.canvas.off('mouse:down', convertText);
                canvasContext.canvas.off("text:editing:entered", onTextEditingEntered);
            }
        };
    }, [canvasContext.canvas]);

    useEffect(() => {
        const handleCardBodyClick = (event: MouseEvent) => {
            const target = event.target as HTMLElement;
            if (target.classList.contains("add-to-cart") || target.closest(".add-to-cart")) {
                deselectAllObjects(canvasContext.canvas);
            }
        };
        document.addEventListener("click", handleCardBodyClick, true);

        return () => {
            document.removeEventListener("click", handleCardBodyClick);
        };
    }, [canvasContext.canvas]);


    useEffect(() => {
        if (textObjects.length === 0) {
            setIsEdit(false);
            setText('');
        } else {
            const [selectedTextObject] = textObjects;

            if (selectedTextObject) {
                setIsEdit(true);
                setText(selectedTextObject.text || '');
            }
        }
    }, [textObjects]);

    const onTextEditingExited = (event: fabric.IEvent) => {
        const object = event.target as fabric.IText | fabric.Text;
        if (object.type === 'text' || object.type === 'i-text') {
            setIsEdit(false);
        }
    }

    const convertTextToIText = (textObject: fabric.Text) => {
        const canvas = canvasContext.canvas;

        if (textObject instanceof fabric.Text && !(textObject instanceof fabric.IText)) {
            const { text, left, top, fontFamily, fill, fontSize, textAlign, scaleX, scaleY, width, height } = textObject;

            const iTextObject = new fabric.IText(text || '', {
                left,
                top,
                fontFamily,
                fill,
                fontSize,
                width,
                height,
                textAlign: textAlign || 'left',
                editable: true,
                backgroundColor: 'rgba(255, 255, 255, 0.003)',
                stroke: textObject.stroke,
                strokeWidth: textObject.strokeWidth,
                angle: textObject.angle,
                lineHeight: textObject.lineHeight,
                charSpacing: textObject.charSpacing,
                fontWeight: textObject.fontWeight,
                fontStyle: textObject.fontStyle,
                underline: textObject.underline,
                overline: textObject.overline,
                linethrough: textObject.linethrough,
                originX: textObject.originX,
                originY: textObject.originY,
                padding: textObject.padding,
                scaleX: scaleX ?? 1,
                scaleY: scaleY ?? 1,
            });

            iTextObject.initDimensions();
            if (canvas.getObjects().includes(textObject)) {
                canvas.remove(textObject);
            }

            canvas.add(iTextObject);
            canvas.setActiveObject(iTextObject);
            canvas.requestRenderAll();
            return iTextObject;
        }
        return textObject;
    };

    const convertText = (event: fabric.IEvent) => {
        const target = event.target as fabric.Text | fabric.IText;
        if (!target) return;

        if (target.type === 'text') {
            // Prevent double firing during conversion
            if ((target as any)._converting) {
                return;
            }
            (target as any)._converting = true;
            convertTextToIText(target as fabric.Text);

            requestAnimationFrame(() => {
                delete (target as any)._converting;
            });
        }
    };

    const onTextEditingEntered = (event: fabric.IEvent) => {
        const iTextObj = event.target as fabric.IText;
        if (iTextObj.hiddenTextarea) {
            requestAnimationFrame(() => {

                blurAllFocusable();

                iTextObj.hiddenTextarea?.setAttribute("tabindex", "-1");
                iTextObj.hiddenTextarea?.focus({ preventScroll: true });
            });
        }
    }

    function blurAllFocusable() {
        const focusableElements = document.querySelectorAll<HTMLElement>(
            'a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])'
        );

        focusableElements.forEach(el => {
            el.setAttribute('tabindex', '-1');
            el.blur();
        });
    }

    const onObjectModified = (event: fabric.IEvent) => {
        const object = event.target as fabric.IText | fabric.Text;
        if (object.type === 'text' || object.type === 'i-text') {
            setText(object.text || '');
            setIsEdit(true);
            adjustFontSizeToFit(object);
        }

    }

    const adjustFontSizeToFit = (textObject: fabric.IText | fabric.Text) => {
        const canvas = canvasContext.canvas;
        const canvasWidth = canvas.getWidth();
        const canvasHeight = canvas.getHeight();
        const MIN_FONT_SIZE = 1;
        let rect = textObject.getBoundingRect(true);
        while (
            isObjectPartiallyOutsideCanvas(textObject, canvasWidth, canvasHeight) &&
            textObject.fontSize! > MIN_FONT_SIZE
        ) {
           
            if (textObject.fontSize! - 1 < MIN_FONT_SIZE) break;

            textObject.set({ fontSize: textObject.fontSize! - 1 });
            textObject.setCoords();
            rect = textObject.getBoundingRect(true);

            canvas.fire("text:updating", { target: textObject, action: "text:updating" });
            canvas.requestRenderAll();
        }

        const offsetLeft = Math.min(0, rect.left) || Math.max(0, rect.left + rect.width - canvasWidth);
        const offsetTop = Math.min(0, rect.top) || Math.max(0, rect.top + rect.height - canvasHeight);

        if (offsetLeft || offsetTop) {
        textObject.set({
            left: textObject.left! - (offsetLeft > 0 ? offsetLeft : 0),
            top: textObject.top! - (offsetTop > 0 ? offsetTop : 0),
        });
            textObject.setCoords();
        }

        canvas.requestRenderAll();
    };

    const onTextChange = (text: string) => {
        setText(text);
        if (text.trim() === "") {
            if (isEdit && canvasContext.canvas.getActiveObject() instanceof fabric.IText) {
                const activeObject = canvasContext.canvas.getActiveObject() as fabric.IText;
                canvasContext.canvas.remove(activeObject);
                setIsEdit(false);
                canvasContext.canvas.requestRenderAll();
            }
            return;
        }
        if (isEdit) {
            const [selectedTextObject] = textObjects;

            if (selectedTextObject) {
                selectedTextObject.text = text.trim();
                adjustFontSizeToFit(selectedTextObject);
                selectedTextObject.dirty = true;
                canvasContext.canvas.requestRenderAll();
            }
        } else {
            const newText = new fabric.IText(text.trim(), {
                fontFamily: fontFamily || 'arial',
                fill: '#000000',
                fontSize: 50,
                backgroundColor: 'rgba(255, 255, 255, 0.003)',
                centeredScaling: true,
                left: canvasContext.canvas.getWidth() / 2,
                top: canvasContext.canvas.getHeight() / 2,
            });

            canvasContext.canvas.add(newText);
            canvasContext.canvas.setActiveObject(newText);
            setIsEdit(true);

            adjustFontSizeToFit(newText);
        }
        canvasContext.canvas.requestRenderAll();
    };

    const onAddText = () => {
        setText('');
        setIsEdit(false);
        canvasContext.canvas.discardActiveObject();
        canvasContext.canvas.requestRenderAll();
    };

    return (
        <>
            {!isMobile && <Label>{isEdit ? 'Edit' : 'Add'} Text</Label>}
            <AddTextContainer>
                <AddTextInput
                    size="small"
                    value={text}
                    onChange={(e: any) => onTextChange(e.target.value)}
                    placeholder="Add Text"
                />
                <AddTextButton type="primary" onClick={onAddText}>
                    Add Text
                </AddTextButton>
            </AddTextContainer>
        </>
    );
};

export default AddText;