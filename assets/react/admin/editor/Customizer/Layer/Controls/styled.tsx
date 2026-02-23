import styled from "styled-components";
import Button from "@react/admin/editor/components/Button";

export const ControlsWrapper = styled.div`
    display: flex;
    justify-content: start;
    align-items: center;
    margin: 10px 0;
    flex-wrap: wrap;
`;

const ControlButton = styled(Button)`
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 13px !important;
    padding: 5px 10px;
    margin-bottom: 5px;
    margin-right: 5px;

    .anticon {
        font-size: 16px;
        margin-right: -5px;
    }
`;

export const BringToFront = styled(ControlButton)`
`;

export const SendToBack = styled(ControlButton)`
`;

export const BringForward = styled(ControlButton)`
    .anticon.anticon-rollback {
        transform: rotate(180deg);
    }
`;

export const SendBackwards = styled(ControlButton)`
`;