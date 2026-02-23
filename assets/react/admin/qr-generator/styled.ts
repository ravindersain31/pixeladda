import styled, { keyframes } from 'styled-components';
import { Card, Button } from 'antd';

export const fadeIn = keyframes`
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
`;

export const AnimatedSection = styled.div`
    animation: ${fadeIn} 0.5s ease-out;
`;

export const FadeInWrapper = styled.div`
    width: 100%;
    animation: ${fadeIn} 0.3s ease-out;
`;

export const GlassCard = styled(Card)`
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
    border-radius: 16px;
    overflow: hidden;
    animation: ${fadeIn} 0.5s ease-out;

    .ant-card-head {
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        background: transparent;
        padding: 0 24px;
    }
`;

export const QRPreviewContainer = styled.div`
    background: white;
    padding: 24px;
    border-radius: 20px;
    box-shadow: inset 0 0 0 1px rgba(0,0,0,0.05), 0 10px 25px rgba(0,0,0,0.05);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    cursor: pointer;

    &:hover {
        transform: translateY(-5px);
        box-shadow: inset 0 0 0 1px rgba(0,0,0,0.05), 0 20px 40px rgba(0,0,0,0.1);
    }
`;

export const HistoryItem = styled.div`
    padding: 12px;
    border-radius: 12px;
    background: #f9f9f9;
    margin-bottom: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.2s;
    border: 1px solid transparent;

    &:hover {
        background: #fff;
        border-color: #1677ff;
        transform: translateX(5px);
    }
`;

export const ProtocolButton = styled(Button)`
    border-radius: 8px;
    height: 38px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
`;