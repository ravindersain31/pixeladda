import {
    AlignLeftOutlined,
    AlignCenterOutlined,
    AlignRightOutlined,
} from "@ant-design/icons";
import {
    RadioGroup,
    RadioButton
} from "./styled";
import {Label, StyledTooltip} from "../styled.tsx";
import fabric from "@react/editor/canvas/fabric.ts";
import {useContext, useEffect, useState} from "react";
import CanvasContext from "@react/editor/context/canvas.ts";
import {RadioChangeEvent} from "antd/lib";

interface AlignmentProps {
    objects: fabric.Object[];
    optionClassName?: string;
}

const Alignment = ({objects, optionClassName}: AlignmentProps) => {
    const [alignment, setAlignment] = useState(null);

    const canvasContext = useContext(CanvasContext);
    useEffect(() => {
        setAlignment(null);
    }, [objects]);

    const onAlignmentChange = (e: RadioChangeEvent) => {
        const alignment = e.target.value;
        setAlignment(alignment);
        if (objects) {
            for (let object of objects) {
                let offsetLeft = object.left;
                if (alignment === 'left') {
                    offsetLeft = 0;
                } else if (alignment === 'center') {
                    offsetLeft = (canvasContext.canvas.getWidth() / 2) - (object.getScaledWidth() / 2)
                } else if (alignment === 'right') {
                    offsetLeft = canvasContext.canvas.getWidth() - object.getScaledWidth();
                }

                object.left = offsetLeft;
                object.setCoords();
                object.dirty = true;
            }
            canvasContext.canvas.requestRenderAll();
        }
    }

    return <>
        <Label>Align</Label>
        <RadioGroup disabled={objects && objects.length <= 0} value={alignment} onChange={onAlignmentChange}>
            <StyledTooltip title="Align Left">
                <RadioButton className={optionClassName} value="left">
                    <AlignLeftOutlined/>
                </RadioButton>
            </StyledTooltip>
            <StyledTooltip title="Align Center">
                <RadioButton className={optionClassName} value="center">
                    <AlignCenterOutlined/>
                </RadioButton>
            </StyledTooltip>
            <StyledTooltip title="Align Right">
                <RadioButton className={optionClassName} value="right">
                    <AlignRightOutlined/>
                </RadioButton>
            </StyledTooltip>
        </RadioGroup>
    </>
}

export default Alignment;