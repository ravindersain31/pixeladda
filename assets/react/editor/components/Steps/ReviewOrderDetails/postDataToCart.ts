import axios from 'axios';
import {message} from 'antd';

export const postDataToCart = async (editorData: any, add_to_cart: any) => {
    try {
        const {data} = await axios.post(add_to_cart, {
            editor: editorData,
        });

        if (data.action) {
            switch (data.action) {
                case 'error':
                    message.open({
                        type: 'error',
                        content: data.message,
                    });
                    if (data.moveTo) {
                        document.getElementById(data.moveTo)?.scrollIntoView();
                    }
                    break;
                case 'redirect':
                    // @ts-ignore
                    fbq('track', 'AddToCart');
                    if (editorData.additionalData?.saveDesignEmail) {
                        message.open({
                            type: 'success',
                            content: data.message,
                        });
                    } else if (editorData.additionalData?.orderQuoteEmail) {
                        message.open({
                            type: 'success',
                            content: data.message,
                        });
                    }
                    else if (editorData.additionalData?.downloadQuote) {
                        message.open({
                            type: 'success',
                            content: data.message,
                        });
                    } else {
                        message.open({
                            type: 'success',
                            content: data.message,
                        });
                        window.location.href = data.redirectUrl;
                    }
                    break;
                default:
                    message.open({
                        type: 'info',
                        content: data.message,
                    });
                    break;
            }
        }
    } catch (error: any) {
        message.open({
            type: 'error',
            content: error.message,
        });
    }
};
