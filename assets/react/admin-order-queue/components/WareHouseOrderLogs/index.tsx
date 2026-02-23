import React, { useContext, useEffect, useState } from "react";
import { List, Typography, Space, message, Button, Modal, Spin } from "antd";
import { CommentLogsCard, StyledCard, StyledList } from "./styled";
import Comment from "@react/admin-order-queue/components/Comment";
import { OrderDetails, WarehouseOrderLog } from "@react/admin-order-queue/redux/reducer/config/interface";
import axios from "axios";
import { useAppDispatch } from '@react/admin-order-queue/hook.ts';
import actions from '@react/admin-order-queue/redux/actions';
import { MercureEvent, MERCURE_TOPICS } from "@react/admin-order-queue/constants/mercure.constant";
import { MercureContext } from "@react/admin-order-queue/context/MercureProvider";

const { Text } = Typography;

const WareHouseOrderLogs = ({ warehouseOrder }: { warehouseOrder: OrderDetails }) => {
    const [logs, setLogs] = useState<WarehouseOrderLog[]>([]);
    const [loading, setLoading] = useState<boolean>(false);
    const [error, setError] = useState<string | null>(null);
    const dispatch = useAppDispatch();

    const { events } = useContext(MercureContext);

    useEffect(() => {
        if (events.length > 0) {
            const relevantEvents = events.filter((event: MercureEvent) => event.topic === MERCURE_TOPICS['WAREHOUSE_ORDER_UPDATE_LOGS']);
            relevantEvents.forEach((event: MercureEvent) => {
                const data = event.data;
                if (data.id === warehouseOrder.id && data.logs) {
                    setLogs(data.logs);
                }
            });
        }
    },[events])

    const fetchLogs = async () => {
        setLoading(true);
        setError(null);
        try {
            const response = await axios.get(`/warehouse/queue-api/warehouse-orders/logs?id=${warehouseOrder.id}`);
            if (response.status === 200 && response.data.warehouseOrderLogs) {
                setLogs(response.data.warehouseOrderLogs);
            } else {
                throw new Error(response.data.error || "Failed to fetch logs.");
            }
        } catch (error: any) {
            setError(error.response?.data?.error || "Error fetching logs.");
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (warehouseOrder.id) {
            fetchLogs();
        }
    }, [warehouseOrder.id]);

    const handleDeleteLog = async (logId: string) => {
        try {
            const response = await axios.delete('/warehouse/queue-api/warehouse-orders/remove-log', {
                data: { id: warehouseOrder.id, logId: logId },
            });
            if (response.status === 200) {
                message.success(response.data.message);
                // dispatch(actions.config.updateWarehouseOrder(response.data.data));
                setLogs((prevLogs) => prevLogs.filter((log) => log.id !== logId));
            } else {
                message.error('An error occurred while deleting the log.');
            }
        } catch (error: any) {
            console.error('Error deleting log:', error);
            if (error.response && error.response.data && error.response.data.error) {
                message.error(error.response.data.error);
            } else {
                message.error('Failed to delete log. Please try again later.');
            }
        } finally {
            
        }
    };

    const confirmDeleteLog = (logId: string) => {
        Modal.confirm({
            title: "Are you sure you want to delete this log?",
            content: "This action cannot be undone.",
            okText: "Yes",
            okType: "danger",
            cancelText: "No",
            onOk: () => handleDeleteLog(logId),
        });
    };

    return (
        <CommentLogsCard title="History">
            {loading && <Spin size="default" style={{ display: "block", textAlign: "center", margin: "20px 0" }} />}
            {!loading && (
                <StyledList
                    itemLayout="vertical"
                    header={<Comment warehouseOrder={warehouseOrder} />}
                    dataSource={logs}
                    renderItem={(item: any) => (
                        <List.Item style={{ padding: 0 }}>
                            <StyledCard>
                                <div
                                    style={{ marginBottom: 6 }}
                                    dangerouslySetInnerHTML={{ __html: item.content }}
                                />
                                <Space direction="horizontal" style={{ fontSize: "11px", color: "#777" }}>
                                    <Text type="secondary" style={{ marginRight: 8 }}>
                                        Logged By: {item.loggedBy.name || item.loggedBy.email}
                                    </Text>
                                    <Text type="secondary">@{new Date(item.createdAt).toLocaleString()}</Text>
                                    {item.isManual && (
                                        <Button type="link" danger onClick={() => confirmDeleteLog(item.id)}>
                                            Delete
                                        </Button>
                                    )}
                                </Space>
                            </StyledCard>
                        </List.Item>
                    )}
                />
            )}
        </CommentLogsCard>
    );
};

export default WareHouseOrderLogs;