import { StyledSelect, SelectLabel } from './styled';
import { Label } from '../styled';
import fonts from '@react/editor/fonts.json';
import { useContext, useEffect, useState } from "react";
import CanvasContext from "@react/editor/context/canvas.ts";

interface FontFamilyProps {
    textObjects: fabric.Text[] | fabric.IText[];
    objects: fabric.Object[];
    fontFamily: string;
    setFontFamily: (fontFamily: string) => void;
}

const FontFamily = ({ textObjects, objects, fontFamily, setFontFamily }: FontFamilyProps) => {


    const canvasContext = useContext(CanvasContext);

    useEffect(() => {
        if (textObjects.length > 0) {
            const lastFont = textObjects[textObjects.length - 1].fontFamily || 'arial';
            setFontFamily(lastFont);
        }
    }, [textObjects, objects]);

    const onFontFamilyChange = (value: string) => {
        if (textObjects.length <= 0) return;
        for (const object of textObjects) {
            object.fontFamily = value;
            object.dirty = true;
        }
        canvasContext.canvas.requestRenderAll();
        setFontFamily(value);
    }

    return <>
        <Label>Font Family</Label>
        <StyledSelect
            placeholder="Font Family"
            showSearch
            value={fontFamily}
            disabled={textObjects.length <= 0}
            onChange={(value: any) => onFontFamilyChange(value)}
            options={fonts.map((font) => ({
                label: <SelectLabel data-family={font.family.join(', ')}>{font.name}</SelectLabel>,
                value: font.family.join(', '),
                className: 'font-option',
            }))}
        />
    </>
}

export default FontFamily;