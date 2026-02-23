import styled from "styled-components";
import { Slider } from "antd";

export const RotationSlider = styled(Slider)`
  height: 30px;
  display: flex;
  align-items: center;
  margin: 11px 11px !important;

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
    margin: 0 11px !important;
  }
`;