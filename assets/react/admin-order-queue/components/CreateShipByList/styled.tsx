import styled from 'styled-components';
import { Button, Card } from 'antd';

export const Container = styled.div`
    padding: 8px;
`;

export const StyledCard = styled(Card)`
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    padding: 10px;
    width: 300px;
    max-width: 600px;
    .ant-card-body {
        padding: 0;
    }
    .ant-space-item {
        .ant-form-item {
            margin-bottom: 10px;
        }
    }

    .ant-form-item {
        margin: 0px;
    }
`;

export const StyledButton = styled(Button)`
    width: 100%;
    font-size: 0.9rem;
    font-weight: 600;
    margin-top: 10px;
    margin-bottom: 0;
`;

export const AddButton = styled(Button)`
    width: 100%;
    margin-bottom: 0;
    && {
        background: #fafafa;
        border-color: #d9d9d9;
        font-size: 0.9rem;
        font-weight: 500;
    }
`;

export const RemoveButton = styled(Button)`
    font-size: 0.9rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
`;
