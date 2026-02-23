import React, {
    forwardRef,
    Fragment,
    memo,
    type Ref,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';
import ReactDOM from 'react-dom';
import invariant from 'tiny-invariant';

import {
    attachClosestEdge,
    type Edge,
    extractClosestEdge,
} from '@atlaskit/pragmatic-drag-and-drop-hitbox/closest-edge';
import { DropIndicator } from '@atlaskit/pragmatic-drag-and-drop-react-drop-indicator/box';
import { combine } from '@atlaskit/pragmatic-drag-and-drop/combine';
import {
    draggable,
    dropTargetForElements,
} from '@atlaskit/pragmatic-drag-and-drop/element/adapter';
import { preserveOffsetOnSource } from '@atlaskit/pragmatic-drag-and-drop/element/preserve-offset-on-source';
import { setCustomNativeDragPreview } from '@atlaskit/pragmatic-drag-and-drop/element/set-custom-native-drag-preview';
import { dropTargetForExternal } from '@atlaskit/pragmatic-drag-and-drop/external/adapter';

import { OrderDetails } from '@react/admin-order-queue/redux/reducer/config/interface';
import { useBoardContext, type BoardContextValue } from '@react/admin-order-queue/context/BoardContext';
import WareHouseOrderCard from '../WareHouseOrderCard';
import { Skeleton } from 'antd';

type State =
    | { type: 'idle' }
    | { type: 'preview'; container: HTMLElement; rect: DOMRect }
    | { type: 'dragging' };

const idleState: State = { type: 'idle' };
const draggingState: State = { type: 'dragging' };

// === CardShadow ===
export function CardShadow({ dragging }: { dragging: DOMRect }) {
    return (
        <Skeleton.Node active={false} style={{ width: dragging.width ?? 160, height: dragging.height ?? 160 }} />
    );
}

// === Styles ===
const baseCardStyle: React.CSSProperties = {
    padding: 0,
    position: 'relative',
    display: 'flex',
    alignItems: 'center',
    boxShadow: '0 1px 3px rgba(0,0,0,0.1)',
    cursor: 'grab',
    transition: 'transform 200ms ease, opacity 200ms ease, box-shadow 200ms ease',
    marginTop: '10px',
};

const originalDraggingStyle: React.CSSProperties = {
    opacity: 0,
    pointerEvents: "none",
    height: "1px", // important for virtuoso
    visibility: "hidden",
};

const previewStyle: React.CSSProperties = {
    transform: 'rotate(2deg) scale(1.02)',
    boxShadow: '0 4px 12px rgba(0, 0, 0, 0.2)',
    opacity: 1,
};

type CardPrimitiveProps = {
    closestEdge: Edge | null;
    item: OrderDetails;
    state: State;
    isPreview?: boolean;
    actionMenuTriggerRef?: Ref<HTMLButtonElement>;
    onClick?: () => void;
};

const CardPrimitive = forwardRef<HTMLDivElement, CardPrimitiveProps>(function CardPrimitive(
    { closestEdge, item, state, isPreview = false, actionMenuTriggerRef, onClick },
    ref,
) {
    const { id } = item;
    const style: React.CSSProperties = {
        ...baseCardStyle,
        ...(state.type === 'dragging' && !isPreview ? originalDraggingStyle : {}),
        ...(isPreview ? previewStyle : {}),
    };

    return (
        <div ref={ref} data-testid={`item-${id}`} style={style} onClick={onClick}>
            <WareHouseOrderCard warehouseOrder={item} />
            <button ref={actionMenuTriggerRef} style={{ marginLeft: 'auto' }} hidden/>
        </div>
    );
});

export const Card = memo(function Card({ item }: { item: OrderDetails }) {
    const ref = useRef<HTMLDivElement | null>(null);
    const { id } = item;
    const [closestEdge, setClosestEdge] = useState<Edge | null>(null);
    const [state, setState] = useState<State>(idleState);
    const [orderIdFromUrl, setOrderIdFromUrl] = useState<string | null>(null);

    const actionMenuTriggerRef = useRef<HTMLButtonElement>(null);
    const { instanceId, registerCard, openOrderDrawer, openOrderModal } = useBoardContext();

    useEffect(() => {
        invariant(actionMenuTriggerRef.current, 'actionMenuTriggerRef is null');
        invariant(ref.current, 'ref is null');
        return registerCard({
            cardId: id,
            entry: {
                element: ref.current,
                actionMenuTrigger: actionMenuTriggerRef.current,
            },
        });
    }, [registerCard, id]);

    useEffect(() => {
        const element = ref.current;
        invariant(element, 'element is null');
        return combine(
            draggable({
                element,
                getInitialData: () => ({ type: 'card', id, instanceId }),
                onGenerateDragPreview: ({ location, source, nativeSetDragImage }) => {
                    const rect = source.element.getBoundingClientRect();
                    setCustomNativeDragPreview({
                        nativeSetDragImage,
                        getOffset: preserveOffsetOnSource({
                            element,
                            input: location.current.input,
                        }),
                        render({ container }) {
                            setState({ type: 'preview', container, rect });
                            return () => setState(draggingState);
                        },
                    });
                },
                onDragStart: () => setState(draggingState),
                onDrop: () => setState(idleState),
            }),
            dropTargetForExternal({ element }),
            dropTargetForElements({
                element,
                canDrop: ({ source }) =>
                    source.data.instanceId === instanceId && source.data.type === 'card',
                getIsSticky: () => true,
                getData: ({ input, element }) => {
                    const data = { type: 'card', id };
                    return attachClosestEdge(data, {
                        input,
                        element,
                        allowedEdges: ['top', 'bottom'],
                    });
                },
                onDragEnter: (args) => {
                    if (args.source.data.id !== id) {
                        setClosestEdge(extractClosestEdge(args.self.data));
                    }
                },
                onDrag: (args) => {
                    if (args.source.data.id !== id) {
                        setClosestEdge(extractClosestEdge(args.self.data));
                    }
                },
                onDragLeave: () => setClosestEdge(null),
                onDrop: () => setClosestEdge(null),
            }),
        );
    }, [instanceId, item, id]);

    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const orderId = params.get('view');
        setOrderIdFromUrl(orderId);
    }, []);

    useEffect(() => {
        if (orderIdFromUrl) {
            if (orderIdFromUrl === item.order.orderId.toString()) {
                openOrderModal(item);
            }
        }
    }, [orderIdFromUrl]);

    return (
        <Fragment>
            {closestEdge === 'top' && state.type !== 'dragging' && ref.current && (
                <CardShadow dragging={ref.current.getBoundingClientRect()} />
            )}

            <CardPrimitive
                ref={ref}
                item={item}
                state={state}
                closestEdge={closestEdge}
                actionMenuTriggerRef={actionMenuTriggerRef}
                onClick={() => openOrderDrawer(item)}
            />

            {closestEdge === 'bottom' && state.type !== 'dragging' && ref.current && (
                <CardShadow dragging={ref.current.getBoundingClientRect()} />
            )}

            {state.type === 'preview' &&
                ReactDOM.createPortal(
                    <div
                        style={{
                            boxSizing: 'border-box',
                            width: state.rect.width,
                            height: state.rect.height,
                        }}
                    >
                        <CardPrimitive
                            item={item}
                            state={draggingState}
                            isPreview={true}
                            closestEdge={null}
                        />
                    </div>,
                    state.container,
                )}
        </Fragment>
    );
});