import { Button } from "antd";
import styled from "styled-components";

export const StyledButton = styled(Button)`
    height: auto;
    border: 1px solid #704c9f !important;
    background: #fff;
    color: #000;
    transition: none;
    font-size: 12px;
    padding: 2px 10px;
    width: fit-content;
    margin: 7px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 2px;

    @media screen and (max-width: 767px){
        margin: 2px 5px;
    }

    &:hover{
        background-color: var(--primary-color) !important;
        color: #fff !important;
    }
`;
