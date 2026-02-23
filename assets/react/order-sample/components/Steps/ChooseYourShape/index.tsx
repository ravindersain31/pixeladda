import React, { memo, useEffect, useState } from "react";
import { StepProps } from "@orderSample/utils/interface";
import StepCard from "@orderSample/components/Cards/StepCard";
import { useAppDispatch, useAppSelector } from "@orderSample/hook";
import { shallowEqual } from "react-redux";
import { Col, Radio } from "antd";
import RadioButton from "@orderSample/components/Radio/RadioButton";
import AddonCard from "@orderSample/components/Cards/AddonCard";
import { isMobile } from "react-device-detect";
import { IAddOnPrices, IShape } from "@orderSample/redux/reducer/cart/interface";
import actions from "@orderSample/redux/actions";
import { isPromoStore } from "@react/editor/helper/editor";

const ChooseYourShape = ({ stepNumber = 3 }: StepProps) => {

    const ribbonMeta: Record<string, { texts: string[]; colors: string[] }> = {
        [IShape.SQUARE]: {
            texts: ["FREE"],
            colors: ["#1B8A1B"],
        },
        [IShape.CUSTOM]: {
            texts: [`+$${IAddOnPrices.SHAPE[IShape.CUSTOM].toFixed(2)}`],
            colors: ["#1d4e9b"],
        },
    };

    const { cartStage, totalQuantity } = useAppSelector((state) => ({
        cartStage: state.cartStage,
        selectedShape: state.cartStage.shape,
        totalQuantity: state.cartStage.totalQuantity
    }), shallowEqual);

    const [shape, setShape] = useState<string>(cartStage.shape || IShape.SQUARE);

    const dispatch = useAppDispatch();

    useEffect(() => {
        dispatch(actions.cartStage.updateShape(shape));
    }, [totalQuantity]);

    const onShapeChange = (shapeName: string) => {
        setShape(shapeName);
        dispatch(actions.cartStage.updateShape(shapeName));
    };

    return (
        <>
            <StepCard title="Choose Your Shape" stepNumber={stepNumber}>
                <Radio.Group
                    className="ant-row"
                    value={shape}
                    onChange={(e) => onShapeChange(e.target.value)}
                >
                    <Col xs={12} sm={12} md={6} lg={5}>
                        <RadioButton value={IShape.SQUARE}>
                            <AddonCard
                                title="Square / Rectangle"
                                imageUrl={isPromoStore() ? "https://static.yardsignplus.com/storage/promo-store/Imprint-Color-Icon.svg" : "https://static.yardsignplus.com/assets/Square.png"}
                                ribbonText={ribbonMeta[IShape.SQUARE].texts}
                                ribbonColor={ribbonMeta[IShape.SQUARE].colors}
                                helpText={
                                    <p className="text-start mb-0">
                                        <b>Square / Rectangle Shape:</b><br />
                                        Square or Rectangle Shape allows<br />
                                        printing and cutting along any<br />
                                        defined square or rectangular<br />
                                        border. This is the most common<br />
                                        and popular choice for standard<br />
                                        yard signs, including default sizes.
                                    </p>
                                }
                            />
                        </RadioButton>
                    </Col>
                    <Col xs={12} sm={12} md={6} lg={5}>
                        <RadioButton value={IShape.CUSTOM}>
                            <AddonCard
                                title="Custom"
                                imageUrl={isPromoStore() ? "https://static.yardsignplus.com/storage/promo-store/Custom-Promo-Icon.svg" : "https://static.yardsignplus.com/assets/Custom.png"}
                                ribbonText={ribbonMeta[IShape.CUSTOM].texts}
                                ribbonColor={ribbonMeta[IShape.CUSTOM].colors}
                                placement="left"
                                helpText={
                                    <p className="text-start mb-0">
                                        <b>Custom Shape:</b><br />
                                        Custom Shape allows printing and cutting along<br />
                                        any irregular border or die cut. This includes<br />
                                        any undefined outlining for fully custom signs.<br />
                                        We will cut along the outer edges of your custom<br />
                                        shape. Please leave a comment if necessary on<br />
                                        your final cut requirements.
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

export default memo(ChooseYourShape);