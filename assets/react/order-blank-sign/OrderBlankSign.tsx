import React from 'react';
import {Provider} from "react-redux";
import {ConfigProvider} from 'antd';

// internal imports
import store from "@react/order-blank-sign/redux/store.ts";
import Layout from "@react/order-blank-sign/Layout/index.tsx";
import { isPromoStore } from '@react/editor/helper/editor';


const OrderBlankSign = (props: any) => {
    return <Provider store={store}>
        <ConfigProvider
            theme={{
                token: {
                    borderRadius: 3,
                    colorPrimary: isPromoStore() ? "#25549b" : "#6f4c9e",
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

export default OrderBlankSign;