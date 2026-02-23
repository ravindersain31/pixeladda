import { Radio, Col } from "antd";
import { useAppDispatch, useAppSelector } from "@react/editor/hook.ts";
import StepCard from '@react/editor/components/Cards/StepCard';
import RadioButton from "@react/editor/components/Radio/RadioButton";
import AddonCard from "@react/editor/components/Cards/AddonCard";
import { Grommets } from "@react/editor/redux/interface.ts";
import actions from "@react/editor/redux/actions";
import { StepProps } from "../interface.ts";
import { AddOnPrices, GrommetColor } from "@react/editor/redux/reducer/editor/interface.ts";
import { useEffect, useMemo, useRef, useState } from "react";
import { grommetImages } from "@react/editor/helper/addonGrommetsImage.ts";
import { AdditionalNote } from "../../AdditionalNote/styled.tsx";
import { isMobile } from "react-device-detect";

const ChooseYourGrommets = ({ stepNumber }: StepProps) => {
    const config = useAppSelector(state => state.config);
    const canvas = useAppSelector(state => state.canvas);
    const grommets = useAppSelector(state => state.editor.grommets);
    const currentItem = useAppSelector(state => state.editor.items[canvas.item.id]);
    const searchParams = new URLSearchParams(window.location.search);
    const urlGrommets = searchParams.get('grommets');
    const dispatch = useAppDispatch();
    const addonImages = useMemo(() => grommetImages(currentItem), [currentItem]);

    const getNoteValue = () => {
        if (!currentItem) return '';
        const grommetNotes = currentItem.notes?.grommets;
        if (!grommetNotes) return '';
        if (typeof grommetNotes === 'string') return '';
        return grommetNotes[grommets] || '';
    };

    const [noteValue, setNoteValue] = useState(getNoteValue());
    const debounceTimerRef = useRef<NodeJS.Timeout | null>(null);

    useEffect(() => {
        if (urlGrommets) {
            dispatch(actions.editor.updateGrommets(urlGrommets));
        }
    }, []);

    useEffect(() => {
        if (grommets == Grommets.NONE) {
            dispatch(actions.editor.updateGrommetColor(GrommetColor.SILVER));
        }
        dispatch(actions.editor.updatePrePackedDiscount());
    }, [grommets])

    useEffect(() => {
        if (!currentItem) return;
        if (grommets === Grommets.CUSTOM_PLACEMENT) {
            setNoteValue(getNoteValue());
        } else {
            setNoteValue('');
            dispatch(actions.editor.updateNotes({
                type: "grommets",
                subType: Grommets.CUSTOM_PLACEMENT,
                value: '',
                itemId: currentItem.id,
            }));
        }
    }, [grommets, currentItem?.notes?.grommets]);

    useEffect(() => {
        return () => {
            if (debounceTimerRef.current) {
                clearTimeout(debounceTimerRef.current);
            }
        };
    }, []);

    const onNoteChange = (e: any) => {
        if (!currentItem) return;

        const value = e.target.value;
        setNoteValue(value);

        if (debounceTimerRef.current) {
            clearTimeout(debounceTimerRef.current);
        }

        debounceTimerRef.current = setTimeout(() => {
            dispatch(actions.editor.updateNotes({
                type: "grommets",
                subType: grommets,
                value: value,
                itemId: currentItem.id,
            }));
        }, 500);
    };

    return <StepCard title="Choose Your Grommets (3/8 Inch Hole)" stepNumber={stepNumber} scrollable={!isMobile} scrollHeight={250}>
        <Radio.Group
            className="ant-row"
            value={grommets}
            onChange={(e) => dispatch(actions.editor.updateGrommets(e.target.value))}
        >
            <Col xs={12} sm={12} md={8} lg={6}>
                <RadioButton value={Grommets.NONE}>
                    <AddonCard
                        title="None"
                        imageUrl={addonImages[Grommets.NONE](config.product)}
                        ribbonText={AddOnPrices.GROMMETS[Grommets.NONE] === 0 ? 'FREE' : AddOnPrices.GROMMETS[Grommets.NONE]}
                        ribbonColor={'#1B8A1B'}
                    />
                </RadioButton>
            </Col>
            <Col xs={12} sm={12} md={8} lg={6}>
                <RadioButton value={Grommets.TOP_CENTER}>
                    <AddonCard
                        title="Top Center"
                        imageUrl={addonImages[Grommets.TOP_CENTER](config.product)}
                        ribbonText={"+" + AddOnPrices.GROMMETS[Grommets.TOP_CENTER] + "%"}
                        ribbonColor={'#1d4e9b'}
                    />
                </RadioButton>
            </Col>
            <Col xs={12} sm={12} md={8} lg={6}>
                <RadioButton value={Grommets.TOP_CORNERS}>
                    <AddonCard
                        title="Top Corners"
                        imageUrl={addonImages[Grommets.TOP_CORNERS](config.product)}
                        ribbonText={"+" + AddOnPrices.GROMMETS[Grommets.TOP_CORNERS] + "%"}
                        ribbonColor={'#1d4e9b'}
                    />
                </RadioButton>
            </Col>
            <Col xs={12} sm={12} md={8} lg={6}>
                <RadioButton value={Grommets.FOUR_CORNERS}>
                    <AddonCard
                        title="Four Corners"
                        imageUrl={addonImages[Grommets.FOUR_CORNERS](config.product)}
                        ribbonText={"+" + AddOnPrices.GROMMETS[Grommets.FOUR_CORNERS] + "%"}
                        ribbonColor={'#1d4e9b'}
                    />
                </RadioButton>
            </Col>
            <Col xs={12} sm={12} md={8} lg={6}>
                <RadioButton value={Grommets.SIX_CORNERS}>
                    <AddonCard
                        title="Six Corners"
                        imageUrl={addonImages[Grommets.SIX_CORNERS](config.product)}
                        ribbonText={"+" + AddOnPrices.GROMMETS[Grommets.SIX_CORNERS] + "%"}
                        ribbonColor={'#1d4e9b'}
                    />
                </RadioButton>
            </Col>
            <Col xs={12} sm={12} md={8} lg={6}>
                <RadioButton value={Grommets.CUSTOM_PLACEMENT}>
                    <AddonCard
                        title="Custom Placement"
                        imageUrl={addonImages[Grommets.CUSTOM_PLACEMENT](config.product)}
                        ribbonText={"+" + AddOnPrices.GROMMETS[Grommets.CUSTOM_PLACEMENT] + "%"}
                        ribbonColor={'#1d4e9b'}
                        placement={isMobile ? 'bottomLeft' : 'left'}
                        helpText={
                            <p className="text-start mb-0">
                                <b>Custom Placement</b><br />
                                Custom Placement allows you to choose the number of grommets and position for each. Please leave a comment with the total number of grommets and positions for each required. We will apply this to each sign.
                            </p>
                        }
                    />
                </RadioButton>
            </Col>
        </Radio.Group>

        {grommets === Grommets.CUSTOM_PLACEMENT && (
            <div className="m-1">
                <AdditionalNote
                    value={noteValue}
                    onChange={onNoteChange}
                    rows={3}
                    placeholder="Custom Placement allows you to choose the number of grommets and position for each. Please leave a comment with the total number of grommets and positions for each required. We will apply this to each sign.
"
                />
            </div>
        )}
    </StepCard>
}

export default ChooseYourGrommets;