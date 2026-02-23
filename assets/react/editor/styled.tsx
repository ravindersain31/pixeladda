import styled from "styled-components";
import {Button} from "antd";

export const VerticalPreviewButton = styled.button`
  position: fixed;
  right: -52px;
  z-index: 900;
  display: inline-block;
  border: 1px solid var(--primary-color);
  transform: rotate(90deg);
  font-size: 14px;
  border-radius: 0 0 0.25rem 0.25rem;
  text-shadow: 0 0 0 snow;
  text-transform: capitalize;
  padding: 5px 10px;
  text-decoration: none;
  color: #272727;
  background-color: #fff;
  box-shadow: 1px 1px #757575;
  font-weight: 400;
  top: 40%;
  letter-spacing: 1px;

  &:hover, &:focus, &:active {
    border: 1px solid var(--primary-color);
    text-shadow: 0 0 0 snow;
    background-color: #fff !important;
    box-shadow: 1px 1px #757575;
  }
  @media (max-width: 480px) {
    &:active {
    background-color: var(--primary-color) !important;
    box-shadow: 1px 1px var(--primary-color);
    color: white;
  }
`;

