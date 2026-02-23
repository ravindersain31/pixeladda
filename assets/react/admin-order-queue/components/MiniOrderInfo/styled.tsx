import { Button } from "antd";
import styled from "styled-components";

export const MiniOrderInfoWrapper = styled.div`
    display: flex;
    flex-wrap: wrap;
    gap: 3px;
    font-size: 13px;
    cursor: default;
    color: rgb(105, 112, 122) !important;
`;

export const DriveLinkButton = styled(Button)`
    padding: 0;
    margin: 0;
    border: none;
    background: none;
    height: auto;
    color: rgb(0, 97, 242);
`;