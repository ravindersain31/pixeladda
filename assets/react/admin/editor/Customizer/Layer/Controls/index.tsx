import {
    RollbackOutlined,
    VerticalAlignTopOutlined,
    VerticalAlignBottomOutlined,
    ColumnWidthOutlined
} from '@ant-design/icons';
import {
    ControlsWrapper,
    SendToBack,
    BringToFront,
    SendBackwards,
    BringForward
} from "./styled.tsx";
import CanvasContext from "@react/admin/editor/context/canvas.ts";
import {useContext, useEffect, useState} from "react";
import { lockAttrs } from '@react/admin/editor/canvas/utils.ts';
import { LockOutlined, UnlockOutlined } from "@ant-design/icons";

interface Props {
    onChange: () => void
    objects: fabric.Object[]
}

const Controls = ({ onChange, objects }: Props) => {
    const [isLock, setIsLock] = useState(false);

    const canvasContext = useContext(CanvasContext);

    useEffect(() => {
        if (objects.length === 0) {
            setIsLock(false);
        } else {
            const isAllLocked = objects.every((object) => object.hasControls);
            canvasContext.canvas.selection = isAllLocked;
        }
        canvasContext.canvas.requestRenderAll();
    }, [objects]);

    const sendToBack = () => {
        if (canvasContext.canvas && canvasContext.canvas.getActiveObject()) {
            // @ts-ignore
            canvasContext.canvas.sendToBack(canvasContext.canvas.getActiveObject());
            canvasContext.canvas.requestRenderAll();
            onChange();
        }
    }

    const bringToFront = () => {
        if (canvasContext.canvas && canvasContext.canvas.getActiveObject()) {
            // @ts-ignore
            canvasContext.canvas.bringToFront(canvasContext.canvas.getActiveObject());
            canvasContext.canvas.requestRenderAll();
            onChange();
        }
    }

    const sendBackwards = () => {
        if (canvasContext.canvas && canvasContext.canvas.getActiveObject()) {
            // @ts-ignore
            canvasContext.canvas.sendBackwards(canvasContext.canvas.getActiveObject());
            canvasContext.canvas.requestRenderAll();
            onChange();
        }
    }

    const bringForward = () => {
        if (canvasContext.canvas && canvasContext.canvas.getActiveObject()) {
            // @ts-ignore
            canvasContext.canvas.bringForward(canvasContext.canvas.getActiveObject());
            canvasContext.canvas.requestRenderAll();
            onChange();
        }
    }

    const flipObject = () => {
        if (canvasContext.canvas && canvasContext.canvas.getActiveObject()) {
            // @ts-ignore
            canvasContext.canvas.getActiveObject().set('flipX', !canvasContext.canvas.getActiveObject().get('flipX'));
            canvasContext.canvas.requestRenderAll();
            onChange();
        }
    }

    const toggleLock = () => {
        objects.forEach((object) => {
            lockAttrs.forEach((attr) => {
                object[attr] = !object[attr];
            });
            object.dirty = true;
        });
        canvasContext.canvas.selection = !isLock;
        setIsLock(!isLock);
        canvasContext.canvas.requestRenderAll();
        onChange();
    };

    return (
        <ControlsWrapper>
            <SendToBack type="default" onClick={sendToBack}>
                <VerticalAlignTopOutlined />
                <span>Sent To Back</span>
            </SendToBack>
            <BringToFront type="default" onClick={bringToFront}>
                <VerticalAlignBottomOutlined />
                <span>Bring To Front</span>
            </BringToFront>
            <SendBackwards type="default" onClick={flipObject}>
                <ColumnWidthOutlined />
                <span>Flip Object</span>
            </SendBackwards>
            <SendBackwards type="default" onClick={sendBackwards}>
                <RollbackOutlined />
                <span>Send Backwards</span>
            </SendBackwards>
            <BringForward type="default" onClick={bringForward}>
                <RollbackOutlined />
                <span>Bring Forward</span>
            </BringForward>
            <BringForward type="default" onClick={toggleLock}>
                {isLock ? <LockOutlined /> : <UnlockOutlined />}
                <span>{isLock ? 'Unlock All' : 'Lock All'}</span>
            </BringForward>
        </ControlsWrapper>
    );
}

export default Controls;
