import ConfigState from "@react/editor/redux/reducer/config/interface.ts";
import EditorState from "@react/editor/redux/reducer/editor/interface.ts";
import CanvasState from "@react/editor/redux/reducer/canvas/interface.ts";
import StorageState from "@react/editor/redux/reducer/storage/interface.ts";

export default interface AppState {
    config: ConfigState;
    editor: EditorState;
    canvas: CanvasState;
    storage: StorageState;
}