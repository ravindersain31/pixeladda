import { Card } from "antd";
import styled from "styled-components";

export const OrderFilterWrapper = styled.div`
    margin: 10px 0;
`;

export const OrderFilterCard = styled(Card)`
    .ant-picker-panels {
        flex-direction: column !important;
    }
`;