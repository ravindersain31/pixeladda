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
`;

export const AddTextButton = styled(Button)`
    margin: 0 15px;
`;