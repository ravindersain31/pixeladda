import { List, Upload } from "antd";
import styled from "styled-components";

export const StyledUpload = styled(Upload)`
    .ant-upload-list {
        max-height: 250px;
        overflow: auto;
    }
`

export const StyledList = styled(List)`
    .ant-list-items {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 16px;
    }

    .ant-list-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 8px;
    }

    .ant-list-item img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #eee;
        margin-bottom: 8px;
    }
    
    .pdf-box {
        margin: auto;
        width: 100px;
        height: 100px;
        border-radius: 4px;
        border: 1px solid #eee;
        background: #fafafa;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        font-weight: bold;
        color: #d32f2f;
        margin-bottom: 8px;
    }
`;
