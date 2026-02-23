import React, { useState, useMemo, useEffect } from 'react';
import { Select, Button, Space, message, Card, Typography, Flex } from 'antd';
import { ArrowRightOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import { useBoardContext } from '@react/admin-order-queue/context/BoardContext';
import { OrderDetails } from '@react/admin-order-queue/redux/reducer/config/interface';
import { Label, MoveOrderWrapper } from './styled';

const { Option } = Select;
const { Text } = Typography;

interface MoveOrderProps {
    warehouseOrder: OrderDetails;
}

const MoveOrder = ({ warehouseOrder }: MoveOrderProps) => {
    const { getColumns, moveCard, reorderCard, closeMoveOrderModal } = useBoardContext();
    const [refreshKey, setRefreshKey] = useState(0);
    const allColumns = useMemo(() => getColumns(), [getColumns, refreshKey]);

    const latestOrder = useMemo(() => {
        const allItems = allColumns.flatMap(col => col.items);
        const found = allItems.find(i => String(i.id) === String(warehouseOrder.id));
        return found || warehouseOrder;
    }, [allColumns, warehouseOrder]);

    const currentColumn = useMemo(() => {
        const found = allColumns.find(col =>
            col.items.some(i => String(i.id) === String(latestOrder.id))
        );
        return found || null;
    }, [allColumns, latestOrder.id]);

    const currentPosition = useMemo(() => {
        if (!currentColumn) return -1;
        const index = currentColumn.items.findIndex(
            i => String(i.id) === String(latestOrder.id)
        );
        return index;
    }, [currentColumn, latestOrder.id]);

    const [selectedColumn, setSelectedColumn] = useState<string>(currentColumn?.columnId || '');
    const [selectedPosition, setSelectedPosition] = useState<number>(
        currentPosition >= 0 ? currentPosition : 0
    );

    useEffect(() => {
        setSelectedColumn(currentColumn?.columnId || '');
    }, [currentColumn?.columnId]);

    useEffect(() => {
        if (currentPosition >= 0) {
            setSelectedPosition(currentPosition);
        }
    }, [currentPosition]);

    const targetColumn = useMemo(
        () => allColumns.find(col => col.columnId === selectedColumn),
        [allColumns, selectedColumn]
    );

    const handleColumnChange = (value: string) => {
        setSelectedColumn(value);
        const newTarget = allColumns.find(c => c.columnId === value);
        if (!newTarget) return;
        const isSame = newTarget.columnId === currentColumn?.columnId;
        if (isSame) {
            setSelectedPosition(currentPosition >= 0 ? currentPosition : 0);
        } else {
            setSelectedPosition(newTarget.items.length);
        }
    };

    const handleMove = () => {
        if (!targetColumn || !currentColumn || currentPosition === -1) {
            message.error('Cannot find current order position');
            return;
        }

        const sameColumn = targetColumn.columnId === currentColumn.columnId;
        const samePosition = sameColumn && selectedPosition === currentPosition;

        if (samePosition) {
            message.info('Order already at this position');
            return;
        }

        if (sameColumn) {
            reorderCard({
                columnId: currentColumn.columnId,
                startIndex: currentPosition,
                finishIndex: selectedPosition,
            });

            setTimeout(() => {
                setRefreshKey(prev => prev + 1);
            }, 100);
        } else {
            moveCard({
                startColumnId: currentColumn.columnId,
                finishColumnId: targetColumn.columnId,
                itemIndexInStartColumn: currentPosition,
                itemIndexInFinishColumn: selectedPosition,
            });

            setTimeout(() => {
                setRefreshKey(prev => prev + 1);
            }, 100);

            closeMoveOrderModal();
        }

        message.success(
            `Moved #${latestOrder.order?.orderId} to ${dayjs(targetColumn.title).format(
                'MMM D'
            )} • position ${selectedPosition + 1}`
        );
    };

    if (currentPosition === -1 || !currentColumn) {
        return (
            <Card
                size="small"
                style={{
                    background: '#fff1f0',
                    border: '1px solid #ffa39e',
                    borderRadius: 10,
                }}
            >
                <Text type="danger">Current column not found</Text>
            </Card>
        );
    }

    return (
        <MoveOrderWrapper>
            <Space direction="vertical" size="middle" style={{ width: '100%' }}>
                <Flex justify="space-between" align="center">
                    <Text strong>
                        Current:
                        <br />
                        <Text type="secondary">
                            {dayjs(currentColumn.title).format('MMM D')} • Position{' '}
                            {currentPosition + 1}/{currentColumn.items.length}
                        </Text>
                    </Text>
                </Flex>

                <Flex wrap={'wrap'} gap={8}>
                    <Flex vertical>
                        <Label>Ship By</Label>
                        <Select
                            value={selectedColumn}
                            onChange={handleColumnChange}
                            style={{ minWidth: 180 }}
                        >
                            {allColumns.map((c) => (
                                <Option key={c.columnId} value={c.columnId}>
                                    {dayjs(c.title).format('MMM D')} ({c.items.length})
                                </Option>
                            ))}
                        </Select>
                    </Flex>
                    <Flex vertical>
                        <Label>Position</Label>
                        <Select
                            value={selectedPosition}
                            onChange={(v) => setSelectedPosition(v)}
                            style={{ width: 120 }}
                            disabled={!targetColumn}
                        >
                            {targetColumn?.items.map((_, i) => (
                                <Option key={i} value={i}>
                                    {i + 1}
                                </Option>
                            ))}
                        </Select>
                    </Flex>
                    <Flex align="end">
                        <Button
                            type="primary"
                            icon={<ArrowRightOutlined />}
                            onClick={handleMove}
                            disabled={!targetColumn}
                        >
                            Move
                        </Button>
                    </Flex>
                </Flex>
            </Space>
        </MoveOrderWrapper>
    );
};

export default MoveOrder;