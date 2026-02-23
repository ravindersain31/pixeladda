import axios from 'axios';
import { message } from 'antd';

export const postDataToShareCanvas = async (editorData: any, share_canvas: string, canvasItemId: any) => {
    try {
        const { data } = await axios.post(share_canvas, {
            editor: editorData,
            currentItemId: canvasItemId,
        });

        if (data.error) {
            message.error(data.error);
            return null;
        }

        if (data.redirectUrl) {
            return data;
        }

        message.info(data.message || "No redirect URL received.");
        return null;

    } catch (error: any) {
        message.error(error.message);
        return null;
    }
};
