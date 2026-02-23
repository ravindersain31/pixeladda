import styled from "styled-components";
import { Col, InputNumber, Slider, Tooltip, Button } from "antd";
export const StyledTooltip = styled(Tooltip)`
`;

export const FontSize = styled(InputNumber)`
  width: 90% !important;
  padding: 0px 8px !important;
  margin: 5px !important;
  @media screen and (max-width: 768px) {
    font-size: 12px;
    padding: 0 !important;
    margin: 0 5px 5px 5px !important;
  }
  @media screen and (max-width: 576px) {
    width: 112px !important;;
    display: block;
  }
`;

export const Label = styled.label`
  font-size: 13px;
  padding-left: 6px;

  @media screen and (max-width: 768px) {
    margin-top: 2px;
  }
`;

export const TrimWidth = styled(Slider)`
  height: 30px;
  display: flex;
  align-items: center;
  margin: 11px 12px !important;

  .ant-slider-track {
    background-color: #000;
  }

  &:hover {
    .ant-slider-track {
      background-color: #000;
    }
  }

  .ant-slider-handle {
    top: 10px;
  }

  @media screen and (max-width: 768px) {
    margin: 0 12px !important;
  }
`;

export const ExtraContent = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 13px;
`;

export const StyledCol = styled(Col)`
  padding: 0 10px;
`;

export const PopoverContent = styled.div`
  color: white;
`;

export const QuestionButton = styled(Button)`
  position: absolute;
  background: #ededed;
  border: none;
  width: 16px !important;
  min-width: 16px !important;
  height: 16px;
  padding-top: 0;
  padding-bottom: 0;
  margin: 0 3px;
  margin-top: 2px;
  font-size: 10px;
  &:hover{
    color: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
  }
  &:focus{
    color: black;
    border-color: black;
  }
`;

export const StyledContainer = styled.div`
  text-align: center;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  flex-direction: column;
`;

export const UndoRedoButton = styled(Button)`
  height: 33px;
  width: 40px !important;
  margin: 5px;
  background: #fff;
  border: 1px solid #9e87be;
  border-radius: 5px;
  display: flex;
  justify-content: center;
  align-items: center;
  cursor: pointer;
  @media screen and (max-width: 768px) {
    margin: 0 5px;
  }
  @media screen and (max-width: 370px) {
    margin: 0 2px;
  }
`;

export const StyledYSPCol = styled(Col)`
  display: flex;
  align-items: end;
`;

export const UndoRedoLabel = styled.label`
  font-size: 13px;
  padding-left: 6px;

  @media screen and (max-width: 768px) {
    margin-top: 3px;
  }
`;

export const OrderCol = styled(Col)`

`;