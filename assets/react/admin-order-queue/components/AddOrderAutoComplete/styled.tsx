import { Card, Form, Row } from "antd";
import styled from "styled-components";

export const AddOrderFrom = styled(Form)`
    .ant-form-item {
        margin: 0;
    }
`;

export const StyledRow = styled(Row)`
    flex-flow: row;
    .ant-form-item-control-input {
        min-height: 0 !important;
    }
`;

export const AddOrderWrapper = styled.div``;

export const NoDataRender = styled(Card)`
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
`;