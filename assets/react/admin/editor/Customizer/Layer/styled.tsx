import { Button } from "antd";
import styled from "styled-components";

export const LayerWrapper = styled.div`
    display: flex;
    flex-direction: column;

    h5 {
        font-size: 15px;
        text-transform: capitalize;
    }

    .layer-list {
        max-height: 250px;
        overflow: scroll;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
`;

export const LayerItem = styled.div`
    display: flex;
    align-items: center;
    margin-bottom: 5px;
    padding: 5px 10px;
    cursor: pointer;

    .icon {
        margin-right: 5px;
    }

    &.active {
        background: #f2f2f2;
    }

    &:hover {
        background: #f2f2f2;
    }
`;

export const SelectBackground = styled(Button)<{ $isBackground: boolean }>`
  margin: 5px;
  border-radius: 5px;
  height: auto;
  min-height: auto;
  padding: 8px;
  background: ${({ $isBackground }) => ($isBackground ? "var(--primary-color)" : "#fff")} !important;
  color: ${({ $isBackground }) => ($isBackground ? "#fff" : "var(--primary-color)")} !important;
  border: 2px solid var(--primary-color);
  font-size: 10px;
  svg {
    font-size: 20px;
  }
`;