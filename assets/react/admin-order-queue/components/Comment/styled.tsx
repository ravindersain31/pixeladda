import { Card } from "antd";
import styled from "styled-components";

export const CommentWrapper = styled.div`
    .comment-form {
        margin-bottom: 5px;
        .ant-form-item {
            margin-bottom: 5px;
            padding: 0;
        }
        .ant-form-item-label {
            padding: 0;
        }
    }
    .ant-card-body {
        .ant-list-header {
            padding: 5px 0;
        }
    }
`;

export const CommentCard = styled(Card)`
    margin-bottom: 5px;
`;