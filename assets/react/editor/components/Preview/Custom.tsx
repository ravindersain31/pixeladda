import {useAppDispatch, useAppSelector} from "@react/editor/hook.ts";
import {CustomPreview, SwiperPreview} from "./styled";
import ViewControls from "./Canvas/Controls";
import {useEffect, useState} from "react";
import PictureViewer from "./Viewer/Picture.tsx";
import ObjectViewer from "./Viewer/Object.tsx";
import Swiper from "./Swiper";
import actions from "@react/editor/redux/actions";

const Display = ({url}: { url: string }) => {
    if (url) {
        const extension = url.toLowerCase().split('.').pop() || 'pdf';
        if (['jpg', 'jpeg', 'png', 'svg'].includes(extension)) {
            return <PictureViewer url={url}/>;
        } else if (['pdf'].includes(extension)) {
            return <ObjectViewer url={url}/>;
        }
    }
    return <></>
}

const Custom = () => {

    const canvas = useAppSelector(state => state.canvas);

    const config = useAppSelector(state => state.config);

    const [templateUrl, setTemplateUrl] = useState<string | null>();

    const dispatch = useAppDispatch();

    const handleUrlChange = (url: string) => {        
        const extension = url.toLowerCase().split(".").pop() || "pdf";
        if (["ppt", "pptx", "psd", "tiff", "tif", "heic"].includes(extension)) {
            setTemplateUrl(null);
            dispatch(actions.canvas.updateCanvasLoader(false));
        } else {
            setTemplateUrl(url);
        }
    };

    useEffect(() => {
        if (config.product.isCustom) {
            // @ts-ignore
            if (canvas.data[canvas.view] && canvas.data[canvas.view].length > 0) {
                // @ts-ignore
                handleUrlChange(canvas.data[canvas.view][canvas.activeObject]);
            }else {
                setTemplateUrl(null);
            }
        } else {
            setTemplateUrl(null);
        }
    }, [canvas.item, canvas.data, canvas.view, canvas.activeObject]);

    return (
        <>
            {templateUrl ? (
                <CustomPreview>
                    <Display url={templateUrl as string}/>
                </CustomPreview>
            ) : (
                <SwiperPreview>
                    <Swiper />
                </SwiperPreview>
            )}
            <ViewControls/>
        </>
    );

}

export default Custom;