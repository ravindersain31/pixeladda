import {useAppDispatch} from "@react/editor/hook.ts";
import {isMobile} from "react-device-detect";
import actions from "@react/editor/redux/actions";
import {useEffect} from "react";

const PDFViewer = ({url}: { url: string }) => {
    const dispatch = useAppDispatch();

    const onLoaded = () => {
        dispatch(actions.canvas.updateCanvasLoader(false));
    }

    useEffect(() => {
        if (isMobile) {
            dispatch(actions.canvas.updateCanvasLoader(false));
        }
    }, []);

    return <object
        data={`${url}?#toolbar=0&amp;navpanes=0`}
        type="application/pdf"
        onLoad={onLoaded}
    >
        <p className="text-center mb-2">
            <a href={url} target="_blank" download="">Download Custom Design</a>
        </p>
    </object>
}

export default PDFViewer;