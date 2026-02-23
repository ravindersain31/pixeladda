import React, { useMemo, useState } from 'react';
import { Col, Row, Form } from 'antd';
import { ImprintColor } from '@react/editor/redux/interface';
import AddonCard from '@react/editor/components/Cards/AddonCard';
import RadioButton from "@react/editor/components/Radio/RadioButton";
import { AddOnPrices } from '@react/editor/redux/reducer/editor/interface';
import { StyledRadioGroup,FormItem } from '../../styled';
import { imprintImagesForQuote } from '@react/editor/helper/addonImprintImages';

const ImprintColors = ({ addons }: any) => {
    const [imprintColor, setImprintColor] = useState<string>(ImprintColor.ONE);
    const addonImages = useMemo(
        () => imprintImagesForQuote(addons),
        [addons?.shape]
    );

    const onImprintColorChange = (e: any) => {
        setImprintColor(e.target.value);
    };

    return (
        <FormItem
            name="imprintColor"
            initialValue={ImprintColor.ONE}
            rules={[{ required: true, message: 'Please select an imprint color!' }]}
        >
            <StyledRadioGroup
                className="ant-row"
                value={imprintColor}
                onChange={onImprintColorChange}
            >
                <Col xs={12} sm={12} md={6}>
                    <RadioButton value={ImprintColor.ONE}>
                        <AddonCard
                            title="1 Imprint Color"
                            imageUrl={addonImages[ImprintColor.ONE]}
                            ribbonText={AddOnPrices.IMPRINT_COLOR[ImprintColor.ONE] === 0 ? "FREE" : AddOnPrices.IMPRINT_COLOR[ImprintColor.ONE]}
                            ribbonColor={'#1B8A1B'}
                            helpText={
                                <p className="text-start mb-0 custom-search-addons-popover">
                                    <b>1 Imprint Color: </b><br /> 1 Imprint Color offers you to choose
                                    one color for your text, numbers, and/or artwork. This
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
                            imageUrl={addonImages[ImprintColor.TWO]}
                            ribbonText={"+" + AddOnPrices.IMPRINT_COLOR[ImprintColor.TWO] + "%"}
                            ribbonColor={'#1d4e9b'}
                            helpText={
                                <p className="text-start mb-0 custom-search-addons-popover">
                                    <b>2 Imprint Colors: </b><br /> 2 Imprint Colors offers you to choose
                                    two colors for your text, numbers, and/or artwork. This
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
                            imageUrl={addonImages[ImprintColor.THREE]}
                            ribbonText={"+" + AddOnPrices.IMPRINT_COLOR[ImprintColor.THREE] + "%"}
                            ribbonColor={'#1d4e9b'}
                            helpText={
                                <p className="text-start mb-0 custom-search-addons-popover">
                                    <b>3 Imprint Colors: </b><br /> 3 Imprint Colors offers you to choose
                                    three colors for your text, numbers, and/or artwork. This
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
                            imageUrl={addonImages[ImprintColor.UNLIMITED]}
                            ribbonText={"+" + AddOnPrices.IMPRINT_COLOR[ImprintColor.UNLIMITED] + "%"}
                            ribbonColor={'#1d4e9b'}
                            helpText={
                                <p className="text-start mb-0 custom-search-addons-popover">
                                    <b>Unlimited Imprint Colors:</b><br /> Unlimited Imprint Colors offer you
                                    to choose four or more colors for your text, numbers, and/or
                                    artwork. This choice is best for customizations requiring various colors.
                                    White is included (free).
                                </p>
                            }
                        />
                    </RadioButton>
                </Col>
            </StyledRadioGroup>
        </FormItem>
    );
};

export default ImprintColors;
