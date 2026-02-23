import { Radio, Col } from "antd";
import { useAppDispatch, useAppSelector } from "@react/editor/hook.ts";
import StepCard from '@react/editor/components/Cards/StepCard';
import AddonCard from "@react/editor/components/Cards/AddonCard";
import RadioButton from "@react/editor/components/Radio/RadioButton";
import { ImprintColor } from "@react/editor/redux/interface.ts";
import actions from "@react/editor/redux/actions";
import { StepProps } from "../interface.ts";
import { isMobile } from "react-device-detect";
import { AddOnPrices } from "@react/editor/redux/reducer/editor/interface.ts";
import { useEffect, useMemo } from "react";
import { isPromoStore } from "@react/editor/helper/editor.ts";
import { imprintImages } from "@react/editor/helper/addonImprintImages.ts";


const ChooseImprintColor = ({ stepNumber }: StepProps) => {
    const config = useAppSelector(state => state.config);
    const canvas = useAppSelector(state => state.canvas);
    const imprintColor = useAppSelector(state => state.editor.imprintColor);
    const currentItem = useAppSelector(state => state.editor.items[canvas.item.id]);
    const searchParams = new URLSearchParams(window.location.search);
    const urlImprintColor = searchParams.get('imprintColor');
    const dispatch = useAppDispatch();

    const addonImages = useMemo(() => imprintImages(currentItem), [currentItem]);

    useEffect(() => {
        if (urlImprintColor) {
            dispatch(actions.editor.updateImprintColor(urlImprintColor));
        }
    }, []);

    return <StepCard title="Choose Imprint Color" stepNumber={stepNumber}>
        <Radio.Group
            className="step-radio-group ant-row"
            value={imprintColor}
            onChange={(e) => {
                dispatch(actions.editor.updateImprintColor(e.target.value));
                dispatch(actions.editor.updatePrePackedDiscount());
            }}
        >
            <Col xs={12} sm={12} md={6}>
                <RadioButton value={ImprintColor.ONE}>
                    <AddonCard
                        title="1 Imprint Color"
                        imageUrl={addonImages[ImprintColor.ONE](config.product)}
                        ribbonText={AddOnPrices.IMPRINT_COLOR[ImprintColor.ONE] === 0 ? "FREE" : AddOnPrices.IMPRINT_COLOR[ImprintColor.ONE]}
                        ribbonColor={'#1B8A1B'}
                        helpText={
                            <p className="text-start mb-0">
                                <b>1 Imprint Color: </b><br /> 1 Imprint Color offers you to choose
                                one color for your text, numbers, and / or artwork. This
                                choice is best for customizations requiring only one color.
                                White is included (free).
                            </p>
                        }
                    />
                </RadioButton>
            </Col>
            <Col xs={12} sm={12} md={6}>
                <RadioButton value={ImprintColor.TWO}>
                    <AddonCard
                        title="2 Imprint Colors"
                        imageUrl={addonImages[ImprintColor.TWO](config.product)}
                        ribbonText={"+" + AddOnPrices.IMPRINT_COLOR[ImprintColor.TWO] + "%"}
                        ribbonColor={'#1d4e9b'}
                        placement={isMobile ? 'bottomLeft' : undefined}
                        helpText={
                            <p className="text-start mb-0">
                                <b>2 Imprint Colors: </b><br />2 Imprint Colors offers you to choose
                                two colors for your text, numbers, and / or artwork. This
                                choice is best for customizations requiring only two colors.
                                White is included (free).
                            </p>
                        }
                    />
                </RadioButton>
            </Col>
            <Col xs={12} sm={12} md={6}>
                <RadioButton value={ImprintColor.THREE}>
                    <AddonCard
                        title="3 Imprint Colors"
                        imageUrl={addonImages[ImprintColor.THREE](config.product)}
                        ribbonText={"+" + AddOnPrices.IMPRINT_COLOR[ImprintColor.THREE] + "%"}
                        ribbonColor={'#1d4e9b'}
                        helpText={
                            <p className="text-start mb-0">
                                <b>3 Imprint Colors: </b><br />3 Imprint Colors offers you to choose
                                three colors for your text, numbers, and / or artwork. This
                                choice is best for customizations requiring only three colors.
                                White is included (free).
                            </p>
                        }
                    />
                </RadioButton>
            </Col>
            <Col xs={12} sm={12} md={6}>
                <RadioButton value={ImprintColor.UNLIMITED}>
                    <AddonCard
                        title="Unlimited Imprint Colors"
                        imageUrl={addonImages[ImprintColor.UNLIMITED](config.product)}
                        ribbonText={"+" + AddOnPrices.IMPRINT_COLOR[ImprintColor.UNLIMITED] + "%"}
                        ribbonColor={'#1d4e9b'}
                        placement={isMobile ? 'bottomLeft' : "left"}
                        helpText={
                            <p className="text-start mb-0">
                                <b>Unlimited Imprint Colors:</b><br /> Unlimited Imprint Colors offer you
                                to choose four or more colors for your text, numbers, and / or
                                artwork. This choice is best for customizations requiring various colors.
                                White is included (free).
                            </p>
                        }
                    />
                </RadioButton>
            </Col>
        </Radio.Group>
    </StepCard>
}

export default ChooseImprintColor;