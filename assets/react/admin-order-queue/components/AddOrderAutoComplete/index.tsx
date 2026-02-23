import React, { useState, useEffect, memo } from "react";
import { AddOrderFrom, StyledRow, AddOrderWrapper, NoDataRender } from "./styled";
import { Button, Card, Col, Form, message, Row, Select, Spin } from "antd";
import { PlusOutlined } from "@ant-design/icons";
import axios from "axios";
import { useAppSelector } from "@react/admin-order-queue/hook";
import moment from "moment";
import { ColumnType } from "@react/admin-order-queue/Types/BoardTypes";
import { AddShipByButton } from "../ShipByList/styled";
import { shallowEqual } from "react-redux";

interface AddOrderAutoCompleteProps {
    shipBy: string;
    listId: string | number;
}

const AddOrderAutoComplete = memo(({ shipBy, listId }: AddOrderAutoCompleteProps) => {
    const [loading, setLoading] = useState<boolean>(false);
    const [orders, setOrders] = useState<{ value: string; label: string }[]>([]);
    const [searchQuery, setSearchQuery] = useState<string>("");
    const [isFetching, setIsFetching] = useState<boolean>(false);
    const [form] = Form.useForm();

    const printer = useAppSelector((state) => state.config.printer, shallowEqual);

    useEffect(() => {
        const fetchOrders = async () => {
            if (searchQuery.trim()) {
                setIsFetching(true);
                try {
                    const response = await axios.post("/warehouse/queue-api/warehouse-orders/filter-orders", {
                        query: searchQuery,
                    });

                    if (response.data.length === 0) {
                        message.error(response.data.message);
                        return;
                    }

                    setOrders(
                        response.data.map((order: any) => ({
                            value: order.orderId,
                            label: order.orderId,
                        }))
                    );
                } catch (error) {
                    message.error("OrderID not found", 5);
                } finally {
                    setIsFetching(false);
                }
            } else {
                setOrders([]);
                setIsFetching(false);
            }
        };

        const timeout = setTimeout(fetchOrders, 300);
        return () => clearTimeout(timeout);
    }, [searchQuery]);

    const onFinish = async (values: any) => {
        setLoading(true);
        try {

            axios.post("/warehouse/queue-api/warehouse-orders/add-orders", {
                orders: values.orders,
                printerName: printer,
                shipBy: moment(shipBy).format("YYYY-MM-DD"),
            }).then((response) => {
                message.success("Order has been added successfully.", 5);
            }).catch((error) => {
                message.error("Error adding order: " + error, 5);
            })

            const orderIds = values.orders.join(", ");
            message.success("Order IDs " + orderIds + " added successfully! ", 5);
            form.resetFields();
        } catch (error) {
            console.error("Error submitting form:", error);
            message.error("Error submitting form"+ error, 5);
        } finally {
            setLoading(false);
        }
    };

    return (
        <AddOrderWrapper>
            <AddOrderFrom
                form={form}
                layout="horizontal"
                name={`AddOrderForm-${listId}`}
                onFinish={onFinish}
            >
                <StyledRow>
                    <Col xs={20} sm={20} md={20} lg={20}>
                        <Form.Item
                            name="orders"
                            rules={[{ required: true, message: "" }]}
                        >
                            <Select
                                mode="multiple"
                                size="small"
                                notFoundContent={isFetching ?
                                <>
                                    <NoDataRender bordered={false}>
                                        <Spin size="small" />
                                    </NoDataRender>
                                </> : null}
                                allowClear
                                showSearch
                                placeholder="Select Order"
                                options={orders}
                                onSearch={setSearchQuery}
                            />
                        </Form.Item>
                    </Col>
                    <Col xs={20} sm={20} md={3} lg={3}>
                        <Form.Item>
                            <AddShipByButton
                                type="primary"
                                htmlType="submit"
                                size="small"
                                loading={loading}
                                icon={<PlusOutlined style={{ fontSize: "14px" }} />}
                            />
                        </Form.Item>
                    </Col>
                </StyledRow>
            </AddOrderFrom>
        </AddOrderWrapper>
    );
});

export default AddOrderAutoComplete;
