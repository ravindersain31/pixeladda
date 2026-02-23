import styled from "styled-components";
import { Form, Input } from "antd";
import YSPButton from "@react/editor/components/Button";

export const SubscribeWrapper = styled.div`
  display: flex;
  flex-wrap: wrap;
  gap: 10px 5px;
  justify-content: center;
  align-items: center; 
  padding: 3px 10px;
  border-radius: 10px;
  margin: 5px auto;
  background: #f9fbfd;
  border: 2px dotted #d8d8d8;
  width: fit-content;
 
  @media (max-width: 704px) {
    gap: 5px;
  }

  @media (max-width: 570px) {
    margin: 5px;
  }
  @media (max-width: 565px) {
    display: block;
    margin: 5px auto;
  }
`;

export const HeadingBox = styled.div`
  h5 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    font-size: 12px;
    font-weight: normal;

    @media (max-width: 565px) {
       margin-bottom: 4px;
    }
  }
`;

export const StyledForm = styled(Form)`
  gap: 10px;
  .ant-form-item-explain-error {
    display: none !important;
  }
  .ant-form-item {
    margin: 0 !important;
  }
`;

export const EmailInput = styled(Input)`
  max-width: 144px;
  font-size: 12px;
`;

export const SaveButton = styled(YSPButton)`
  padding: 4px;
  font-size: 12px !important;
  border-color: var(--primary-color);
  background: #fff;
  color: #000 !important;
  height: auto;
  font-weight: 500;

  &:hover {
    background: var(--primary-color) !important;
    color: #fff !important;
  }
`;
