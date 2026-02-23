import React, { useEffect, useState } from "react";
import { AdditionalNote } from "./styled.tsx";
import { useAppDispatch, useAppSelector } from "@orderSample/hook";
import actions from "@orderSample/redux/actions";
import { shallowEqual } from "react-redux";

const AdditionalComments = () => {
    const cartStage = useAppSelector((state) => state.cartStage, shallowEqual);
    const [note, setNote] = useState<string>(cartStage.additionalNote);
    const dispatch = useAppDispatch();

    useEffect(() => {
        setNote(cartStage.additionalNote);
    }, []);


    const onNoteChange = (note: string) => {
        dispatch(actions.cartStage.updateComment(note));
        setNote(note);
    }

    return <>
        <AdditionalNote
            value={note}
            onChange={(event: any) => onNoteChange(event.target.value)}
            rows={3}
            placeholder="Add additional instructions. Your comments will be seen when we receive your order."
        />
    </>
}

export default AdditionalComments;