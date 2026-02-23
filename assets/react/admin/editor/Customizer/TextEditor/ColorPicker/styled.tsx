import styled from "styled-components";
import {Slider} from "antd";

export const Label = styled.label`
  font-size: 14px;
  padding: 0 6px;
`;

export const TrimWidth = styled(Slider)`
  height: 30px;
  display: flex;
  align-items: center;

  .ant-slider-track {
    background-color: var(--primary-color);
  }

  &:hover {
    .ant-slider-track {
      background-color: var(--primary-color);
    }
  }

  .ant-slider-handle {
    top: 10px;
  }
`;