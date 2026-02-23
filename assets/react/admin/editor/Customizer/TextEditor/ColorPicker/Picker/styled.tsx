import styled from 'styled-components';
import {ColorPicker} from 'antd';
import {ColorPickerProps} from "antd/lib";

export const StyledColorPicker = styled(ColorPicker)<ColorPickerProps>`
  height: 43px;
  width: 43px;
  margin: 5px auto;

  .ant-color-picker-color-block {
    width: 100%;
    height: 100%;
    padding: 3px;
    background: #fff;
  }
`;