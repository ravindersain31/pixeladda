import React from "react";
import { Provider } from "react-redux";
import { ConfigProvider } from "antd";
import Layout from "./Layout";
import store from "./redux/store";

const OrderQueue = (props: any) => {
    return <Provider store={store}>
        <ConfigProvider
            theme={{
                token: {
                    borderRadius: 3,
                    colorPrimary: "#0061f2",
                    fontFamily: 'Montserrat, sans-serif',
                    fontWeightStrong: 500,
                    // colorBorderSecondary: '#d9d9d9'
                },
            }}
        >
            <Layout {...props}/>
        </ConfigProvider>
    </Provider>;
};

export default OrderQueue;
