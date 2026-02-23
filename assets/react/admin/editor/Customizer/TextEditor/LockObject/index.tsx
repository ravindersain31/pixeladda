import { LockOutlined, UnlockOutlined } from "@ant-design/icons";
import { Label } from "../styled.tsx";
import fabric from "@react/admin/editor/canvas/fabric.ts";
import { useContext, useEffect, useState } from "react";
import CanvasContext from "@react/admin/editor/context/canvas.ts";
import { Button, Col } from "antd";
import { LockObjectContainer, LockButton } from "./styled.tsx";
import { lockAttrs } from "@react/admin/editor/canvas/utils.ts";

interface ObjectProps {
    objects: fabric.Object[];
}

const LockObject = ({ objects }: ObjectProps) => {
    const [isLock, setIsLock] = useState(false);
    const [selectedObject, setSelectedObject] = useState<fabric.Object | null>(null);
    const canvasContext = useContext(CanvasContext);

    useEffect(() => {
        const { canvas } = canvasContext;

        const handleCanvasChange = () => {
            // Add logic to handle canvas changes if needed
        };

        const onSelection = (e: any) => {
            const object = e.selected ? e.selected[0] : null;
            setSelectedObject(object);
            setIsLock(object ? !object.hasControls : false);
        };

        canvas.on("object:added", handleCanvasChange);
        canvas.on("object:modified", handleCanvasChange);
        canvas.on("selection:created", onSelection);
        canvas.on("selection:updated", onSelection);
        canvas.on("selection:cleared", onSelection);

        return () => {
            canvas.off("object:added", handleCanvasChange);
            canvas.off("object:modified", handleCanvasChange);
            canvas.off("selection:created", onSelection);
            canvas.off("selection:updated", onSelection);
            canvas.off("selection:cleared", onSelection);
        };
    }, [canvasContext]);

    useEffect(() => {
        if (objects.length === 0) {
            setIsLock(false);
        } else {
            const lockStatus = objects.every((object) => !object.hasControls);
            setIsLock(lockStatus);
        }
        canvasContext.canvas.requestRenderAll();
    }, [objects, canvasContext.canvas]);

    const toggleLock = () => {
        if (selectedObject) {
            lockAttrs.forEach((attr) => {
                selectedObject[attr] = !selectedObject[attr];
            });
            selectedObject.dirty = true;
            canvasContext.canvas.selection = !isLock;
            setIsLock(!isLock);
            canvasContext.canvas.requestRenderAll();
        }
    };

    return (
        <Col xs={24} md={5} style={{ textAlign: "center" }}>
            <Label>Lock</Label>
            <LockObjectContainer>
                <LockButton
                    onClick={toggleLock}
                    $isLock={isLock}
                    disabled={!selectedObject}
                >
                    {isLock ? <LockOutlined /> : <UnlockOutlined />}
                </LockButton>
            </LockObjectContainer>
        </Col>
    );
};

export default LockObject;
