import { Button, Card, Modal } from "antd";
import styled from "styled-components";

export const OrderQueueListWrapper = styled.div`
    min-width: 360px;
    max-width: 360px;
    border-radius: 5px;
    background: #ffe5df;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    padding: 4px 8px;
    border: 1px solid #e5e5e5;
    display: flex;
    flex-direction: column;
    overflow: hidden;

    .orders-wrapper {
        overflow-y: auto;
        flex-grow: 1;
        max-height: 100%;
    }

    .sortable-chosen {
        border-color: #d9d9d9;
        box-shadow: none;
    }

    .sortable-drag {
    }
`;


export const HeaderWrapper = styled.div`
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 2px 8px;
	width: 100%;
	background-color: transparent;

	.board-header {
		width: 100%;
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 0;
	}
`;



export const ListHeader = styled.div`
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
`;

export const ListHeaderTitle = styled.div`
	display: flex;
	align-items: center;
	gap: 4px;
	font-weight: 500;
	color: black;
	font-size: 14px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
`;


export const StyledCard = styled(Card)`
    margin-bottom: 10px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    background: #fff;
    .ant-card-body {
        padding: 5px;
    }
`;

export const StyledModal = styled(Modal)`
    .ant-modal-content {
        padding: 15px;
    }
`;

export const CardHeaderWrapper = styled.div`
    display: flex;
    align-items: center;
    justify-content: space-between;
`;

export const ExpandButton = styled(Button)`
    width: 20px !important;
    height: 20px;
    padding: 0;
    .ant-btn-icon {
        .anticon-expand {
            font-size: 12px;
        }
    }
`;

export const RemoveShipByButton = styled(Button)`
    background: red !important;
    width: 20px !important;
    height: 20px !important;
    line-height: 0 !important;
    .ant-btn-link {
        /* color: rgb(232, 21, 0); */
        background: red;
    }

    .ant-button {
    }
`;

export const AddShipByButton = styled(Button)`
    line-height: 0 !important;
    .ant-btn-link {

    }

    .ant-button {
    }
`;

export const DraggableWrapper = styled.div``;