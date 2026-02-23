import React from 'react';
import { Provider } from "react-redux";
import { ConfigProvider } from 'antd';
import Layout from '@react/editor/Layout.tsx';
import store from "./redux/store.ts";
import { isPromoStore } from './helper/editor.ts';

const ProductEditor = (props: any) => {
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
            <Layout {...props} />
        </ConfigProvider>
    </Provider>;
}

export default ProductEditor;