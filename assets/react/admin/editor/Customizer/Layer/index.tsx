import { useContext, useEffect, useState } from "react";
import CanvasContext from "@react/admin/editor/context/canvas.ts";
import fabric from "@react/admin/editor/canvas/fabric.ts";
import Controls from "./Controls";
import { LayerWrapper, LayerItem, SelectBackground } from "./styled.tsx";
import Icon from "./Icon.tsx";
import { LockButton } from "../TextEditor/LockObject/styled.tsx";
import { BgColorsOutlined, LockOutlined, UnlockOutlined } from "@ant-design/icons";
import { lockAttrs } from "../../canvas/utils.ts";

const Layer = () => {
    const canvasContext = useContext(CanvasContext);

    const [objects, setObjects] = useState<fabric.Object[]>([]);
    const [activeObject, setActiveObject] = useState<fabric.Object | null>(null);

    useEffect(() => {
        canvasContext.canvas.on('object:added', onCanvasChange);
        canvasContext.canvas.on('object:modified', onCanvasChange);
        canvasContext.canvas.on('selection:updated', onSelected);

        setObjects(canvasContext.canvas._objects);

        return () => {
            canvasContext.canvas.off('object:added', onCanvasChange);
            canvasContext.canvas.off('object:modified', onCanvasChange);
            canvasContext.canvas.off('selection:updated', onSelected);
        };
    }, [canvasContext.canvas]);

    const onCanvasChange = () => {
        setObjects([...canvasContext.canvas.getObjects()]);
    }

    const onSelected = (e: any) => {
        if (e.selected.length === 1) {
            setActiveObject(e.selected[0]);
        } else {
            setActiveObject(null);
        }
    }

    const selectLayer = (object: fabric.Object) => {
        canvasContext.canvas.setActiveObject(object);
        canvasContext.canvas.requestRenderAll();
    }

    const toggleLock = (selectedObject: fabric.Object) => {
        lockAttrs.forEach((attr) => {
            selectedObject[attr] = !selectedObject[attr];
        });
        selectedObject.dirty = true;
        canvasContext.canvas.requestRenderAll();
        onCanvasChange();
    }

    const setBackground = (selectedObject: fabric.Object) => {
        if (selectedObject.custom?.type === 'background') {
            selectedObject.custom = undefined;
        } else {
            objects.forEach((obj) => {
                if (obj.custom?.type === 'background') {
                    obj.custom = undefined;
                }
            });
            selectedObject.custom = { type: 'background' };
        }

        selectedObject.dirty = true;

        canvasContext.canvas.requestRenderAll();
        onCanvasChange();
    };

    return (
        <LayerWrapper>
            <h5>Layers</h5>
            <Controls onChange={onCanvasChange} objects={objects} />
            <div className="layer-list">
                {objects.map((object: any, index) => {
                    return (
                        <LayerItem
                            className={activeObject === object ? 'active' : ''}
                            key={`${index}_${object.type}`}
                            onClick={() => selectLayer(object)}
                        >
                            <div className="me-1">{index + 1}.</div>
                            <div className="icon"><Icon type={object.type} /></div>
                            <div>
                                {object.type}
                                {object.type === 'text' && `: ${object.text}`}
                            </div>
                            <LockButton onClick={(e: any) => {
                                e.stopPropagation();
                                toggleLock(object);
                            }} $isLock={!object.hasControls}>
                                {!object.hasControls ? <LockOutlined /> : <UnlockOutlined />}
                            </LockButton>
                            <SelectBackground onClick={(e: any) => {
                                e.stopPropagation();
                                setBackground(object)
                            }}
                                $isBackground={object.custom?.type === 'background'}>
                                <BgColorsOutlined />
                            </SelectBackground>
                        </LayerItem>
                    );
                })}
            </div>
        </LayerWrapper>
    );
}

export default Layer;