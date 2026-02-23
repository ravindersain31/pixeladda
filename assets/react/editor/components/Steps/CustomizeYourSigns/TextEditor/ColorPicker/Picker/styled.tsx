import styled from 'styled-components';
import {ColorPicker} from 'antd';
import {ColorPickerProps} from "antd/lib";
import {lightOrDark} from "@react/editor/helper/canvas.ts";

export const StyledColorPicker = styled(ColorPicker)<ColorPickerProps>`
  height: 43px;
  width: 43px;
  margin: 5px;

  .ant-color-picker-color-block {
    width: 100%;
    height: 100%;
    padding: 3px;
    background: #fff;
  }
`;

export const ColorPickerTrigger = styled.div<{ color: string, disabled?: boolean }>`
  margin: 5px;

  div {
    height: 33px;
    width: 40px;
    background: ${props => props.disabled ? '#f5f5f5' : '#fff'};
    border: 1px solid var(--primary-color);
    border-radius: 5px;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: ${props => props.disabled ? 'not-allowed' : 'pointer'};

    span {
      color: ${props => {
          return lightOrDark(props.color as string) === 'light' ? '#000' : '#FFF'
      }};
      width: 85%;
      height: 85%;
      border-radius: 3px;
      display: flex;
      justify-content: center;
      align-items: center;
      opacity: ${props => props.disabled ? 0.5 : 1};
    }
  }

  @media screen and (max-width: 768px) {
    margin: 0 5px 5px 5px;
  }
`;