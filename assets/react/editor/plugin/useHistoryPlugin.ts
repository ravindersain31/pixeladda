import { CanvasProperties } from "@react/editor/canvas/utils.ts";
import fabric from "@react/editor/canvas/fabric.ts";

const useHistoryPlugin = (canvas: fabric.Canvas) => {
    const history: { id: string, state: any }[] = [];
    const redoStack: { id: string, state: any }[] = [];
    let saveHistoryTimeout: NodeJS.Timeout | null = null;
    let isModifying = false;

    const generateId = () => '_' + Math.random().toString(36).substring(2, 9);

    const saveHistory = () => {
        if(canvas._objects.length === 0) return;
        if (isModifying) return;
        if (saveHistoryTimeout) clearTimeout(saveHistoryTimeout);

        saveHistoryTimeout = setTimeout(() => {
            const json = canvas.toJSON(CanvasProperties);
            const newId = generateId();
            if (history.length === 0 || JSON.stringify(history[history.length - 1].state) !== JSON.stringify(json)) {
                history.push({ id: newId, state: json });
                redoStack.length = 0;
            }
            isModifying = false;
        }, 100);
    };

    const onSelection = (event: fabric.IEvent<MouseEvent | TouchEvent>) => {
        const obj = canvas.getActiveObject();
        if (obj instanceof fabric.Textbox && obj.isEditing) {
            isModifying = false;
            return;
        }

        if ((event.e.type === "mousedown" || event.e.type === "touchstart") && event.isClick === false) {
            isModifying = true;
        }else if(event.isClick === true && (event.e.type === "touchend" || event.e.type === "mouseup")) {
            isModifying = false;
        }
    };

    const initialize = () => {
        const events = [
            "canvas:updated",
            "object:modified",
            "object:added",
            "object:removed",
            "text:changed",
        ];

        events.forEach(event => {
            canvas.on(event, saveHistory);
        });

        const onKeyDown = (e: KeyboardEvent) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z') {
                e.preventDefault();
                undo();
            }
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key.toLowerCase() === 'z') {
                e.preventDefault();
                redo();
            }
        };

        document.addEventListener("keydown", onKeyDown);

        canvas.on("mouse:down", onSelection);
        canvas.on("mouse:up", onSelection);
    };

    const restoreState = (state: any) => {
        isModifying = true;
        requestAnimationFrame(() => {
            canvas.loadFromJSON(state, () => {
                canvas.renderAll();
                const objects = canvas.getObjects();
                const lastObj = objects.at(-1);

                if (lastObj) {
                    canvas.setActiveObject(lastObj);
                }

                isModifying = false;

                if (lastObj?.type === "textbox") {
                    setTimeout(() => {
                        const active = canvas.getActiveObject();
                        if (active?.type === "textbox" && !(active as fabric.Textbox).isEditing) {
                            (active as fabric.Textbox).enterEditing();
                        }
                    }, 0);
                }
            });
        });
    };

    const undo = () => {
        if (history.length > 1) {
            const redoState = history.pop();
            if (redoState) {
                redoStack.push(redoState);
                const previousState = history[history.length - 1].state;
                restoreState(previousState);
            }
        }
    };

    const redo = () => {
        if (redoStack.length > 0) {
            const redoState = redoStack.pop();
            if (redoState) {
                history.push(redoState);
                restoreState(redoState.state);
            }
        }
    };

    const clear = () => {
        history.length = 0;
        redoStack.length = 0;
    };

    initialize();

    return { 
        undo,
        redo,
        canUndo: () => history.length > 1,
        canRedo: () => redoStack.length > 0,
        clear,
        saveHistory
    };
};

export default useHistoryPlugin;