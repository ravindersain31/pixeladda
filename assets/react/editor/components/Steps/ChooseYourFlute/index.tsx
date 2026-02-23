import { Radio, Col, Button } from "antd";
import { useAppDispatch, useAppSelector } from "@react/editor/hook.ts";
import StepCard from '@react/editor/components/Cards/StepCard';
import RadioButton from "@react/editor/components/Radio/RadioButton";
import AddonCard from "@react/editor/components/Cards/AddonCard";
import { Flute } from "@react/editor/redux/interface.ts";
import { StepProps } from "../interface.ts";
import actions from "@react/editor/redux/actions";
import { useEffect, useState } from "react";
import { AddOnPrices, Shape } from "@react/editor/redux/reducer/editor/interface.ts";
import { isMobile } from "react-device-detect";
import { getFluteOptions } from "@react/editor/helper/flute.tsx";

const ChooseYourFlute = ({ stepNumber }: StepProps) => {
    const flute = useAppSelector(state => state.editor.flute);
    const editor = useAppSelector(state => state.editor);
    const config = useAppSelector(state => state.config);
    const canvas = useAppSelector(state => state.canvas);
    const currentItem = editor.items[canvas.item.id];
    const [flutePrices, setFlutePrices] = useState<{ [key: string]: number }>(AddOnPrices.Flute);
    const searchParams = new URLSearchParams(window.location.search);
    const urlFlute = searchParams.get('flute');
    
    const dispatch = useAppDispatch();

    const [fluteName, setFluteName] = useState<string | string[]>(urlFlute ?? editor.flute);
    const fluteOptions = getFluteOptions(config.product, currentItem, flutePrices);

    const onFluteChange = (name: string) => {
        dispatch(actions.editor.updateFlute(name));
        setFluteName(name);
    }

    useEffect(() => {
        if (!config.product.isYardLetters) {
            dispatch(actions.editor.updateFlute(fluteName));
        }
    }, [editor.totalQuantity, editor.flute, canvas.customSize.templateSize, editor.shape]);

    const mobilePlacementMap: any = {
        [Flute.VERTICAL]: 'top',
        [Flute.HORIZONTAL]: 'right',
    };

    const desktopPlacementMap: Record<string, string> = {
        [Flute.VERTICAL]: 'right',
    };

    return <StepCard id="choose-your-flute" title="Choose Flutes Direction" stepNumber={stepNumber}>
        <Radio.Group
            className="ant-row"
            value={flute}
            onChange={(e) => onFluteChange(e.target.value)}
        >
            {fluteOptions.map((option:any) => (
                <Col xs={12} sm={12} md={8} lg={6} key={option.key}>
                    <RadioButton value={option.key}>
                        <AddonCard
                            title={option.title}
                            imageUrl={option.image}
                            ribbonText={option.ribbonText}
                            ribbonColor={option.ribbonColor}
                            placement={
                                isMobile
                                    ? mobilePlacementMap[option.key] ?? 'left'
                                    : desktopPlacementMap[option.key]
                            }
                            helpText={option.helpText}
                        />
                    </RadioButton>
                </Col>
            ))}
        </Radio.Group>
    </StepCard>
}

export default ChooseYourFlute;