import { Col, Row } from "antd";
import { FontColorsOutlined, BgColorsOutlined } from '@ant-design/icons';
import Picker from "./Picker";
import { Label, TrimWidth, StyledCol } from "./styled.tsx";
import { useContext, useEffect, useState } from "react";
import CanvasContext from "@react/editor/context/canvas.ts";
import fabric from "@react/editor/canvas/fabric.ts";
import { isMobile } from "react-device-detect";

interface ColorPickersProps {
    objects: fabric.Object[];
    textObjects: fabric.Text[] | fabric.IText[];
    showTrim: boolean;
}

const ColorPickers = ({ objects, textObjects, showTrim }: ColorPickersProps) => {
    const canvasContent = useContext(CanvasContext);

    const [backgroundColor, setBackgroundColor] = useState<string>('#ddd');
    const [isPickerOpen, setIsPickerOpen] = useState<boolean>(false);

    const [fillColor, setFillColor] = useState<string>('#ddd');

    useEffect(() => {
        if (objects.length === 0) {
            setFillColor('#ddd');
        } else {
            for (const object of objects) {
                let fillColor = object.fill as string;
                if (['image'].includes(object.type as string)) {
                    const image = object as fabric.Image;
                    for (const filter of image.filters as any || []) {
                        if (filter.mode === 'overlay') {
                            fillColor = filter.color;
                        }
                    }
                }
                setFillColor(fillColor);
            }
        }
    }, [objects]);

    useEffect(() => {
        const backgroundObj = canvasContent.canvas._objects.find(obj => obj.custom?.type === 'background');

        if (backgroundObj) {
            if (backgroundObj.type === 'image') {
                const image = backgroundObj as fabric.Image;
                let backgroundColor = '#ddd';
                if (Array.isArray(image.filters)) {
                    for (const filter of image.filters as any || []) {
                        if (filter.mode === 'overlay') {
                            backgroundColor = filter.color;
                            break;
                        }
                    }
                }
                setBackgroundColor(backgroundColor);
            }
            else {
                setBackgroundColor(backgroundObj.fill as string || '#ddd');
            }
        } else {
            setBackgroundColor(canvasContent.canvas.backgroundColor as string || '#ddd');
        }
    }, [canvasContent.canvas, objects]);

    const onBackgroundChange = (color: string) => {
        const backgroundObj = canvasContent.canvas._objects.find(obj => obj.custom?.type === 'background');

        if (backgroundObj) {
            if (backgroundObj.type === 'image') {
                const image = backgroundObj as fabric.Image;
                image.filters = [
                    new fabric.Image.filters.BlendColor({
                        mode: 'overlay',
                        color: color,
                        alpha: 1,
                    })
                ];
                image.applyFilters();
            } else {
                backgroundObj.fill = color;
                backgroundObj.dirty = true;
            }
        } else {
            canvasContent.canvas.setBackgroundColor(color, () => {
                canvasContent.canvas.requestRenderAll();
            });
        }

        canvasContent.canvas.requestRenderAll();
        setBackgroundColor(color);
    };


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

    const togglePicker = () => {
        setIsPickerOpen(!isPickerOpen && objects.length > 0);
    };

    const getLabel = (activeObject: fabric.Object) => {
        const excludedTypes = ['path', 'activeSelection', 'rect', 'background'];
        const excludedCustomTypes = ['background', 'custom-design']
        const textTypes = ['text', 'i-text'];
        if (activeObject?.custom?.type) {
            if (excludedCustomTypes.includes(activeObject.custom.type)) {
                return ''
            };
            return activeObject.custom.type.charAt(0).toUpperCase() + activeObject.custom.type.slice(1);
        }
        if (activeObject?.type) {
            if (excludedTypes.includes(activeObject.type)) {
                return '';
            }
            return textTypes.includes(activeObject.type) ? 'Text' : activeObject.type.charAt(0).toUpperCase() + activeObject.type.slice(1);
        }
        return '';
    };

    useEffect(() => {
        const handleClick = (event: MouseEvent) => {
            const target = event.target as HTMLElement;             
            if (!target.closest('.custom-lightgallery, .swiper, .lightgallery-item')) return;
            setIsPickerOpen(false);
        };
    
        document.addEventListener('click', handleClick);
        return () => {
            document.removeEventListener('click', handleClick);
        };
    }, []); 

    return (
        <>
            <Row>
                <StyledCol xs={8} sm={8} md={8} lg={8}>
                    <Label>{"BG"}</Label>
                    <Picker
                        value={backgroundColor}
                        icon={<BgColorsOutlined />}
                        onChange={(value) => onBackgroundChange(value.toHexString())}
                        placement={'bottom'}
                    />
                </StyledCol>
                <StyledCol xs={16} sm={16} md={16} lg={16}>
                    <Label>{getLabel(canvasContent.canvas._activeObject)} Color</Label>
                    <Picker
                        disabled={(objects.length === 0 || canvasContent.canvas._activeObject?.custom?.type === 'background')}
                        value={fillColor}
                        icon={<FontColorsOutlined />}
                        onChange={(value) => onFillColorChange(value.toHexString())}
                        open={isPickerOpen}
                        onOpenChange={togglePicker}
                        placement={'bottom'}
                    />
                </StyledCol>
            </Row>
        </>
    )
}

export default ColorPickers;