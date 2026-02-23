import React, { lazy, memo, Suspense, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';
import { notification, Spin } from 'antd';
import axios from 'axios';
import invariant from "tiny-invariant";
import { triggerPostMoveFlash } from "@atlaskit/pragmatic-drag-and-drop-flourish/trigger-post-move-flash";
import { extractClosestEdge } from "@atlaskit/pragmatic-drag-and-drop-hitbox/closest-edge";
import { getReorderDestinationIndex } from "@atlaskit/pragmatic-drag-and-drop-hitbox/util/get-reorder-destination-index";
import * as liveRegion from "@atlaskit/pragmatic-drag-and-drop-live-region";
import { combine } from "@atlaskit/pragmatic-drag-and-drop/combine";
import { monitorForElements } from "@atlaskit/pragmatic-drag-and-drop/element/adapter";
import { reorder } from "@atlaskit/pragmatic-drag-and-drop/reorder";
import { autoScrollForElements } from '@atlaskit/pragmatic-drag-and-drop-auto-scroll/element';
import { unsafeOverflowAutoScrollForElements } from '@atlaskit/pragmatic-drag-and-drop-auto-scroll/unsafe-overflow/element';
import { CleanupFn } from '@atlaskit/pragmatic-drag-and-drop/dist/types/internal-types';
import { bindAll } from 'bind-event-listener';
import { useAppDispatch, useAppSelector } from "@react/admin-order-queue/hook.ts";
import { ListProps, type OrderDetails } from '@react/admin-order-queue/redux/reducer/config/interface';
import { useMercureActions } from '@react/admin-order-queue/hooks/useMercureActions';
import { BoardContext, type BoardContextValue } from '@react/admin-order-queue/context/BoardContext';
import { createRegistry } from '@react/admin-order-queue/context/createRegistry';
import { BoardState, ColumnMap, ColumnType, Outcome, Trigger } from '@react/admin-order-queue/Types/BoardTypes';
import { getBoardState } from '@react/admin-order-queue/helper/board';
import Board from '@react/admin-order-queue/components/Board';
import { Column } from '@react/admin-order-queue/components/Column';
import { getSessionId } from '@react/admin-order-queue/utils/mercure.utils';
import { shallowEqual } from 'react-redux';
import { FiltersState, OrderTags } from '@react/admin-order-queue/redux/reducer/interface';
import { MercureContext } from "@react/admin-order-queue/context/MercureProvider";
import _ from 'lodash';
import { MERCURE_TOPICS, MercureEvent } from '@react/admin-order-queue/constants/mercure.constant';
import { normalizeDate } from '@react/admin-order-queue/helper';
import dayjs from 'dayjs';

// Lazy load heavy components
const CreateShipByList = lazy(() => import('@react/admin-order-queue/components/CreateShipByList'));
const OrderDetailsDrawer = lazy(() => import('@react/admin-order-queue/components/OrderDetailsDrawer'));
const OrderDetailsModal = lazy(() => import('@react/admin-order-queue/components/OrderDetailsModal'));
const OrderFilter = lazy(() => import('@react/admin-order-queue/components/OrderFilter'));

const OrderQueueBoard = memo(() => {

    const lists = useAppSelector((state) => state.config.lists, shallowEqual);
    const printer = useAppSelector((state) => state.config.printer, shallowEqual);
    const filters = useAppSelector((state) => state.config.filters, shallowEqual);
    const dispatch = useAppDispatch();
    const [drawerContent, setDrawerContent] = useState<OrderDetails | null>(null);
    const [drawerVisible, setDrawerVisible] = useState<boolean>(false);
    const [modalVisible, setModalVisible] = useState<boolean>(false);
    const [modalContent, setModalContent] = useState<OrderDetails | null>(null);
    const [moveOrderModalVisible, setMoveOrderModalVisible] = useState<boolean>(false);
    const [moveOrderContent, setMoveOrderContent] = useState<OrderDetails | null>(null);
    
    const { events } = useContext(MercureContext);

    const scrollableRef = useRef<HTMLDivElement | null>(null);
    const blockBoardPanningAttr = 'data-block-board-panning' as const;
    const sessionId = getSessionId();

    const params = new URLSearchParams(window.location.search);
    const searchUri = params.get('wq');

    const [isFiltering, setIsFiltering] = useState<boolean>(false);

    useMercureActions();

    const stableLists = useRef(lists);
    useEffect(() => {
        stableLists.current = lists;
    }, [lists]);

    const [data, setData] = useState<BoardState>(() => {
        const base = getBoardState(stableLists.current, filters);
        return {
            ...base,
            lastOperation: null,
        };
    });

    const prevFiltersRef = useRef<FiltersState | null>(null);

    useEffect(() => {
        if (!lists || lists.length === 0) return;

        const filtersChanged = !_.isEqual(prevFiltersRef.current, filters);

        if (filtersChanged) {
            setIsFiltering(true);

            setTimeout(() => {
                setData((prevData) => {
                    const updated = getBoardState(stableLists.current, filters);
                    setIsFiltering(false);
                    return {
                        ...updated,
                        lastOperation: null,
                    };
                });
            }, 500);

            prevFiltersRef.current = filters;
        } else{

            setData((prevData) => {
                const updated = getBoardState(stableLists.current, filters);
                return {
                    ...updated,
                    lastOperation: null,
                };
            });
        }
    }, [lists, filters]);

    const stableData = useRef(data);
    useEffect(() => {
        stableData.current = data;
    }, [data]);

    const [registry] = useState(createRegistry);
    const { lastOperation } = data;

    useEffect(() => {
        if (lastOperation === null) {
            return;
        }

        const { outcome, trigger } = lastOperation;

        if (outcome.type === "column-reorder") {
            const { startIndex, finishIndex } = outcome;

            const { columnMap, orderedColumnIds } = stableData.current;
            const sourceColumn = columnMap[orderedColumnIds[finishIndex]];

            const entry = registry.getColumn(sourceColumn.columnId);
            triggerPostMoveFlash(entry.element);

            liveRegion.announce(
                `You've moved ${sourceColumn.title} from position ${startIndex + 1
                } to position ${finishIndex + 1} of ${orderedColumnIds.length}.`
            );

            return;
        }

        if (outcome.type === "card-reorder") {
            const { columnId, startIndex, finishIndex } = outcome;

            if (startIndex < 0 || finishIndex < 0) {
                console.warn("Invalid card-reorder outcome. Skipping animation/announcement.", outcome);
                return;
            }

            const { columnMap } = stableData.current;
            const column = columnMap[columnId];
            const item = column.items[finishIndex];
            const entry = registry.getCard(item.id);
            triggerPostMoveFlash(entry.element);

            if (trigger !== "keyboard") {
                return;
            }

            liveRegion.announce(
                `You've moved ${item.id} from position ${startIndex + 1
                } to position ${finishIndex + 1} of ${column.items.length} in the ${column.title
                } column.`
            );

            return;
        }

        if (outcome.type === "card-move") {
            const {
                finishColumnId,
                itemIndexInStartColumn,
                itemIndexInFinishColumn,
            } = outcome;

            const data = stableData.current;
            const destinationColumn = data.columnMap[finishColumnId];
            const item = destinationColumn.items[itemIndexInFinishColumn];

            const finishPosition =
                typeof itemIndexInFinishColumn === "number"
                    ? itemIndexInFinishColumn + 1
                    : destinationColumn.items.length;

            const entry = registry.getCard(item.id);
            triggerPostMoveFlash(entry.element);

            if (trigger !== "keyboard") {
                return;
            }

            liveRegion.announce(
                `You've moved ${item.id} from position ${itemIndexInStartColumn + 1
                } to position ${finishPosition} in the ${destinationColumn.title
                } column.`
            );

            /**
             * Because the card has moved column, it will have remounted.
             * This means we need to manually restore focus to it.
             */
            entry.actionMenuTrigger.focus();

            return;
        }

        if (outcome.type === "card-remove") {
            const { columnId, cardId } = outcome;
            // Or update UI with flash/animation/live region, if needed
            liveRegion.announce(`Card ${cardId} removed from column.`);
        }

    }, [lastOperation, registry]);

    useEffect(() => {
        return liveRegion.cleanup();
    }, []);

    const getColumns = useCallback(() => {
        const { columnMap, orderedColumnIds } = stableData.current;
        return orderedColumnIds.map((columnId) => columnMap[columnId]);
    }, []);

    const reorderColumn = useCallback(
        ({
            startIndex,
            finishIndex,
            trigger = "keyboard",
        }: {
            startIndex: number;
            finishIndex: number;
            trigger?: Trigger;
        }) => {
            setData((data) => {
                const outcome: Outcome = {
                    type: "column-reorder",
                    columnId: data.orderedColumnIds[startIndex],
                    startIndex,
                    finishIndex,
                };

                return {
                    ...data,
                    orderedColumnIds: reorder({
                        list: data.orderedColumnIds,
                        startIndex,
                        finishIndex,
                    }),
                    lastOperation: {
                        outcome,
                        trigger: trigger,
                    },
                };
            });
        },
        []
    );

    const reorderCard = useCallback(
        ({
            columnId,
            startIndex,
            finishIndex,
            trigger = "keyboard",
        }: {
            columnId: string;
            startIndex: number;
            finishIndex: number;
            trigger?: Trigger;
        }) => {
            setData((data) => {
                const sourceColumn = data.columnMap[columnId];
                const updatedItems = reorder({
                    list: sourceColumn.items,
                    startIndex,
                    finishIndex,
                });

                const updatedSourceColumn: ColumnType = {
                    ...sourceColumn,
                    items: updatedItems,
                };

                const updatedMap: ColumnMap = {
                    ...data.columnMap,
                    [columnId]: updatedSourceColumn,
                };

                // 🔁 Send updated sort order to API
                const updatedOrders = updatedItems.map((order, index) => ({
                    id: order.id,
                    sortIndex: index,
                    orderId: order.order.orderId,
                    shipBy: order.shipBy,
                }));

                // if (!searchUri) {
                    axios.post("/warehouse/queue-api/orders/update-sort", {
                        orders: updatedOrders,
                        printer: printer,
                        sessionId: sessionId
                    }).catch((error) => {
                        console.error("Error updating sort indexes:", error);
                    });
                // }

                const outcome: Outcome | null = {
                    type: "card-reorder",
                    columnId,
                    startIndex,
                    finishIndex,
                };

                return {
                    ...data,
                    columnMap: updatedMap,
                    lastOperation: {
                        trigger: trigger,
                        outcome,
                    },
                };
            });
        },
        []
    );

    const moveCard = useCallback(
        ({
            startColumnId,
            finishColumnId,
            itemIndexInStartColumn,
            itemIndexInFinishColumn,
            trigger = "pointer",
        }: {
            startColumnId: string;
            finishColumnId: string;
            itemIndexInStartColumn: number;
            itemIndexInFinishColumn?: number;
            trigger?: "pointer" | "keyboard";
        }) => {
            // invalid cross column movement
            if (startColumnId === finishColumnId) {
                return;
            }

            setData((data) => {
                const sourceColumn = data.columnMap[startColumnId];
                const destinationColumn = data.columnMap[finishColumnId];
                const item: OrderDetails = sourceColumn.items[itemIndexInStartColumn];

                if (item.order.metaData.mustShip?.date) {
                    notification.warning({
                        message: 'Cannot Move Order',
                        description: `Orders with "Must Ship ${dayjs(item.order.metaData.mustShip.date).format('MMM D, YYYY')}" tag cannot be moved to a different Ship By column. Please remove the tag first or update it.`,
                        duration: 5,
                    });
                    return data;
                }

                const destinationItems = Array.from(destinationColumn.items);
                // Going into the first position if no index is provided
                const newIndexInDestination = itemIndexInFinishColumn ?? destinationItems.length;
                destinationItems.splice(newIndexInDestination, 0, item);

                const updatedMap: ColumnMap = {
                    ...data.columnMap,
                    [startColumnId]: {
                        ...sourceColumn,
                        items: sourceColumn.items.filter((i) => i.id !== item.id),
                    },
                    [finishColumnId]: {
                        ...destinationColumn,
                        items: destinationItems,
                    },
                };

                // ✅ Update full sort order of destination column
                const updatedOrders = destinationItems.map((order, index) => ({
                    id: order.id,
                    sortIndex: index,
                    orderId: order.order.orderId,
                    shipBy: destinationColumn.title,
                }));

                // if (!searchUri) {
                    axios.post("/warehouse/queue-api/orders/update-sort", {
                        orders: updatedOrders,
                        printer,
                        sessionId,
                    }).catch((error) => {
                        console.error("Error updating sort indexes after move:", error);
                    });
                // }

                const outcome: Outcome = {
                    type: "card-move",
                    startColumnId,
                    finishColumnId,
                    itemIndexInStartColumn,
                    itemIndexInFinishColumn: newIndexInDestination,
                };

                return {
                    ...data,
                    columnMap: updatedMap,
                    lastOperation: {
                        outcome,
                        trigger,
                    },
                };
            });
        },
        []
    );

    const removeCard = useCallback(
        ({ columnId, cardId }: { columnId: string; cardId: string }) => {
            setData((data) => {
                const column = data.columnMap[columnId];
                if (!column) {
                    console.warn(`Column with ID "${columnId}" not found.`);
                    return data;
                }

                const cardExists = column.items.some((item) => item.id === cardId);
                if (!cardExists) {
                    console.warn(`Card with ID "${cardId}" not found in column "${columnId}".`);
                    return data;
                }

                const updatedItems = column.items.filter((item) => item.id !== cardId);

                const updatedColumn: ColumnType = {
                    ...column,
                    items: updatedItems,
                };

                const updatedMap: ColumnMap = {
                    ...data.columnMap,
                    [columnId]: updatedColumn,
                };

                const outcome: Outcome = {
                    type: "card-remove",
                    columnId,
                    cardId,
                };

                return {
                    ...data,
                    columnMap: updatedMap,
                    lastOperation: {
                        trigger: "pointer",
                        outcome,
                    },
                };
            });
        },
        []
    );

    const openOrderDrawer = (order: OrderDetails) => {
        setDrawerContent(order);
        setDrawerVisible(true);
    };

    const closeOrderDrawer = () => {
        setDrawerVisible(false);
        setDrawerContent(null);
    };

    const openOrderModal = (order: OrderDetails) => {
        setModalContent(order);
        setModalVisible(true);
        const newUrl = new URL(window.location.href);
        newUrl.searchParams.set('view', order.order.orderId.toString());
        window.history.pushState({}, '', newUrl.toString());
    };

    const closeOrderModal = () => {
        setModalVisible(false);
        setModalContent(null);

        const newUrl = new URL(window.location.href);
        newUrl.searchParams.delete('view');
        window.history.pushState({}, '', newUrl.toString());
    };

    const openMoveOrderModal = (order: OrderDetails) => {
        setMoveOrderContent(order);
        setMoveOrderModalVisible(true);
    }

    const closeMoveOrderModal = () => {
        setMoveOrderContent(null);
        setMoveOrderModalVisible(false);
    }

    const [instanceId] = useState(() => Symbol("instance-id"));

    useEffect(() => {

        if(!scrollableRef.current) return

        const element = scrollableRef.current;
        invariant(element, 'element is null');
        const cleanup = combine(
            monitorForElements({
                canMonitor({ source }) {
                    return source.data.instanceId === instanceId;
                },
                onDrop(args) {
                    const { location, source } = args;

                    if (!location.current.dropTargets.length) {
                        console.warn("No drop targets found");
                        return;
                    }

                    if (source.data.type === "column") {
                        const sourceId = String(source.data.columnId);
                        const [target] = location.current.dropTargets;
                        const targetId = String(target.data.columnId);

                        const startIndex = data.orderedColumnIds.findIndex(
                            (columnId) => columnId === sourceId
                        );
                        const indexOfTarget = data.orderedColumnIds.findIndex(
                            (id) => id === targetId
                        );

                        const closestEdgeOfTarget = extractClosestEdge(target.data);

                        const finishIndex = getReorderDestinationIndex({
                            startIndex,
                            indexOfTarget,
                            closestEdgeOfTarget,
                            axis: "horizontal",
                        });

                        if (startIndex === -1 || finishIndex === -1) {
                            console.warn("Invalid column reorder indices", {
                                sourceId,
                                targetId,
                                startIndex,
                                indexOfTarget,
                                finishIndex,
                                orderedColumnIds: data.orderedColumnIds,
                            });
                            return;
                        }

                        reorderColumn({ startIndex, finishIndex, trigger: "pointer" });
                    }

                    if (source.data.type === "card") {
                        let id = source.data.id;
                        if (typeof id !== "string") id = String(id);

                        invariant(typeof id === "string");

                        if (!location.initial || !location.initial.dropTargets?.length) {
                            console.warn("Initial drop targets missing");
                            return;
                        }

                        const [, startColumnRecord] = location.initial.dropTargets;
                        let sourceId = startColumnRecord.data.columnId;
                        if (typeof sourceId !== "string") sourceId = String(sourceId);
                        invariant(typeof sourceId === "string");

                        const sourceColumn = data.columnMap[sourceId];
                        if (!sourceColumn) {
                            console.warn("Source column not found", sourceId);
                            return;
                        }

                        const itemIndex = sourceColumn.items.findIndex((item) => String(item.id) === id);

                        if (itemIndex === -1) {
                            console.warn("Dragged item not found in source column", {
                                sourceColumn,
                                itemId: id,
                            });
                            return;
                        }

                        if (location.current.dropTargets.length === 1) {
                            const [destinationColumnRecord] = location.current.dropTargets;
                            const destinationId = String(destinationColumnRecord.data.columnId);

                            invariant(typeof destinationId === "string");

                            const destinationColumn = data.columnMap[destinationId];
                            invariant(destinationColumn);

                            if (sourceColumn === destinationColumn) {
                                const destinationIndex = getReorderDestinationIndex({
                                    startIndex: itemIndex,
                                    indexOfTarget: sourceColumn.items.length - 1,
                                    closestEdgeOfTarget: null,
                                    axis: "vertical",
                                });

                                if (destinationIndex === -1) {
                                    console.warn("Invalid reorder index in same column");
                                    return;
                                }

                                reorderCard({
                                    columnId: sourceColumn.columnId,
                                    startIndex: itemIndex,
                                    finishIndex: destinationIndex,
                                    trigger: "pointer",
                                });
                                return;
                            }

                            moveCard({
                                itemIndexInStartColumn: itemIndex,
                                startColumnId: sourceColumn.columnId,
                                finishColumnId: destinationColumn.columnId,
                                trigger: "pointer",
                            });
                            return;
                        }

                        if (location.current.dropTargets.length === 2) {
                            const [destinationCardRecord, destinationColumnRecord] = location.current.dropTargets;
                            let destinationColumnId = destinationColumnRecord.data.columnId;
                            if (typeof destinationColumnId !== "string") {
                                destinationColumnId = String(destinationColumnId);
                            }

                            invariant(typeof destinationColumnId === "string");

                            const destinationColumn = data.columnMap[destinationColumnId];
                            invariant(destinationColumn);

                            const indexOfTarget = destinationColumn.items.findIndex(
                                (item) => item.id === destinationCardRecord.data.id
                            );
                            const closestEdgeOfTarget = extractClosestEdge(destinationCardRecord.data);

                            if (indexOfTarget === -1) {
                                console.warn("Target card not found in destination column");
                                return;
                            }

                            if (sourceColumn === destinationColumn) {
                                const destinationIndex = getReorderDestinationIndex({
                                    startIndex: itemIndex,
                                    indexOfTarget,
                                    closestEdgeOfTarget,
                                    axis: "vertical",
                                });

                                if (destinationIndex === -1) {
                                    console.warn("Invalid reorder index in same column");
                                    return;
                                }

                                reorderCard({
                                    columnId: sourceColumn.columnId,
                                    startIndex: itemIndex,
                                    finishIndex: destinationIndex,
                                    trigger: "pointer",
                                });
                                return;
                            }

                            const destinationIndex = closestEdgeOfTarget === "bottom" ? indexOfTarget + 1 : indexOfTarget;

                            moveCard({
                                itemIndexInStartColumn: itemIndex,
                                startColumnId: sourceColumn.columnId,
                                finishColumnId: destinationColumn.columnId,
                                itemIndexInFinishColumn: destinationIndex,
                                trigger: "pointer",
                            });
                        }
                    }
                },
            }),
            autoScrollForElements({
                canScroll({ source }) {
                    return source.data.instanceId === instanceId;
                },
                getConfiguration: () => ({ maxScrollSpeed: 'fast' }),
                element,
            }),
            unsafeOverflowAutoScrollForElements({
                element,
                getConfiguration: () => ({ maxScrollSpeed: 'fast' }),
                canScroll({ source }) {
                    return true;
                },
                getOverflow() {
                    return {
                        forLeftEdge: {
                            top: 1000,
                            left: 1000,
                            bottom: 1000,
                        },
                        forRightEdge: {
                            top: 1000,
                            right: 1000,
                            bottom: 1000,
                        },
                    };
                },
            }),
        );
        return () => cleanup();
    }, [data, instanceId, moveCard, reorderCard, reorderColumn]);

    // Panning the board
    useEffect(() => {

        if (!scrollableRef.current) {
            return;
        }

        let cleanupActive: CleanupFn | null = null;
        const scrollable = scrollableRef.current;
        invariant(scrollable);

        function begin({ startX }: { startX: number }) {
            let lastX = startX;

            const cleanupEvents = bindAll(
                window,
                [
                    {
                        type: 'pointermove',
                        listener(event: PointerEvent): void {
                            const currentX = event.clientX;
                            const diffX = lastX - currentX;

                            lastX = currentX;
                            scrollable?.scrollBy({ left: diffX });
                        },
                    },
                    // stop panning if we see any of these events
                    ...(
                        [
                            'pointercancel',
                            'pointerup',
                            'pointerdown',
                            'keydown',
                            'resize',
                            'click',
                            'visibilitychange',
                        ] as const
                    ).map((eventName) => ({
                        type: eventName,
                        listener: (): void => cleanupEvents(),
                    })),
                ],
                { capture: true },
            );

            cleanupActive = cleanupEvents;
        }

        const cleanupStart = bindAll(scrollable, [
            {
                type: 'pointerdown',
                listener(event: PointerEvent): void {
                    if (!(event.target instanceof HTMLElement)) {
                        return;
                    }
                    // ignore interactive elements
                    if (event.target.closest(`[${blockBoardPanningAttr}]`)) {
                        return;
                    }

                    begin({ startX: event.clientX });
                },
            },
        ]);

        return function cleanupAll() {
            cleanupStart();
            cleanupActive?.();
        };
    }, []);

    useEffect(() => {
        return liveRegion.cleanup();
    }, []);

    useEffect(() => {
        return () => {
            setDrawerVisible(false);
            setDrawerContent(null);
            setModalVisible(false);
            setModalContent(null);
        };
    }, []);

    const contextValue = useMemo<BoardContextValue>(() => ({
        getColumns,
        reorderColumn,
        reorderCard,
        moveCard,
        removeCard,
        registerCard: registry.registerCard,
        registerColumn: registry.registerColumn,
        instanceId,
        openOrderDrawer,
        closeOrderDrawer,
        drawerContent,
        drawerVisible,
        openOrderModal,
        closeOrderModal,
        modalContent,
        modalVisible,
        moveOrderModalVisible,
        openMoveOrderModal,
        closeMoveOrderModal
    }), [
        registry,
        drawerContent,
        drawerVisible,
        modalContent,
        modalVisible,
        moveOrderModalVisible
    ]);


    // if (!lists || lists.length === 0) {
    //     return (
    //         <div style={{
    //             display: 'flex',
    //             justifyContent: 'center',
    //             alignItems: 'center',
    //             height: '100vh',
    //             width: '100%'
    //         }}>
    //             <Spin size="large" />
    //         </div>
    //     );
    // }

    useEffect(() => liveRegion.cleanup(), []);
    const topScrollbarRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (scrollableRef.current && topScrollbarRef.current) {
            const scrollWidth = scrollableRef.current.scrollWidth;
            const topScrollbar = topScrollbarRef.current.querySelector('.top__scrollbar') as HTMLDivElement;
            if (topScrollbar) {
                topScrollbar.style.width = `${scrollWidth}px`;
            }
            topScrollbarRef.current.style.overflowX = 'auto';
        }
    }, [data.orderedColumnIds]);

    useEffect(() => {
        const scrollable = scrollableRef.current;
        const topScrollbar = topScrollbarRef.current;

        if (scrollable && topScrollbar) {
            const handleScroll = () => syncScroll("content");
            scrollable.addEventListener('scroll', handleScroll);

            return () => {
                scrollable.removeEventListener('scroll', handleScroll);
            };
        }
    }, []);

    useEffect(() => {
        if (events.length > 0) {
            const relevantEvents = events.filter((event: MercureEvent) => event.topic === MERCURE_TOPICS.WAREHOUSE_ORDER_CHANGED_SHIP_BY || event.topic === MERCURE_TOPICS.WAREHOUSE_ORDER_REMOVED);
            relevantEvents.forEach((event: MercureEvent) => closeModalDrawer(event.data));
        }
    },[events])

    const closeModalDrawer = (data: any) => {
        if (drawerContent && data.warehouseOrderId === drawerContent.id) {
            closeOrderDrawer();
        }

        if (modalContent && data.warehouseOrderId === modalContent.id) {
            closeOrderModal();
        }
    }

    useEffect(() => {
        const drawerShipBy = drawerContent?.shipBy ? normalizeDate(drawerContent.shipBy) : null;
        const modalShipBy = modalContent?.shipBy ? normalizeDate(modalContent.shipBy) : null;

        if (!drawerShipBy && !modalShipBy) return;

        stableLists.current.forEach((list: ListProps) => {
            const listShipBy = normalizeDate(list.shipBy);

            const isDrawerMatch = drawerShipBy && drawerShipBy == listShipBy;
            const isModalMatch = modalShipBy && modalShipBy == listShipBy;

            if (!isDrawerMatch && !isModalMatch) return;

            list.warehouseOrders.forEach((order) => {
                if (drawerContent?.id === order.id) {
                    setDrawerContent(order);
                }
                if (modalContent?.id === order.id) {
                    setModalContent(order);
                }
            });
        });
    }, [drawerContent?.id, modalContent?.id, lists]);

    const syncScroll = (source: "top" | "content") => {
        if (source === "top") {
            if (scrollableRef.current && topScrollbarRef.current) {
                scrollableRef.current.scrollLeft = topScrollbarRef.current.scrollLeft;
            }
        } else if (source === "content") {
            if (scrollableRef.current && topScrollbarRef.current) {
                topScrollbarRef.current.scrollLeft = scrollableRef.current.scrollLeft;
            }
        }
    };

    return (
        <>
            {isFiltering && (
                <div
                    style={{
                        position: 'fixed',
                        top: 0,
                        left: 0,
                        right: 0,
                        bottom: 0,
                        display: 'flex',
                        justifyContent: 'center',
                        alignItems: 'center',
                        backgroundColor: 'rgba(255, 255, 255, 0.6)',
                        zIndex: 9999,
                    }}
                >
                    <Spin size="large" />
                </div>
            )}
            <Suspense fallback={<Spin size="small" style={{ display: 'flex', justifyContent: 'center' }} />}>
                <OrderFilter />
            </Suspense>
            <div
                className={"top_scroll_bar_container queue-board-scrollable"}
                onScroll={() => syncScroll("top")}
                ref={topScrollbarRef}
            >
                <div className={"top__scrollbar queue-board-scrollable"}></div>
            </div>
            <div
                ref={scrollableRef}
                className={`d-flex flex-row queue-board-scrollable`}
                style={{ overflowX: 'auto', overflowY: 'auto' }}
            >
                <Suspense
                    fallback={
                        <div
                            style={{
                                position: 'fixed',
                                top: 0,
                                left: 0,
                                right: 0,
                                bottom: 0,
                                display: 'flex',
                                justifyContent: 'center',
                                alignItems: 'center',
                                backgroundColor: 'rgba(255, 255, 255, 0.6)',
                                zIndex: 9999,
                            }}
                        >
                            <Spin size="large" />
                        </div>
                    }
                >
                    <BoardContext.Provider value={contextValue}>
                        <Board>
                            <>
                                {data.orderedColumnIds.map((columnId) => {
                                    return <Column column={data.columnMap[columnId]} key={columnId} />;
                                })}
                            </>
                        </Board>
                        {modalVisible && (
                            <Suspense
                                fallback={
                                    <div style={{
                                        position: 'fixed',
                                        top: 0, left: 0, right: 0, bottom: 0,
                                        display: 'flex',
                                        justifyContent: 'center',
                                        alignItems: 'center',
                                        backgroundColor: 'rgba(255, 255, 255, 0.6)',
                                        zIndex: 9999
                                    }}>
                                        <Spin size="large" />
                                    </div>
                                }
                            >
                                <OrderDetailsModal
                                    warehouseOrder={modalContent}
                                    modalVisible={modalVisible}
                                    onClose={closeOrderModal}
                                />
                            </Suspense>
                        )}
                        {drawerVisible && (
                            <Suspense
                                fallback={
                                    <div style={{
                                        position: 'fixed',
                                        top: 0, left: 0, right: 0, bottom: 0,
                                        display: 'flex',
                                        justifyContent: 'center',
                                        alignItems: 'center',
                                        backgroundColor: 'rgba(255, 255, 255, 0.6)',
                                        zIndex: 9999
                                    }}>
                                        <Spin size="large" />
                                    </div>
                                }
                            >
                                <OrderDetailsDrawer
                                    warehouseOrder={drawerContent}
                                    drawerVisible={drawerVisible}
                                    onClose={closeOrderDrawer}
                                />
                            </Suspense>
                        )}
                    </BoardContext.Provider>
                    <CreateShipByList />
                </Suspense>
            </div>
        </>
    );
});

export default OrderQueueBoard;