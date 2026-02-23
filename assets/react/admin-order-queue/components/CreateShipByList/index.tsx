import React, { useState } from 'react';
import { Button, DatePicker, Form, Space, message, Row, Col, notification } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import { Container, StyledCard, StyledButton, AddButton, RemoveButton } from './styled';
import axios from 'axios';
import dayjs from 'dayjs';
import { useAppSelector } from '@react/admin-order-queue/hook';
import { shallowEqual } from 'react-redux';

const CreateShipByList = () => {
    const [form] = Form.useForm();
    const [shipByDates, setShipByDates] = useState<string[]>([]);
    const [loading, setLoading] = useState<boolean>(false);
    const printer = useAppSelector((state) => state.config.printer, shallowEqual);

    const handleAddDate = () => {
        setShipByDates([...shipByDates, '']);
    };

    const handleRemoveDate = (index: number) => {
        const updatedDates = shipByDates.filter((_, i) => i !== index);
        setShipByDates(updatedDates);
    };

    const handleDateChange = (index: number, date: any) => {
        const updatedDates = [...shipByDates];
        updatedDates[index] = date ? dayjs(date).format('YYYY-MM-DD') : '';
        setShipByDates(updatedDates);
    };

    const handleSubmit = async () => {
        try {
            if (shipByDates.some((date) => !date)) {
                message.error('Please provide all Ship By dates.');
                return;
            }
            setLoading(true);

            await axios.post('/warehouse/queue-api/warehouse-orders/create-ship-by', {
                shipByDates: shipByDates,
                printer: printer
            }).then((response) => {
                if (response.status === 200) {
                    notification.success({
                        message: response.data.message,
                        description: 'Ship By list has been created successfully.',
                    });
                }else{
                    notification.error({
                        message: 'Error',
                        description: 'Error creating Ship By list.',
                    });
                }
            }).catch((error) => {
                notification.error({
                    message: 'Error',
                    description: 'Error creating Ship By list.',
                });
            })

            form.resetFields();
            setShipByDates([]);
        } catch (error) {
            message.error('Please fill in all required fields.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <Container>
            <StyledCard>
                <Form
                    form={form}
                    name="shipByForm"
                    onFinish={handleSubmit}
                >
                    {shipByDates.map((date, index) => (
                        <Row key={index} gutter={[4, 4]} align="middle" style={{ marginBottom: '10px' }}>
                            <Col xs={16} md={16}>
                                <Form.Item
                                    name={['shipByDates', index]}
                                    rules={[{ required: true, message: 'Please select a date' }]}
                                >
                                    <DatePicker
                                        value={date ? dayjs(date) : null}
                                        onChange={(value) => handleDateChange(index, value)}
                                        format="D-M-YYYY"
                                        style={{ width: '100%' }}
                                    />
                                </Form.Item>
                            </Col>
                            <Col xs={4} md={4}>
                                <RemoveButton danger onClick={() => handleRemoveDate(index)}>
                                    Remove
                                </RemoveButton>
                            </Col>
                        </Row>
                    ))}
                    <Row justify="center">
                        <Col>
                            <AddButton
                                type="dashed"
                                icon={<PlusOutlined />}
                                onClick={handleAddDate}
                            >
                                Create Ship By List
                            </AddButton>
                        </Col>
                    </Row>

                    {shipByDates.length > 0 && (
                        <Form.Item>
                            <StyledButton type="primary" htmlType="submit" block loading={loading}>
                                Submit Ship By Dates
                            </StyledButton>
                        </Form.Item>
                    )}
                </Form>
            </StyledCard>
        </Container>
    );
};

export default CreateShipByList;

