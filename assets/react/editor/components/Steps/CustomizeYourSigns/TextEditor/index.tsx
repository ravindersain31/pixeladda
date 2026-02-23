import { Row, Col, Button, Popover, Card } from "antd";
import { isMobile } from "react-device-detect";
import FontFamily from "./FontFamily";
import FontStyle from "./FontStyle";
import ColorPicker from "./ColorPicker";
import AddText from "./AddText";
import Picker from "./ColorPicker/Picker";
import AdditionalNote from "@react/editor/components/AdditionalNote";
import React, { useContext, useEffect, useRef, useState } from "react";
import CanvasContext from "@react/editor/context/canvas.ts";
import fabric from "@react/editor/canvas/fabric.ts";
import { StyledCol } from "./styled.tsx";
import Rotation from "./Rotation";
import useCanvasChange from "@react/editor/plugin/useCanvasChange.ts";
import useHistoryPlugin from "@react/editor/plugin/useHistoryPlugin.ts";
import {
    FontColorsOutlined,
    QuestionCircleOutlined,
    UndoOutlined,
    RedoOutlined
} from "@ant-design/icons";

import {
    FontSize,
    Label,
    ExtraContent,
    TrimWidth,
    QuestionButton,
    PopoverContent,
    StyledContainer,
    UndoRedoButton,
    StyledYSPCol,
    UndoRedoLabel,
    OrderCol,
} from "./styled.tsx"
import { useAppSelector } from "@react/editor/hook.ts";
import DisabledStepMessage from "@react/editor/components/common/DisabledStepMessage/index.tsx";
import YSPLogo from "./YSPLogo/index.tsx";
import TextAlignment from "./TextAlignment/index.tsx";
import AddQrCode from "./AddQrCode";

