import { Button } from "antd";
import styled from "styled-components";

export const AddQrButton = styled(Button)`
    border: 1px solid var(--primary-color) !important;
    background: #fff;
    color: #000;
    transition: none;
    font-size: 10px;
    padding: 0 7px;
    width: fit-content;
    margin: 7px;
    margin-bottom: 15px;
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

    &:hover svg, 
    &:hover svg path {
        fill: #fff !important;
    }
`;
