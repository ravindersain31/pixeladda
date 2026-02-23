import React, { createContext, useContext } from 'react';

import invariant from 'tiny-invariant';

import type { CleanupFn } from '@atlaskit/pragmatic-drag-and-drop/types';
import { ColumnType } from '@react/admin-order-queue/Types/BoardTypes';
import { OrderDetails } from '@react/admin-order-queue/redux/reducer/config/interface';

export type BoardContextValue = {
	getColumns: () => ColumnType[];

	reorderColumn: (args: { startIndex: number; finishIndex: number }) => void;

	reorderCard: (args: { columnId: string; startIndex: number; finishIndex: number }) => void;

	removeCard: (args: { columnId: string; cardId: string }) => void;

	moveCard: (args: {
		startColumnId: string;
		finishColumnId: string;
		itemIndexInStartColumn: number;
		itemIndexInFinishColumn?: number;
	}) => void;

	registerCard: (args: {
		cardId: string;
		entry: {
			element: HTMLElement;
			actionMenuTrigger: HTMLElement;
		};
	}) => CleanupFn;

	registerColumn: (args: {
		columnId: string;
		entry: {
			element: HTMLElement;
		};
	}) => CleanupFn;

	instanceId: symbol;

	openOrderDrawer: (order: OrderDetails) => void;
	closeOrderDrawer: () => void;
	drawerContent: OrderDetails | null;
	drawerVisible: boolean;

	openOrderModal: (order: OrderDetails) => void;
	closeOrderModal: () => void;
	modalContent: OrderDetails | null;
	modalVisible: boolean;
	moveOrderModalVisible: boolean;
	openMoveOrderModal: (order: OrderDetails) => void;
	closeMoveOrderModal: () => void;
};

export const BoardContext = createContext<BoardContextValue | null>(null);

export function useBoardContext(): BoardContextValue {
	const value = useContext(BoardContext);
	invariant(value, 'cannot find BoardContext provider');
	return value;
}
