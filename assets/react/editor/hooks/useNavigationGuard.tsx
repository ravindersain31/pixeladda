import { useState, useEffect, useContext, useMemo, useRef } from "react";
import { useAppSelector } from "../hook";
import CanvasContext from "@react/editor/context/canvas.ts";
import fabric from "@react/editor/canvas/fabric.ts";

const useNavigationGuard = (isEnabled: boolean = true) => {
    const [isModalVisible, setIsModalVisible] = useState(false);
    const [nextPath, setNextPath] = useState<string | null>(null);
    const [hasUserInteracted, setHasUserInteracted] = useState(false);
    const state = useAppSelector(state => state);
    const canvasContext = useContext(CanvasContext);
    const isMounted = useRef(false);

    const totalAmount = useMemo(() => state.editor.totalAmount, []);

    useEffect(() => {
        setTimeout(() => {
            isMounted.current = true;
            return () => {
                isMounted.current = false;
            };
        }, 2000)
    }, []);

    useEffect(() => {
        if (canvasContext.canvas instanceof fabric.Canvas) {
            canvasContext.canvas.on('object:modified', onObjectModified);
            canvasContext.canvas.on("text:changed", onObjectModified);
            canvasContext.canvas.on('text:editing:exited', onObjectModified);
            canvasContext.canvas.on("mouse:dblclick", onObjectModified);
        }

        return () => {
            if (canvasContext.canvas instanceof fabric.Canvas) {
                canvasContext.canvas.off('object:modified', onObjectModified);
                canvasContext.canvas.off("text:changed", onObjectModified);
                canvasContext.canvas.off('mouse:dblclick', onObjectModified);
            }
        };
    }, [canvasContext.canvas]);

    useEffect(() => {
        const userInteractionHandler = () => {
            setHasUserInteracted(true);
        };

        // window.addEventListener("click", userInteractionHandler);
        // window.addEventListener("keydown", userInteractionHandler);

        return () => {
            // window.removeEventListener("click", userInteractionHandler);
            // window.removeEventListener("keydown", userInteractionHandler);
        };
    }, []);

    useEffect(() => {
        if (!isEnabled) return;

        const handleBeforeUnload = (event: BeforeUnloadEvent) => {
            if (hasUserInteracted) {
                event.preventDefault();
                event.returnValue = "";
            }
        };

        const handlePopState = (event: PopStateEvent) => {
            if (hasUserInteracted) {
                event.preventDefault();
                setIsModalVisible(true);
                setNextPath(window.location.pathname);
            }
        };

        window.addEventListener("beforeunload", handleBeforeUnload);
        window.addEventListener("popstate", handlePopState);

        return () => {
            window.removeEventListener("beforeunload", handleBeforeUnload);
            window.removeEventListener("popstate", handlePopState);
        };
    }, [hasUserInteracted, isEnabled]);

    useEffect(() => {
        if (totalAmount !== state.editor.totalAmount && isMounted.current) {
            setHasUserInteracted(true);
        }
    }, [state.editor.totalAmount]);

    const confirmLeave = () => {
        setIsModalVisible(false);
        if (nextPath) {
            window.location.href = nextPath;
        }
    };

    const onObjectModified = (event: fabric.IEvent) => {
        setHasUserInteracted(true);
    }

    return {
        isModalVisible,
        confirmLeave,
        cancelLeave: () => setIsModalVisible(false),
    };
};

export default useNavigationGuard;
