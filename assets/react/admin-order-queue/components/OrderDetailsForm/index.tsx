import React, { useEffect, useState } from 'react';
import { Form, Input, Select, DatePicker, Button, Row, Col, notification } from 'antd';
import { OrderDetails } from '@react/admin-order-queue/redux/reducer/config/interface';
import { StyledForm } from './styled';
import { makeFormChoices } from '@react/admin-order-queue/constants/warehouse.constants';
import { activeTags, buildOrderTagOptions } from '@react/admin-order-queue/helper';
import axios from 'axios';
import dayjs from 'dayjs';
import weekday from "dayjs/plugin/weekday";
import localeData from "dayjs/plugin/localeData";
dayjs.extend(weekday);
dayjs.extend(localeData);
dayjs.locale("en");

const { Option } = Select;

interface OrderDetailsFormProps {
    warehouseOrder: OrderDetails;
}

const OrderDetailsForm = ({ warehouseOrder }: OrderDetailsFormProps) => {
    const shippingChoices = makeFormChoices();
    const orderTagOptions = buildOrderTagOptions();

    const [formData, setFormData] = useState({
        printer: warehouseOrder.printerName,
        shippingService: warehouseOrder.shippingService,
        driveLink: warehouseOrder.driveLink,
        shipBy: dayjs(warehouseOrder.shipBy),
        mustShip: warehouseOrder.order.metaData.mustShip?.date ? dayjs(warehouseOrder.order.metaData.mustShip.date) : null,
        warehouseOrderId: warehouseOrder.id,
        orderTag: activeTags(warehouseOrder.order.metaData.tags ?? {}),
    });

    const [loading, setLoading] = useState<boolean>(false);
    const [prevPrinter, setPrevPrinter] = useState<string>(warehouseOrder.printerName);

    useEffect(() => {
        setFormData({
            printer: warehouseOrder.printerName,
            shippingService: warehouseOrder.shippingService,
            driveLink: warehouseOrder.driveLink,
            shipBy: dayjs(warehouseOrder.shipBy),
            mustShip: warehouseOrder.order.metaData.mustShip?.date ? dayjs(warehouseOrder.order.metaData.mustShip.date) : null,
            warehouseOrderId: warehouseOrder.id,
            orderTag: activeTags(warehouseOrder.order.metaData.tags ?? {}),
        });
        setPrevPrinter(warehouseOrder.printerName);
    }, [warehouseOrder]);

    const onFinish = (values: any) => {
        setLoading(true);

        setFormData({
            ...formData,
            ...values,
        });

        const updateOrder = (updatedValues: any) => {
            return axios.post('/warehouse/queue-api/warehouse-orders/update', updatedValues)
                .then(function (response) {
                    notification.success({
                        message: 'Warehouse Updated Successfully',
                        description: 'Warehouse order has been successfully updated.',
                    });
                })
                .catch(function (error) {
                    console.error(error);
                    notification.error({
                        message: 'Order Update Failed',
                        description: 'There was an issue updating the order. Please try again later.',
                    });
                });
        };

        const preparePayload = (vals: any) => ({
            id: warehouseOrder.id,
            printerName: vals.printer,
            shippingService: vals.shippingService,
            driveLink: vals.driveLink,
            shipBy: vals.shipBy,
            metaData: {
                tags: vals.orderTag,
                mustShip: vals.mustShip ? {
                    name: 'Must Ship ' + vals.mustShip.format('MMM D, YYYY'),
                    date: vals.mustShip.format('YYYY-MM-DD')
                } : null,
            }
        });

        // If the printer has changed, hit the API twice
        if (values.printer !== prevPrinter) {
            const payload = preparePayload(values);
            
            axios.post('/warehouse/queue-api/warehouse-orders/update', payload)
                .then(() => {
                    return updateOrder(payload);
                })
                .catch(function (error) {
                    console.error(error);
                    notification.error({
                        message: 'Order Update Failed',
                        description: 'There was an issue updating the printer. Please try again later.',
                    });
                })
                .finally(() => {
                    setLoading(false);
                });
        } else {
            updateOrder(preparePayload(values)).finally(() => {
                setLoading(false);
            });
        }
    };

    return (
        <StyledForm
            layout="vertical"
            name={`${'orderDetailsForm' + warehouseOrder.id}`}
            onFinish={onFinish}
            initialValues={formData}
        >
            <Row gutter={[16, 16]}>
                <Col xs={24} sm={24} md={8}>
                    <Form.Item
                        label="Printer"
                        name="printer"
                        rules={[{ required: true, message: 'Please select a printer!' }]}
                    >
                        <Select placeholder="Select a printer">
                            <Option value="P1">P1</Option>
                            {/* <Option value="P2">P2</Option> */}
                            <Option value="P3">P3</Option>
                            <Option value="P4">P4</Option>
                            <Option value="P5">P5</Option>
                            <Option value="P6">P6</Option>
                            <Option value="P7">P7</Option>
                            <Option value="P8">P8</Option>
                            <Option value="P9">P9</Option>
                            <Option value="P10">P10</Option>
                        </Select>
                    </Form.Item>
                </Col>

                <Col xs={24} sm={24} md={8}>
                    <Form.Item
                        label="Ship By"
                        name="shipBy"
                        rules={[{ required: true, message: 'Please select a date!' }]}
                    >
                        <DatePicker disabled={warehouseOrder.order.metaData.mustShip ? true : false} format="D-M-YYYY" value={warehouseOrder.shipBy ? dayjs(warehouseOrder.shipBy) : null} style={{ width: '100%' }} placeholder="Select a date" />
                    </Form.Item>
                </Col>

                <Col xs={24} sm={24} md={8}>
                    <Form.Item
                        label="Must Ship"
                        name="mustShip"
                        rules={[{ required: false, message: 'Please select a date!' }]}
                    >
                        <DatePicker disabledDate={(current) => current && current < dayjs().startOf('day')} format="D-M-YYYY" style={{ width: '100%' }} placeholder="Select a date" />
                    </Form.Item>
                </Col>
            </Row>

            <Row gutter={[16, 16]}>
                <Col xs={24} sm={24} md={12}>
                    <Form.Item
                        label="Shipping Service"
                        name="shippingService"
                        rules={[{ required: true, message: 'Please select a shipping service!' }]}
                    >
                        <Select placeholder="Select a shipping service" value={formData.shippingService} options={shippingChoices} popupMatchSelectWidth={false} />
                    </Form.Item>
                </Col>

                <Col xs={24} sm={24} md={12}>
                    <Form.Item
                        label="Order Tag"
                        name="orderTag"
                        rules={[{ required: false, message: 'Please select at least one order tag!' }]}
                    >
                        <Select
                            mode="multiple"
                            placeholder="Select order tags"
                            value={formData.orderTag}
                            options={orderTagOptions}
                        />
                    </Form.Item>
                </Col>
            </Row>

            <Row gutter={[16, 16]}>
                <Col span={24}>
                    <Form.Item
                        label="Drive Link"
                        name="driveLink"
                        rules={[{ type: 'url', message: 'Please enter a valid URL!' }]}
                    >
                        <Input placeholder="Enter a Drive link" />
                    </Form.Item>
                </Col>
            </Row>

            <Row gutter={[16, 16]}>
                <Col span={24}>
                    <Form.Item name="warehouseOrderId" noStyle>
                        <Input type="hidden" name="warehouseOrderId" value={formData.warehouseOrderId} />
                    </Form.Item>
                    <Form.Item style={{ marginBottom: 0 }}>
                        <Button type="primary" htmlType="submit" loading={loading}>
                            Save
                        </Button>
                    </Form.Item>
                </Col>
            </Row>
        </StyledForm>
    );
};

export default OrderDetailsForm;