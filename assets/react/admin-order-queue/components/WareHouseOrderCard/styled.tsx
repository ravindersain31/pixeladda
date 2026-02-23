import { Card, Col } from "antd";
import styled from "styled-components";

export const CardHeaderWrapper = styled.div`
    display: flex;
    align-items: center;
    justify-content: space-between;
`;

export const StyledCard = styled(Card) <{ ordergroupcolor: string, $hasmustship: boolean }>`
    /* margin-bottom: 10px; */
    border-radius: 5px;
    cursor: pointer;
    background: ${(props) => (props.$hasmustship ? "#d2edfd" : "#fff")};
    border: 2px ${props => props.ordergroupcolor} solid;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.3s ease, opacity 0.3s ease;

    box-shadow: 0 .125rem .25rem 0 rgba(33,40,50,.2);

    &:hover {
        border-color: #1890ff;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    &.dragging {
        opacity: 0.7;
        transform: rotate(2deg) scale(1.05);
    }

    .ant-card-body {
        padding: 5px;
    }
`;



export const HeaderWrapper = styled.div`
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background-color: #f5f5f5;
    border-bottom: 1px solid #d9d9d9;
`;

export const ListHeader = styled.h2`
    font-size: 1.5rem;
    font-weight: bold;
    margin: 0;
    color: #333;
`;

export const OrderQueueListWrapper = styled.div`
    padding: 16px;
    background-color: #ffffff;
    border: 1px solid #e8e8e8;
    border-radius: 4px;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
`;

export const StyledCol = styled(Col)`
    padding: 0!important;
`;