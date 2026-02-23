import { Col, Row } from "antd";
import styled from "styled-components";


export const SideName = styled.div`
  font-size: 20px;
  font-weight: bold;
  text-align: center;
  text-transform: uppercase;
  margin-top: 10px;
`;

export const StyledFlexCenterRow = styled(Row)`
    display: flex;
    justify-content: center;
    width: 100%;
`;

export const StyledPreviewContainer = styled(Col) <{ isDoubleSide?: boolean }>`
    display: flex;
    flex-direction: column;
    padding: 10px;
    overflow: hidden;

    canvas {
        max-width: 100%;
        max-height: 100%;
        border: 2px dotted #bbb;
    }
`;