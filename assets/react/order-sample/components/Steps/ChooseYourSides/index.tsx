import React, { memo, useEffect } from "react";
import { StepProps } from "@orderSample/utils/interface";
import StepCard from "@orderSample/components/Cards/StepCard";
import { Col, Radio } from "antd";
import { IAddOnPrices, ISides } from "@orderSample/redux/reducer/cart/interface";
import RadioButton from "@orderSample/components/Radio/RadioButton";
import AddonCard from "@orderSample/components/Cards/AddonCard";
import { isMobile } from "react-device-detect";
import { useAppDispatch, useAppSelector } from "@orderSample/hook";
import actions from "@orderSample/redux/actions";
import { shallowEqual } from "react-redux";
import { isPromoStore } from "@react/editor/helper/editor";

const ribbonMeta: Record<string, { texts: string[]; colors: string[] }> = {
    [ISides.SINGLE]: {
        texts: ["FREE"],
        colors: ["#1B8A1B"],
    },
    [ISides.DOUBLE]: {
        texts: [`+$${IAddOnPrices.SIDES[ISides.DOUBLE].toFixed(2)}`],
        colors: ["#1d4e9b"],
    },
};

const ChooseYourSides = ({ stepNumber = 2 }: StepProps) => {
    const cartStage = useAppSelector(state => state.cartStage, shallowEqual);

    const [sides, setSides] = React.useState<ISides>(cartStage.sides || ISides.SINGLE);
    const dispatch = useAppDispatch();

    useEffect(() => {
        dispatch(actions.cartStage.updateSides(sides));
    }, [cartStage.totalQuantity]);

    const handleOnSidesChange = (value: ISides) => {
        setSides(value);
        dispatch(actions.cartStage.updateSides(value));
    };

    return (
        <>
            <StepCard title="Choose Your Sides" stepNumber={stepNumber}>
                <Radio.Group
                    className="ant-row"
                    value={sides}
                    onChange={(e) => handleOnSidesChange(e.target.value)}
                >
                    <Col xs={12} sm={12} md={6} lg={5}>
                        <RadioButton value={ISides.SINGLE}>
                            <AddonCard
                                title="Single Sided"
                                imageUrl={isPromoStore() ? "https://static.yardsignplus.com/storage/promo-store/Step-3-01.svg" : "https://static.yardsignplus.com/assets/side-option-front.png"}
                                ribbonText={ribbonMeta[ISides.SINGLE].texts}
                                ribbonColor={ribbonMeta[ISides.SINGLE].colors}
                                helpText={
                                    <p className="text-start mb-0">
                                        <b>Single Sided:</b> Single Sided<br />
                                        printing offers a smooth, embedded,<br />
                                        and vibrant finish on one side <br />
                                        of the sign. Your text, numbers<br />
                                        and / or artwork will be ingrained<br />
                                        into the material. We use 4mm thick<br />
                                        corrugated plastic and ultraviolet (UV)<br />
                                        light to print your customizations onto<br />
                                        the yard sign.
                                    </p>
                                }
                            />
                        </RadioButton>
                    </Col>
                    <Col xs={12} sm={12} md={6} lg={5}>
                        <RadioButton value={ISides.DOUBLE}>
                            <AddonCard
                                title="Double Sided"
                                imageUrl={isPromoStore() ? "https://static.yardsignplus.com/storage/promo-store/Step-3-02.svg" : "https://static.yardsignplus.com/assets/side-option-front-back.png"}
                                ribbonText={ribbonMeta[ISides.DOUBLE].texts}
                                ribbonColor={ribbonMeta[ISides.DOUBLE].colors}
                                placement={isMobile ? "left" : undefined}
                                helpText={
                                    <p className="text-start mb-0">
                                        <b>Double Sided:</b> Double Sided<br />
                                        printing offers a smooth, embedded,<br />
                                        and vibrant finish on both sides<br />
                                        of the sign (front and back). Your<br />
                                        text, numbers, and / or artwork<br />
                                        will be ingrained into the material.<br />
                                        We use 4mm thick corrugated plastic<br />
                                        and ultraviolet (UV) light to print<br />
                                        your customizations onto the yard sign.<br />
                                    </p>
                                }
                            />
                        </RadioButton>
                    </Col>
                </Radio.Group>
            </StepCard>
        </>
    );
};

export default memo(ChooseYourSides);