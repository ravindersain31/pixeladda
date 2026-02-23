import React, { useContext, useRef } from "react";
import {
    Controls,
    RadioButton,
    RadioGroup,
    FrontBackGroup,
    PositionButton,
    CopyButton,
    CanvasNote,
    LoginButton
} from './styled.tsx'
import {RadioChangeEvent} from "antd/lib";
import {useAppDispatch, useAppSelector} from "@react/editor/hook.ts";
import actions from "@react/editor/redux/actions";
import {Sides} from "@react/editor/redux/interface.ts";
import CanvasContext from "@react/editor/context/canvas.ts";
import fabric from "@react/editor/canvas/fabric";
import {message} from "antd";
import { isMobile } from "react-device-detect";
import { CanvasProperties } from "@react/editor/canvas/utils.ts";
import {updateEditorHeading} from "@react/editor/helper/template.ts";
import ReviewedByStamp from "@react/editor/components/common/ReviewedByStamp/index.tsx";
import {getRoles } from "@react/editor/helper/editor.ts";


const roles = getRoles();
const isWholeSeller = roles.includes("ROLE_WHOLE_SELLER");

const isPromoDomain = window.location.href.includes("yardsignpromo");

const ViewControls = () => {
    const hasCopiedRef = useRef(false);

    const config = useAppSelector(state => state.config);

    const canvas = useAppSelector(state => state.canvas);

    const editor = useAppSelector(state => state.editor);

    const [messageApi, contextHolder] = message.useMessage();

    const dispatch = useAppDispatch();

    const canvasContext = useContext(CanvasContext);

    const onChange = (id: number) => {
        if (!config.product.isCustom) {
            // save the canvas data before changing the variant
            dispatch(actions.canvas.updateCanvasData(canvasContext.canvas.toJSON(CanvasProperties)));
        }
        const item = Object.values(editor.items).find((item) => (item.id) === id);
        if (item) {
            dispatch(actions.canvas.updateVariant(item));
            updateEditorHeading(item);
        }
    }

    const onViewChange = (view: string) => {
        if (!(canvasContext.canvas instanceof fabric.Canvas)) return;

        const currentView = canvas.view;
        const otherView = view;
        const canvasData = canvasContext.canvas.toJSON(CanvasProperties);

        if (!hasCopiedRef.current) {
            dispatch(actions.canvas.updateCanvasData(canvasData, currentView));
            dispatch(actions.canvas.updateCanvasData(canvasData, otherView));
            hasCopiedRef.current = true;
        }
        dispatch(actions.canvas.updateView(view, canvasData));
    }

    const onCopyDesign = (currentView: string) => {
        const otherView = currentView === 'front' ? 'back' : 'front';
        if(canvasContext.canvas instanceof fabric.Canvas) {
            const canvasData = canvasContext.canvas.toJSON(CanvasProperties);
            if (canvasData) {
                dispatch(actions.canvas.updateCanvasData(canvasData, currentView));
                dispatch(actions.canvas.updateCanvasData(canvasData, otherView));
                dispatch(actions.canvas.changeUpdateCount());
                dispatch(actions.editor.updateYSPLogoDiscount());
            }
        }
        messageApi.open({
            type: 'success',
            content: currentView === 'front' ? 'Front Design Copied to Back' : 'Back Design Copied to Front',
        })
    }

    return <Controls id="canvas-view-controls">
        {editor.sides === Sides.DOUBLE &&
            <>
                <FrontBackGroup
                    size="large"
                    value={canvas.view}
                    onChange={(e: RadioChangeEvent) => onViewChange(e.target.value)}
                >
                    <PositionButton value="front">FRONT</PositionButton>
                    <PositionButton value="back">BACK</PositionButton>
                </FrontBackGroup>
                <CopyButton onClick={() => onCopyDesign(canvas.view)}>
                    Copy
                    {` ${canvas.view} `}
                    to
                    {canvas.view === 'front' ? ' back ' : ' front '} Design
                </CopyButton>
            </>
        }
        <RadioGroup
            size="large"
            value={canvas.item.id}
            onChange={(e: RadioChangeEvent) => onChange(e.target.value)}
        >
            {Object.entries(editor.items).map(([key, item]) => {
                if (item.quantity <= 0) return null;
                if (item.isWireStake) return null;
                return <RadioButton tabIndex={-1} value={item.id} key={`canvas_preview_control_${item.id}`}>
                    <span className="variant">{item.templateSize.width} x {item.templateSize.height}</span>
                    <span className="quantity">QTY: {item.quantity}</span>
                </RadioButton>;
            })}
        </RadioGroup>
        
        {isPromoDomain && !isWholeSeller && (
            <LoginButton onClick={() => (window.location.href = "/whole-seller-login")}>
                WHOLESALE CLIENTS ONLY: <span className="click-to-login"> CLICK TO LOGIN</span>
            </LoginButton>
        )}

        {!isMobile && <CanvasNote>
            <ReviewedByStamp />
            <span className={!config.product.isCustom ? "text-start" : "text-center"}>
                Order online or call now <a href="tel: +1-877-958-1499">+1-877-958-1499</a>. we will email you a digital proof in 1 hour. once approved, we will begin production.
            </span>
        </CanvasNote>}
        {contextHolder}
    </Controls>
}

export default ViewControls;