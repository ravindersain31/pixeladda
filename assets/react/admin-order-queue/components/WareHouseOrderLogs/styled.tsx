import { Card, List } from "antd";
import styled from "styled-components";

export const StyledCard = styled(Card)`
    margin-bottom: -5px;
    .ant-card-body {
        padding: 5px;
    }
`;

export const StyledList = styled(List)``;

export const CommentLogsCard = styled(Card)`
    margin-top: 20px;
    .ant-card-head {
        min-height: 0;
        background: #f5f5f5;
        padding: 0 5px;
    }
    .ant-card-body {
        padding: 5px;
    }
`;