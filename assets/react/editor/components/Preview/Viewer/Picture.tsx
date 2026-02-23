import actions from "@react/editor/redux/actions";
import {useAppDispatch} from "@react/editor/hook.ts";

const ImageViewer = ({url}: { url: string }) => {
    const dispatch = useAppDispatch();
    const onLoaded = () => {
        dispatch(actions.canvas.updateCanvasLoader(false));
    }
    return <picture>
        <img src={url} style={{width: '100%', height: 'auto'}} alt="viewer" onLoad={onLoaded}/>
    </picture>
}

export default ImageViewer;