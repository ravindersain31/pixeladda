import React, { useEffect, Suspense } from 'react';
import { Spin } from 'antd';

// internal imports
import { useAppDispatch, useAppSelector } from "@react/admin-order-queue/hook.ts";
import actions from "@react/admin-order-queue/redux/actions";
import { OrderQueueWrapper } from './styled';
import MercureProvider from './context/MercureProvider';
import OrderQueueBoard from './components/OrderQueueBoard';
import { shallowEqual } from 'react-redux';

const Layout = (props: any) => {
    const printer = useAppSelector((state) => state.config.printer, shallowEqual);
    const initialized = useAppSelector((state) => state.config.initialized, shallowEqual);

    const dispatch = useAppDispatch();

    useEffect(() => {
        if (!initialized) {
            dispatch(actions.config.initialize(props));
        }
    }, [initialized]);

    if (!initialized) {
        return null;
    }

    return (
        <OrderQueueWrapper>
            <MercureProvider>
                <Suspense fallback={<Spin size="large" style={{ display: 'flex', justifyContent: 'center', marginTop: '20vh' }} />}>
                    <OrderQueueBoard />
                </Suspense>
            </MercureProvider>
        </OrderQueueWrapper>
    );
};

export default Layout;
