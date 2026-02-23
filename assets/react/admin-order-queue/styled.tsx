import styled from "styled-components";

export const OrderQueueWrapper = styled.div`
    white-space: nowrap;
    flex-direction: column;
    height: 100vh;
    display: flex;
    padding: 5px;
    background: #f0f2f5;
    position: relative;
    overflow: hidden;

    .orders-container {
        display: flex;
        flex-wrap: nowrap;
        height: 100%;
        overflow-x: auto;
        overflow-y: hidden;
    }
    .queue-board-scrollable {
        &::-webkit-scrollbar {
            height: 7px;
            width: 0;
        }
        &::-webkit-scrollbar-thumb {
            border-radius: 4px;
            background-color: rgba(0, 0, 0, 0.5);
            -webkit-box-shadow: 0 0 1px rgba(255, 255, 255, 0.5);
        }
        &::-webkit-scrollbar-track {
            background: transparent;
        }
    }
`;
