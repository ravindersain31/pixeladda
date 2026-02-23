import styled from "styled-components";
import { Select } from 'antd';

export const StyledSelect = styled(Select)`
    width: 100%;
    padding: 5px;
    height: auto;

    .ant-select-selection-search-input {
        padding-right: 12px !important;
    }

    .ant-select-selector {
        height: 100% !important;
        padding: 0px 15px !important;
        font-size: 14px;

        .ant-select-selection-search-input {
            height: 100% !important;
        }
    }
    @media screen and (max-width: 768px) {
        height: 33px !important;
    }

    @media screen and (max-width: 768px) {
        padding: 0 5px 5px 5px;
    }
`;


export const SelectLabel = styled.span.attrs(props => ({
    style: {
        // @ts-ignore
        fontFamily: props['data-family'],
    },
}))`
    width: 100%;
`;
