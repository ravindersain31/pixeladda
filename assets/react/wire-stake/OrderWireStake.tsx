import React from 'react';
import {Provider} from "react-redux";
import {ConfigProvider} from 'antd';

// internal imports
import store from "@react/wire-stake/redux/store.ts";
import Layout from "@react/wire-stake/Layout/index.tsx";
import { getStoreInfo } from '@react/editor/helper/editor';

const storeInfo = getStoreInfo();
const colorPrimary = storeInfo.isPromoStore ? "#25549b" : "#6f4c9e";

const OrderWireStake = (props: any) => {
    return <Provider store={store}>
        <ConfigProvider
            theme={{
                token: {
                    borderRadius: 3,
                    colorPrimary: colorPrimary,
                    fontFamily: 'Montserrat, sans-serif',
                    fontWeightStrong: 500,
                    colorBorderSecondary: '#d9d9d9'
                },
            }}
        >
            <Layout {...props}/>
        </ConfigProvider>
    </Provider>;
}

export default OrderWireStake;