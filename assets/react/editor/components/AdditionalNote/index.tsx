import React, { useEffect, useMemo, useState, useCallback } from "react";
import { NoteMessage, AdditionalNote } from "./styled.tsx";
import { useAppDispatch, useAppSelector } from "@react/editor/hook.ts";
import actions from "@react/editor/redux/actions";
import NeedAssistance from "@react/editor/components/NeedAssistance";
import { NeedAssistanceContainer } from "../Steps/ChooseYourDesign/CustomDesign/styled.tsx";
import { isMobile } from "react-device-detect";
import YSPLogo from "../common/YSPLogo/index.tsx";
import useShowCanvas from "@react/editor/hooks/useShowCanvas.tsx";
import { debounce } from "lodash";

interface Props {
    showNoteMessage?: boolean;
    showAdditionalNote?: boolean;
    showNeedAssistance?: boolean;
    showNote?: boolean;
}

const AdditionalComments = ({ showNoteMessage = true, showAdditionalNote = true, showNeedAssistance = true, showNote = false }: Props) => {
    const editor = useAppSelector(state => state.editor);
    const config = useAppSelector(state => state.config);
    const canvas = useAppSelector(state => state.canvas);
    const showCanvas = useShowCanvas();

    const dispatch = useAppDispatch();

    const noteInRedux = useMemo(() => editor.items[canvas.item.id]?.additionalNote ?? '', [editor.items, canvas.item.id]);
    const [localNote, setLocalNote] = useState(noteInRedux);

    useEffect(() => {
        setLocalNote(noteInRedux);
    }, [noteInRedux]);

    const debouncedUpdateNote = useCallback(
        debounce((note: string) => {
            dispatch(actions.editor.updateAdditionalNote(note));
        }, 500),
        [dispatch]
    );

    const onNoteChange = (note: string) => {
        setLocalNote(note);
        debouncedUpdateNote(note);
    }

    useEffect(() => {
        if(canvas.item.itemId && !editor.items[canvas.item.id]?.additionalNote){
             dispatch(actions.editor.updateAdditionalNote(editor.items[canvas.item.id]?.additionalNote ?? ''));
        }
    }, []);

    return <>
        {showAdditionalNote && <AdditionalNote
            value={localNote}
            onChange={(event: any) => onNoteChange(event.target.value)}
            rows={3}
            placeholder="Add additional instructions. Your comments will be seen when we receive your order."
        />}
        {showNeedAssistance &&
            (
                <>
                    <NeedAssistanceContainer>
                        {!showCanvas && <YSPLogo/>}
                        <NeedAssistance />
                    </NeedAssistanceContainer>
                </>
            )
        }
        {showNoteMessage && <NoteMessage>
            We will email you a digital proof in 1 hour. We can create any design! For
            repeat orders, mention your old order number. Accepted files are PNG, JPEG, JPG, EXCEL, CSV, Ai, PDF & ZIP. Files must be
            less than 50 MB in size.{' '}
            {showNote && isMobile && <span>Order online or call now  <a href="tel: +1-877-958-1499">+1-877-958-1499</a></span>}
        </NoteMessage>}
    </>
}

export default AdditionalComments;