const TextEditor = () => {

    const [objects, setObjects] = useState<fabric.Object[]>([]);
    const [textObjects, setTextObjects] = useState<fabric.Text[] | fabric.IText[]>([]);
    const [fontSize, setFontSize] = useState<number | string | null>(null);
    const [rotation, setRotation] = useState<number>(0);
    const [lockRotation, setLockRotation] = useState<boolean>(false);
    const canvasContext = useContext(CanvasContext);
    const [trimWidth, setTrimWidth] = useState<number>(0);
    const canvasContent = useContext(CanvasContext);
    const [trimColor, setTrimColor] = useState<string>("#000");
    const [isPickerOpen, setIsPickerOpen] = useState<boolean>(false);
    const [canUndo, setCanUndo] = useState(false);
    const [canRedo, setCanRedo] = useState(false);
    const defaultFontFamily = 'aardvarkcaferegular';
    const [fontFamily, setFontFamily] = useState<string>(defaultFontFamily);

    const editor = useAppSelector((state) => state.editor);
    const canvas = useAppSelector((state) => state.canvas);
    const config = useAppSelector((state) => state.config);

    const historyPluginRef = useRef<ReturnType<typeof useHistoryPlugin>>();

    const popOverContent = "For specific length or width (inches), please leave a comment.";

    useEffect(() => {
        if (canvasContext.canvas instanceof fabric.Canvas) {
            canvasContext.canvas.on('selection:created', onSelection);
            canvasContext.canvas.on('selection:updated', onSelection);
            canvasContext.canvas.on('selection:cleared', onSelection);
            canvasContext.canvas.on('object:modified', onModified);
            canvasContext.canvas.on('text:updating', onModified);
        }
    }, [canvasContext.canvas]);

    useEffect(() => {
        if (canvasContext.canvas instanceof fabric.Canvas) {
            canvasContext.canvas.on('after:render', (event: fabric.IEvent) => {
                canvasContext.canvas.fire("canvas:updated");
            });
            historyPluginRef.current = useHistoryPlugin(canvasContext.canvas);
            canvasContext.canvas.on('canvas:updated', () => {
                if (!historyPluginRef.current) {
                    historyPluginRef.current = useHistoryPlugin(canvasContext.canvas);
                }
                setTimeout(() => {
                    setCanUndo(historyPluginRef.current?.canUndo() || false);
                    setCanRedo(historyPluginRef.current?.canRedo() || false);
                }, 100);
            });
        }
    }, [])


    useEffect(() => {
        historyPluginRef.current?.clear();
    }, [canvas.item.sku, config, canvas.loading]);

    useEffect(() => {
        if (objects.length === 0) {
            setTrimColor("#ddd");
        } else {
            for (const object of objects) {
                if (['text', 'i-text'].includes(object.type as string) && (object instanceof fabric.Text || object instanceof fabric.IText) && object.scaleX && object.scaleY) {
                    const scalingFactor = Math.max(object.scaleX, object.scaleY);
                    setFontSize(Math.round((object.fontSize ?? 0) * scalingFactor) ?? 0);
                }

                let trimColor = object.stroke;
                if (!trimColor && object.fill) {
                    trimColor = object.fill as string;
                }
                setRotation(object.angle ?? 0);
                setLockRotation(object.lockRotation ?? false);
                setTrimColor(object.stroke ?? "#ddd");
                setTrimWidth(object.strokeWidth ?? 0);
            }
        }
    }, [objects]);

    const onModified = (event: fabric.IEvent) => {
        const object = event.target;
        if (object) {
            switch (event.action) {
                case 'rotate':
                    setRotation(object.angle ?? 0);
                    break;
                case 'scale':
                case 'scaleX':
                case 'text:updating':
                case 'scaleY':
                    if (['text', 'i-text'].includes(object.type as string) && (object instanceof fabric.Text || object instanceof fabric.IText) && object.scaleX && object.scaleY) {
                        const scalingFactor = Math.max(object.scaleX, object.scaleY);
                        setFontSize(Math.round((object.fontSize ?? 0) * scalingFactor) ?? 0);
                    }
                    break;
                default:
                    break;
            }
        }
    }

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
            const scalingFactor = textObject.scaleX && textObject.scaleY ? Math.max(textObject.scaleX, textObject.scaleY) : 1;
            textObject.fontSize = Math.round((fontSize || 0) / scalingFactor) || 12;
        });
        setFontSize(fontSize || null);
        canvasContext.canvas.renderAll();
    }

    const onTrimWidthChange = (width: number) => {
        for (const object of objects) {
            object.strokeWidth = width;
            object.dirty = true;
        }
        canvasContent.canvas.requestRenderAll();
        setTrimWidth(width);
        onTrimColorChange(trimColor);
    };

    const onTrimColorChange = (color: string) => {
        for (const object of objects) {
            object.stroke = color;
            object.dirty = true;
        }
        setTrimColor(color);
        if (trimWidth === 0) return;
        canvasContent.canvas.requestRenderAll();
    };

    const onRotationChange = (value: number) => {
        setRotation(value);
        for (const object of objects) {
            object.rotate(value);
            object.dirty = true;
        }
        canvasContent.canvas.requestRenderAll();
    };

    const togglePicker = () => {
        setIsPickerOpen(!isPickerOpen && objects.length > 0);
    };

    const onCanvasChange = () => {
        if (isMobile) {
            setObjects([...objects]);
        }
    }

    useCanvasChange(onCanvasChange);

    const onWindowKeyUp = (event: any) => {
        const historyPlugin = historyPluginRef.current;

        if ((event.ctrlKey || event.metaKey) && event.code === 'KeyZ') {
            historyPlugin?.undo();
            setCanUndo(historyPlugin?.canUndo() || false);
            setCanRedo(historyPlugin?.canRedo() || false);
        }
        if ((event.ctrlKey || event.metaKey) && event.code === 'KeyY') {
            historyPlugin?.redo();
            setCanUndo(historyPlugin?.canUndo() || false);
            setCanRedo(historyPlugin?.canRedo() || false);
        }
    };

    const handleUndo = () => {
        const historyPlugin = historyPluginRef.current;
        historyPlugin?.undo();
        setCanUndo(historyPlugin?.canUndo() || false);
        setCanRedo(historyPlugin?.canRedo() || false);
    };

    const handleRedo = () => {
        const historyPlugin = historyPluginRef.current;
        historyPlugin?.redo();
        setCanUndo(historyPlugin?.canUndo() || false);
        setCanRedo(historyPlugin?.canRedo() || false);
    };

    return (
        <>
            <Row>
                <Col xs={24} sm={24} md={24} lg={24}>
                    <Row>
                        <Col xs={12} sm={12} md={6} lg={4}>
                            <FontFamily textObjects={textObjects} objects={objects} fontFamily={fontFamily} setFontFamily={setFontFamily} />
                        </Col>
                        <Col xs={12} sm={12} md={6} lg={3}>
                            <Label>Font Size</Label>
                            <Popover
                                placement="bottom"
                                color="var(--primary-color)"
                                overlayStyle={{ fontSize: "12px", width: "200px" }}
                                content={<PopoverContent><b>Font Size:</b><br />{popOverContent}</PopoverContent>}
                            >
                                <QuestionButton
                                    shape="circle"
                                    icon={<QuestionCircleOutlined />}
                                />
                            </Popover>
                            <FontSize
                                min={0}
                                value={fontSize}
                                type="text"
                                inputMode="numeric"
                                disabled={textObjects.length === 0}
                                onChange={(value: number | string | null) => onFontSizeChange((value as number))}
                                placeholder="Font Size"
                                onKeyUp={(e: any) => {
                                    if (['Backspace', 'Delete'].includes(e.key) && onFontSizeChange) {
                                        if (e.target.value.length <= 0) {
                                            onFontSizeChange(0);
                                        }
                                    }
                                }}
                                onKeyDown={(e: any) => {
                                    const allowedKeys = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter'];
                                    if ((e.ctrlKey || e.metaKey) && e.key === 'a') {
                                        e.preventDefault();
                                        const inputElement = e.target;
                                        inputElement.select();
                                        return;
                                    }
                                    if (!allowedKeys.includes(e.key)) {
                                        const key = Number(e.key)
                                        if (isNaN(key) || e.key === null || e.key === ' ') {
                                            // Prevent non-numeric characters from being entered.
                                            e.preventDefault();
                                        }
                                    }
                                }}
                                changeOnWheel={false}
                            />
                        </Col>

                        <Col xs={12} sm={8} md={6} lg={5}>
                            <FontStyle optionClassName={isMobile ? 'mobile-device' : ''} textObjects={textObjects || []} objects={objects} />
                        </Col>

                        <Col xs={12} sm={8} md={6} lg={5}>
                            <TextAlignment optionClassName={isMobile ? 'mobile-device' : ''} textObjects={textObjects || []} objects={objects} />
                        </Col>

                        <Col xs={12} sm={6} md={6} lg={4}>
                            <ColorPicker
                                objects={objects}
                                textObjects={textObjects}
                                showTrim={false}
                            />
                        </Col>
                        <OrderCol xs={12} sm={6} md={4} lg={3}>
                            <Row gutter={[4, 4]}>
                                <Col xs={8} sm={12} md={12} lg={12}>
                                    <StyledContainer>
                                        <UndoRedoLabel>Undo</UndoRedoLabel>
                                        <UndoRedoButton
                                            title="Undo Changes"
                                            onClick={handleUndo}
                                            disabled={!canUndo}
                                            icon={<i className="fa fa-undo" aria-hidden="true"></i>}
                                        >
                                        </UndoRedoButton>
                                    </StyledContainer>
                                </Col>
                                <Col xs={8} sm={12} md={12} lg={12}>
                                    <StyledContainer>
                                        <UndoRedoLabel>Redo</UndoRedoLabel>
                                        <UndoRedoButton
                                            title="Redo Changes"
                                            onClick={handleRedo}
                                            disabled={!canRedo}
                                            icon={<i className="fas fa-redo"></i>}
                                        >
                                        </UndoRedoButton>
                                    </StyledContainer>
                                </Col>
                            </Row>
                        </OrderCol>
                    </Row>
                    <Row gutter={[4, 4]} className="mt-1">
                        <Col xs={4} sm={4} md={2} lg={2}>
                            <Label>Trim</Label>
                            <Picker
                                disabled={objects.length <= 0}
                                value={trimColor}
                                icon={<FontColorsOutlined />}
                                onChange={(value) => onTrimColorChange(value.toHexString?.() || '#000')}
                                open={isPickerOpen}
                                onOpenChange={togglePicker}
                            />
                        </Col>
                        <Col xs={10} sm={10} md={4} lg={4}>
                            <Label>Trim Width</Label>
                            <TrimWidth
                                min={0}
                                max={10}
                                disabled={objects.length <= 0}
                                value={trimWidth}
                                onChange={(value: number) => onTrimWidthChange(value)}
                            />
                        </Col>
                        <StyledCol xs={10} sm={10} md={4} lg={4}>
                            <Label>Rotation</Label>
                            <Rotation
                                disabled={objects.length <= 0 || lockRotation}
                                value={rotation}
                                onChange={onRotationChange}
                            />
                        </StyledCol>
                        <StyledYSPCol xs={12} sm={6} md={8} lg={7} xl={5}>
                            <StyledContainer>
                                <YSPLogo />
                            </StyledContainer>
                        </StyledYSPCol>
                        <StyledYSPCol xs={12} sm={6} md={6} lg={7} xl={4}>
                            <StyledContainer>
                                <AddQrCode />
                            </StyledContainer>
                        </StyledYSPCol>
                        <Col xs={24} sm={24} md={24} lg={14}>
                            <AddText textObjects={textObjects} objects={objects} fontFamily={fontFamily} />
                        </Col>
                    </Row>
                </Col>
            </Row>
            {!config.product.isCustom && <AdditionalNote showNeedAssistance={isMobile} />}
            {/* {editor.totalQuantity <= 0 && <DisabledStepMessage />} */}
        </>
    );
}

export default TextEditor;