import styled from "styled-components";
import {Input} from "antd";

export const NoteMessage = styled.div`
  text-align: center;
  font-size: 12px;
  border: dashed 1px #d8d8d8;
  background: #eff2f8;
  padding: 5px;
  color: rgba(0, 0, 0, 0.85);
  border-radius: 2px;
  @media (max-width: 480px) {
    padding: 10px;
    text-align: left;
  }
`;

export const AdditionalNote = styled(Input.TextArea)`
  height: auto;
  max-height: initial;
  padding: 4px 11px;
  color: rgba(0,0,0,.85);
  border-radius: 2px;
  line-height: 1.5715;
  font-size: 12px;
  margin: 5px 0;
`;