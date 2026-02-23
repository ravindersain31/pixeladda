import React, {
	memo,
	useCallback,
	useEffect,
	useMemo,
	useRef,
	useState,
} from 'react';
import { createPortal } from 'react-dom';
import { Virtuoso } from 'react-virtuoso'
import invariant from 'tiny-invariant';
import {
	draggable,
	dropTargetForElements,
} from '@atlaskit/pragmatic-drag-and-drop/element/adapter';
import { combine } from '@atlaskit/pragmatic-drag-and-drop/combine';
import { autoScrollForElements } from '@atlaskit/pragmatic-drag-and-drop-auto-scroll/element';
import {
	attachClosestEdge,
	extractClosestEdge,
	type Edge,
} from '@atlaskit/pragmatic-drag-and-drop-hitbox/closest-edge';
import { DropIndicator } from '@atlaskit/pragmatic-drag-and-drop-react-drop-indicator/box';
import { setCustomNativeDragPreview } from '@atlaskit/pragmatic-drag-and-drop/element/set-custom-native-drag-preview';
import { centerUnderPointer } from '@atlaskit/pragmatic-drag-and-drop/element/center-under-pointer';
import { Col, message, Modal, Row } from 'antd';
import { CloseOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import axios from 'axios';

// internal import
import { getShipByColor } from '@react/admin-order-queue/helper';
import { Card } from '@react/admin-order-queue/components/Card';
import { ColumnContext } from '@react/admin-order-queue/context/ColumnContext';
import { useBoardContext } from '@react/admin-order-queue/context/BoardContext';
import { ColumnType } from '@react/admin-order-queue/Types/BoardTypes';
import { HeaderWrapper, ListHeaderTitle, OrderQueueListWrapper, RemoveShipByButton } from '@react/admin-order-queue/components/ShipByList/styled';
import AddOrderAutoComplete from '@react/admin-order-queue/components/AddOrderAutoComplete';

type State =
	| { type: 'idle' }
	| { type: 'is-card-over' }
	| { type: 'is-column-over'; closestEdge: Edge | null }
	| { type: 'generate-safari-column-preview'; container: HTMLElement }
	| { type: 'generate-column-preview' };

const idle: State = { type: 'idle' };
const isCardOver: State = { type: 'is-card-over' };

const columnBaseStyle: React.CSSProperties = {
	width: '250px',
	borderRadius: 6,
	transition: 'background 0.3s ease-in-out',
	position: 'relative',
	display: 'flex',
	flexDirection: 'column',
	cursor: 'grab',
};

const draggingStyle: React.CSSProperties = {
	opacity: 0.4,
};

const columnOverStyle: React.CSSProperties = {
	backgroundColor: '#E0E0E0',
};

const scrollContainerStyle: React.CSSProperties = {
	height: '100%',
	overflowY: 'auto',
};

const cardListStyle: React.CSSProperties = {
	boxSizing: 'border-box',
	height: '100vh',
	padding: 8,
	display: 'flex',
	flexDirection: 'column',
	gap: 8,
	overflowY: 'scroll',
};


export const Column = memo(function Column({ column }: { column: ColumnType }) {

	const columnId = column.columnId;
	const columnRef = useRef<HTMLDivElement | null>(null);
	const columnInnerRef = useRef<HTMLDivElement | null>(null);
	const headerRef = useRef<HTMLDivElement | null>(null);
	const scrollableRef = useRef<HTMLDivElement | null>(null);
	const [state, setState] = useState<State>(idle);
	const [isDragging, setIsDragging] = useState(false);
	const [loading, setLoading] = useState<boolean>(false);

	const { instanceId, registerColumn } = useBoardContext();

	useEffect(() => {
		const scrollable = scrollableRef.current;

		invariant(columnRef.current);
		invariant(columnInnerRef.current);
		invariant(headerRef.current);
		invariant(scrollableRef.current);
		invariant(scrollable);

		return combine(
			registerColumn({
				columnId,
				entry: {
					element: columnRef.current,
				},
			}),
			draggable({
				element: columnRef.current,
				dragHandle: headerRef.current,
				getInitialData: () => ({ columnId, type: 'column', instanceId }),
				onGenerateDragPreview: ({ nativeSetDragImage }) => {
					const isSafari =
						navigator.userAgent.includes('AppleWebKit') &&
						!navigator.userAgent.includes('Chrome');

					if (!isSafari) {
						setState({ type: 'generate-column-preview' });
						return;
					}

					setCustomNativeDragPreview({
						getOffset: centerUnderPointer,
						render: ({ container }) => {
							setState({
								type: 'generate-safari-column-preview',
								container,
							});
							return () => setState(idle);
						},
						nativeSetDragImage,
					});
				},
				onDragStart: () => setIsDragging(true),
				onDrop: () => {
					setState(idle);
					setIsDragging(false);
				},
			}),
			dropTargetForElements({
				element: columnInnerRef.current,
				getData: () => ({ columnId }),
				canDrop: ({ source }) =>
					source.data.instanceId === instanceId && source.data.type === 'card',
				getIsSticky: () => true,
				onDragEnter: () => setState(isCardOver),
				onDragLeave: () => setState(idle),
				onDragStart: () => setState(isCardOver),
				onDrop: () => setState(idle),
			}),
			dropTargetForElements({
				element: columnRef.current,
				canDrop: ({ source }) =>
					source.data.instanceId === instanceId && source.data.type === 'column',
				getIsSticky: () => true,
				getData: ({ input, element }) => {
					const data = { columnId };
					return attachClosestEdge(data, {
						input,
						element,
						allowedEdges: ['left', 'right'],
					});
				},
				onDragEnter: (args) => {
					setState({
						type: 'is-column-over',
						closestEdge: extractClosestEdge(args.self.data),
					});
				},
				onDrag: (args) => {
					const closestEdge = extractClosestEdge(args.self.data);
					setState((current) => {
						if (
							current.type === 'is-column-over' &&
							current.closestEdge === closestEdge
						) {
							return current;
						}
						return { type: 'is-column-over', closestEdge };
					});
				},
				onDragLeave: () => setState(idle),
				onDrop: () => setState(idle),
			}),
			autoScrollForElements({
				canScroll({ source }) {
					return true;
				},
				getConfiguration: () => ({ maxScrollSpeed: 'fast' }),
				element: scrollable,
			})
		);
	}, [columnId, registerColumn, instanceId]);

	const stableItems = useRef(column.items);
	useEffect(() => {
		stableItems.current = column.items;
	}, [column.items]);

	const getCardIndex = useCallback((id: string) => {
		return stableItems.current.findIndex((item) => item.id === id);
	}, []);

	const getNumCards = useCallback(() => {
		return stableItems.current.length;
	}, []);

	const contextValue = useMemo(() => {
		return { columnId, getCardIndex, getNumCards };
	}, [columnId, getCardIndex, getNumCards]);

	const finalStyle = {
		...columnBaseStyle,
		...(state.type === 'is-card-over' ? columnOverStyle : {}),
		...(isDragging ? draggingStyle : {}),
	};

	const handleDeleteShipBy = () => {
		Modal.confirm({
			title: 'Are you sure you want to delete this Ship By list?',
			content: 'This action cannot be undone. Please ensure all orders have been moved before proceeding.',
			okText: 'Yes, Delete',
			cancelText: 'Cancel',
			okType: 'danger',
			onOk: async () => {
				setLoading(true);
				try {
					const response = await axios.delete(`/warehouse/queue-api/warehouse-orders/delete-ship-by-list/${column.listId}`);
					if (response.status === 200) {
						message.success(response.data.message);
					} else {
						message.error('An error occurred while deleting the Ship By list.');
					}
				} catch (error: any) {
					message.error(
						error.response?.data?.message ||
						'Failed to delete Ship By list. Please try again later.'
					);
				} finally {
					setLoading(false);
				}
			},
		});
	};

	return (
		<ColumnContext.Provider value={contextValue}>
			<OrderQueueListWrapper style={{ backgroundColor: getShipByColor(column.title), ...finalStyle }}>
				<div ref={columnRef} data-testid={`column-${columnId}`}>
					<div ref={columnInnerRef}>
						<HeaderWrapper ref={headerRef}>
							<Row style={{ width: '100%' }} className='board-header'>
								<Col xs={12} sm={12} md={12} lg={12}>
									<ListHeaderTitle>
										Ship By: {dayjs(column.title).format('ddd MMM D')}
										<RemoveShipByButton
											type="primary"
											size="small"
											icon={<CloseOutlined style={{ fontSize: "12px" }} />}
											loading={loading}
											onClick={handleDeleteShipBy}
										/>
									</ListHeaderTitle>
								</Col>
								<Col xs={12} sm={12} md={12} lg={12}>
									<AddOrderAutoComplete shipBy={column.title} listId={column.listId} />
								</Col>
							</Row>
						</HeaderWrapper>
						<div ref={scrollableRef} className="orders-wrapper" style={{ height: '100vh', overflowY: 'auto', }}  >
							<Virtuoso
								totalCount={column.items.length}
								itemContent={(index) => <Card item={column.items[index]} key={column.items[index].id} />}
							/>
						</div>
					</div>
					{state.type === 'is-column-over' && state.closestEdge && (
						<DropIndicator edge={state.closestEdge} gap="2px" />
					)}
				</div>
				{state.type === 'generate-safari-column-preview'
					? createPortal(<SafariColumnPreview column={column} />, state.container)
					: null}
			</OrderQueueListWrapper>
		</ColumnContext.Provider>
	);
});

function SafariColumnPreview({ column }: { column: ColumnType }) {
	return (
		<div
			style={{
				width: '250px',
				backgroundColor: '#F4F5F7',
				borderRadius: 6,
				padding: 16,
			}}
		>
			<h1>{column.title}</h1>
		</div>
	);
}
