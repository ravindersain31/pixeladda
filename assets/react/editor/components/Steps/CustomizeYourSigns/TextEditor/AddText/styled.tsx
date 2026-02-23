import styled from "styled-components";
import {Input} from "antd";
import Button from "@react/editor/components/Button";


export const AddTextContainer = styled.div`
  display: flex;
  justify-content: space-between;
  align-items: center;
`;

export const AddTextInput = styled(Input.TextArea)`
  margin: 5px;
  font-size: 14px;
  padding-top: 4px;
`;

export const AddTextButton = styled(Button)`
    margin: 0 15px;
    @media (min-width: 480px){
      color: #000 !important;
      background: #fff;
      &:hover {
        color: #fff !important;
        background: var(--primary-color) !important;
      }
    }
    @media (max-width:480px) {
      background: #fff !important;
      color: #000 !important;
      margin: 0 5px;
    }
`;