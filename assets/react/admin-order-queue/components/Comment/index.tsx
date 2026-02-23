import React, { useState } from 'react';
import { Form, Input, Button, Spin, Row, Col, message } from 'antd';
import axios from 'axios';
import { OrderDetails } from '@react/admin-order-queue/redux/reducer/config/interface';
import { useAppDispatch } from '@react/admin-order-queue/hook.ts';
import actions from '@react/admin-order-queue/redux/actions';
import { CommentCard, CommentWrapper } from './styled';

const { TextArea } = Input;

const Comment = ({ warehouseOrder }: { warehouseOrder: OrderDetails }) => {
    const dispatch = useAppDispatch();
    const [loading, setLoading] = useState<boolean>(false);

    const [form] = Form.useForm();

    const onFinish = async (values: any) => {
        await saveComment(warehouseOrder.id, values.comment);
    };

    const saveComment = async (warehouseOrderId: string, comment: string) => {
        setLoading(true);
        try {
            const response = await axios.post('/warehouse/queue-api/warehouse-orders/comment', {
                id: warehouseOrderId,
                comment: comment,
            }).then((response) => {
                message.success('Comment saved successfully!');
                // dispatch(actions.config.updateWarehouseOrder(response.data.data));
            }).catch((error) => {
                message.error('Error saving comment:' + error, 5);
            });
            form.resetFields();
        } catch (error) {
            console.error('Error saving comment:', error);
        } finally {
            setLoading(false);
        }
    };

    return (
        <CommentWrapper>
            <Form
                name="commentForm"
                layout="vertical"
                onFinish={onFinish}
                initialValues={{
                    comment: '',
                }}
                form={form}
                style={{ position: 'relative' }}
            >
                <Row gutter={16}>
                    <Col span={20}>
                        <Form.Item
                            label="Comment"
                            name="comment"
                            className="comment-form"
                            rules={[{ required: true, message: 'Please add a comment!' }]}
                        >
                            <div style={{ position: 'relative' }}>
                                {loading && (
                                    <div
                                        style={{
                                            position: 'absolute',
                                            top: '50%',
                                            left: '50%',
                                            transform: 'translate(-50%, -50%)',
                                            zIndex: 1,
                                        }}
                                    >
                                        <Spin size="small" />
                                    </div>
                                )}
                                <TextArea
                                    placeholder="Write your comment here..."
                                    allowClear
                                    rows={2}
                                    disabled={loading}
                                />
                            </div>
                        </Form.Item>
                    </Col>
                    <Col span={4}>
                        <Form.Item>
                            <Button
                                type="primary"
                                htmlType="submit"
                                loading={loading}
                                style={{ marginTop: 32, width: '100%' }}
                                >
                                Submit
                            </Button>
                        </Form.Item>
                    </Col>
                </Row>
            </Form>
        </CommentWrapper>
    );
};

export default Comment;
