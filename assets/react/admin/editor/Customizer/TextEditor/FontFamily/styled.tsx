import styled from "styled-components";
import { Select } from 'antd';

export const StyledSelect = styled(Select)`
  width: 100%;
  padding: 5px;

  .ant-select-selector {
    height: 100% !important;
    padding: 0px 15px!important;
    font-size: 14px;
    .ant-select-selection-search-input {
        height: 100% !important;
    }
  }
`;

export const SelectLabel = styled.span`
  width: 100%;
  font-family: ${(props: any) => props['data-family']};
`;

