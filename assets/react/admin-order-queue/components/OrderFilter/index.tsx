import React, { memo, useEffect, useMemo, useRef, useState } from 'react';
import { Button, Card, Col, DatePicker, Form, Input, Row, Space, Select, Tooltip } from 'antd';
import { useAppDispatch, useAppSelector } from "@react/admin-order-queue/hook.ts";
import actions from '@react/admin-order-queue/redux/actions';
import dayjs, { Dayjs } from 'dayjs';
import weekday from "dayjs/plugin/weekday";
import localeData from "dayjs/plugin/localeData";
import _ from 'lodash';
import { shallowEqual } from 'react-redux';

dayjs.extend(weekday);
dayjs.extend(localeData);
dayjs.locale("en");


// internal imports
import { WarehouseOrderStatus } from '@react/admin-order-queue/constants';
import { FiltersState } from '@react/admin-order-queue/redux/reducer/interface';
import { OrderFilterCard } from './styled';

const { RangePicker } = DatePicker;

const OrderFilter = memo(() => {
    const dispatch = useAppDispatch();
    const filters = useAppSelector((state) => state.config.filters, shallowEqual);

    const [isCollapsed, setIsCollapsed] = useState(false);

    // Extract query parameters from the URL
    const params = new URLSearchParams(window.location.search);
    const urlOrderId = params.get('wq');
    const urlShipBy = params.get('shipBy');

    const [form] = Form.useForm();

    const prevFiltersRef = useRef<FiltersState | null>(null);

    useEffect(() => {
        const noOrderId = Boolean(filters.orderId);
        const noDateRange = Boolean(filters.dateRange?.[0] || filters.dateRange?.[1]);
        const noGlobalSearch = Boolean(filters.globalSearch);
        if (noOrderId || noDateRange || noGlobalSearch) {
            setIsCollapsed(true);
        }
    }, []);

    useEffect(() => {
        if (_.isEqual(prevFiltersRef.current, filters)) return;

        prevFiltersRef.current = filters;

        form.setFieldsValue({
            orderId: filters.orderId || '',
            status: Array.isArray(filters.status) ? filters.status : [],
            dateRange: [
                filters.dateRange?.[0] ? dayjs(filters.dateRange[0]) : null,
                filters.dateRange?.[1] ? dayjs(filters.dateRange[1]) : null,
            ],
            globalSearch: filters.globalSearch || '',
        });
    }, [filters, form]);

    const handleApplyFilters = (values: any) => {
        const { orderId, status, dateRange, globalSearch } = values;
        const [startDate, endDate] = dateRange || [null, null];
        dispatch(actions.config.updateFilters({
            orderId,
            status: status || [],
            dateRange: [
                startDate ? startDate.format("YYYY-MM-DD") : null,
                endDate ? endDate.format("YYYY-MM-DD") : null,
            ],
            globalSearch,
        }));
    };

    const handleResetFilters = () => {
        form.resetFields();
        dispatch(actions.config.updateFilters({ reset: true }));

        const newUrl = new URL(window.location.href);
        newUrl.searchParams.delete('wq');
        newUrl.searchParams.delete('shipBy');
        window.history.pushState({}, '', newUrl.toString());
    };

    useEffect(() => {
        const handleKeyDown = (event: KeyboardEvent) => {
            if ((event.altKey && event.key === 'e') || (event.ctrlKey && event.key === 'e')) {
                event.preventDefault();
                handleResetFilters();
            }
        };

        window.addEventListener('keydown', handleKeyDown);

        return () => {
            window.removeEventListener('keydown', handleKeyDown);
        };
    }, []);

    const statusOptions = useMemo(() => (
        Object.entries(WarehouseOrderStatus).map(([key, { label, color }]) => ({
            value: key,
            label: <span style={{ color }}>{label}</span>,
        }))
    ), []);

    const toggleButtonRef = useRef<HTMLButtonElement | null>(null);

    useEffect(() => {
        const toggleCollapse = () => {
            setIsCollapsed(!isCollapsed);
            if (isCollapsed) {
                handleResetFilters();
            }
        };

        const button: HTMLButtonElement|null = toggleButtonRef.current || document.querySelector('#toggle-filter-button');
        if (button) {
            button.textContent = !isCollapsed ? 'Show Filters' : 'Hide Filters';
            button.classList.toggle('btn-danger', isCollapsed);
            button.addEventListener('click', toggleCollapse);
        }

        return () => {
            if (button) {
                button.removeEventListener('click', toggleCollapse);
            }
        };
    }, [isCollapsed]);

    return (
        // Do Not Remove Display None
        // We’re using display: none to reduce re-renders of the component, which was causing performance issues and potential memory leaks.
        <OrderFilterCard size='small' bordered style={{ marginBottom: 4, display: isCollapsed ? 'block' : 'none' }}>
            <Form
                form={form}
                onFinish={handleApplyFilters}
                layout="vertical"
                initialValues={{
                    orderId: filters.orderId || '',
                    status: Array.isArray(filters.status) ? filters.status : [],
                    dateRange: [
                        filters.dateRange?.[0] ? dayjs(filters.dateRange[0]) : null,
                        filters.dateRange?.[1] ? dayjs(filters.dateRange[1]) : null,
                    ],
                    globalSearch: filters.globalSearch || '',
                }}
            >
                <Row gutter={[16, 16]} align="middle" wrap>
                    <Col xs={24} sm={12} md={4}>
                        <Tooltip title="eg. notes, addons, qty, sides, comments etc..." placement="bottom" destroyTooltipOnHide style={{ width: "100%" }}>
                            <Form.Item name="globalSearch" style={{ marginBottom: 0 }} tooltip="eg. notes, addons, qty, sides, comments">
                                <Input placeholder="Global Search: eg. notes, addons, qty, sides, comments" allowClear />
                            </Form.Item>
                        </Tooltip>
                    </Col>

                    <Col xs={24} sm={12} md={4}>
                        <Form.Item name="orderId" style={{ marginBottom: 0 }} tooltip="Order ID">
                            <Input placeholder="Order ID" allowClear />
                        </Form.Item>
                    </Col>

                    <Col xs={24} sm={12} md={4}>
                        <Form.Item name="status" style={{ marginBottom: 0 }}>
                            <Select
                                mode="multiple"
                                placeholder="Select Status"
                                allowClear
                                options={statusOptions}
                                style={{ width: "100%" }}
                            />
                        </Form.Item>
                    </Col>

                    <Col xs={24} sm={12} md={4}>
                        <Tooltip title="ShipBy Date" placement="bottom" destroyTooltipOnHide style={{ width: "100%" }}>
                            <Form.Item name="dateRange" style={{ marginBottom: 0 }}>
                                <RangePicker
                                    placeholder={['Start ShipBy Date', 'End ShipBy Date']}
                                    style={{ width: "100%" }}
                                />
                            </Form.Item>
                        </Tooltip>
                    </Col>

                    <Col xs={24} sm={24} md={8}>
                        <Space wrap>
                            <Button type="primary" htmlType="submit">
                                Apply
                            </Button>
                            <Tooltip title="Press Alt + E or Ctrl + E to reset filters" placement="bottom">
                                <Button onClick={handleResetFilters}>
                                    Reset
                                </Button>
                            </Tooltip>
                            <span style={{ marginLeft: 8, fontSize: 12, color: '#8c8c8c' }}>
                                (Shortcut: Press Alt + E or Ctrl + E to reset filters)
                            </span>
                        </Space>
                    </Col>
                </Row>
            </Form>
        </OrderFilterCard>
    );
});

export default OrderFilter;