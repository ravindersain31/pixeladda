import styled from "styled-components";
import {Slider, Col} from "antd";

export const StyledCol = styled(Col)`
  text-align: center;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  flex-direction: column;
`;

export const Label = styled.label`
  font-size: 13px;
  padding: 0 6px;
  text-wrap: nowrap;

  @media screen and (max-width: 768px) {
    margin-top: 3px;
  }
`;

export const TrimWidth = styled(Slider)`
  height: 30px;
  display: flex;
  align-items: center;

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
`;