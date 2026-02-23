import React, { useState } from "react";
import { Form, Select, Button, message, Col, Row, Spin } from "antd";
import { PlusOutlined } from "@ant-design/icons";
import axios from "axios";
import moment from "moment";
import { OrderDetails } from "@react/admin-order-queue/redux/reducer/config/interface";
import { OrderGroupForm, WareHouseOrderGroupWrapper } from "./styled";
import { useAppSelector } from "@react/admin-order-queue/hook";

interface OrderDetailsFormProps {
    warehouseOrder: OrderDetails;
}

const WarehouseOrderGroup = ({ warehouseOrder }: OrderDetailsFormProps) => {
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(false);
    const config = useAppSelector((state) => state.config);

    const list = config.lists.find(
        (list) => moment(list.shipBy).format("YYYY-MM-DD") === moment(warehouseOrder.shipBy).format("YYYY-MM-DD")
    );

    const initialFormData = {
        orders: [warehouseOrder.id],
        printer: warehouseOrder.printerName,
        shipBy: moment(warehouseOrder.shipBy).format("YYYY-MM-DD"),
    };

    const onFinish = async (values: any) => {
        setLoading(true);
        try {
            const response = await axios.post(
                "/warehouse/queue-api/warehouse-orders/group-orders",
                {
                    orders: [...values.orders],
                    selectedWarehouseOrder: warehouseOrder.id,
                    printer: config.printer,
                    shipBy: moment(warehouseOrder.shipBy).format("YYYY-MM-DD"),
                }
            );

            if (response.status === 200) {
                message.success(response.data.message, 5);
            } else {
                message.error("Failed to group orders.", 5);
            }
        } catch (error: any) {
            console.error("Error submitting form:", error);
            message.error(`Error submitting form: ${error.message || "Unexpected error"}`, 5);
        } finally {
            setLoading(false);
        }
    };

    return (
        <WareHouseOrderGroupWrapper>
            <OrderGroupForm
                form={form}
                layout="vertical"
                name={`warehouse-order-group-${warehouseOrder.id}`}
                onFinish={onFinish}
                initialValues={initialFormData}
            >
                <Row gutter={16} align="middle">
                    <Col md={20}>
                        <Form.Item
                            label="Group Orders"
                            name="orders"
                            rules={[{ required: false, message: "Please select at least one order!" }]}
                        >
                            <Select
                                title="Group Order"
                                mode="multiple"
                                allowClear
                                placeholder="Select Order"
                                options={list?.warehouseOrders.map((order) => ({ label: order.id, value: order.id }))}
                                disabled={loading}
                            />
                        </Form.Item>
                    </Col>
                    <Col md={4}>
                        <Form.Item label="&nbsp;">
                            <Button
                                type="primary"
                                htmlType="submit"
                                // icon={<PlusOutlined />}
                                block
                                loading={loading}
                            >
                                Update
                            </Button>
                        </Form.Item>
                    </Col>
                </Row>
            </OrderGroupForm>
        </WareHouseOrderGroupWrapper>
    );
};

export default WarehouseOrderGroup;
