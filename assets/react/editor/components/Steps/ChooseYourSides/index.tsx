import { Radio, Col } from "antd";
import { useAppDispatch, useAppSelector } from "@react/editor/hook.ts";
import StepCard from '@react/editor/components/Cards/StepCard';
import AddonCard from "@react/editor/components/Cards/AddonCard";
import RadioButton from "@react/editor/components/Radio/RadioButton";
import { Sides } from "@react/editor/redux/interface.ts";
import actions from "@react/editor/redux/actions";
import { StepProps } from "../interface.ts";
import useCanvas from "@react/editor/hooks/useCanvas.tsx";
import { useEffect, useMemo } from "react";
import { isMobile } from "react-device-detect";
import { AddOnPrices } from "@react/editor/redux/reducer/editor/interface.ts";
import { isPromoStore } from "@react/editor/helper/editor.ts";
import { sideImages } from "@react/editor/helper/addonSidesImages.ts";


const ChooseYourSides = ({ stepNumber }: StepProps) => {
    const config = useAppSelector(state => state.config);
    const canvas = useAppSelector(state => state.canvas);
    const sides = useAppSelector(state => state.editor.sides);
    const product = useAppSelector(state => state.config.product);
    const currentItem = useAppSelector(state => state.editor.items[canvas.item.id]);
    const searchParams = new URLSearchParams(window.location.search);
    const urlSides = searchParams.get('sides');
    const dispatch = useAppDispatch();
    const addonImages = useMemo(() => sideImages(currentItem), [currentItem]);


    useEffect(() => {
        if (urlSides) {
            dispatch(actions.editor.updateSides(urlSides));
        }
    }, []);

    return <StepCard title="Choose Your Sides" stepNumber={stepNumber}>
        <Radio.Group
            className="ant-row"
            value={sides}
            onChange={(e) => {
                dispatch(actions.editor.updateSides(e.target.value));
                dispatch(actions.editor.updatePrePackedDiscount());
            }}
        >
            <Col xs={12} sm={12} md={8} lg={6}>
                <RadioButton value={Sides.SINGLE}>
                    <AddonCard
                        title="Single Sided"
                        imageUrl={addonImages[Sides.SINGLE](config.product)}
                        ribbonText={AddOnPrices.SIDES[Sides.SINGLE] === 0 ? "FREE" : AddOnPrices.SIDES[Sides.SINGLE]}
                        ribbonColor={'#1B8A1B'}
                        helpText={
                            <p className="text-start mb-0">
                                <b>Single Sided:</b><br /> Single Sided
                                printing offers a smooth, embedded,
                                and vibrant finish on one side
                                of the sign. Your text, numbers
                                and / or artwork will be ingrained
                                into the material. We use 4mm thick
                                corrugated plastic and ultraviolet (UV)
                                light to print your customizations onto
                                the yard sign.
                            </p>
                        }
                    />
                </RadioButton>
            </Col>
            <Col xs={12} sm={12} md={8} lg={6}>
                <RadioButton value={Sides.DOUBLE}>
                    <AddonCard
                        title="Double Sided"
                        imageUrl={addonImages[Sides.DOUBLE](config.product)}
                        ribbonText={"+" + AddOnPrices.SIDES[Sides.DOUBLE] + "%"}
                        ribbonColor={'#1d4e9b'}
                        placement={isMobile ? "left" : undefined}
                        helpText={
                            <p className="text-start mb-0">
                                <b>Double Sided:</b><br /> Double Sided
                                printing offers a smooth, embedded,
                                and vibrant finish on both sides
                                of the sign (front and back). Your
                                text, numbers, and / or artwork
                                will be ingrained into the material.
                                We use 4mm thick corrugated plastic
                                and ultraviolet (UV) light to print
                                your customizations onto the yard sign.
                            </p>
                        }
                    />
                </RadioButton>
            </Col>
        </Radio.Group>
    </StepCard>
}

export default ChooseYourSides;