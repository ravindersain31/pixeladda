import React, { useState } from 'react';
import { Col, Row, Form } from 'antd';
import { Shape } from '@react/editor/redux/interface';
import AddonCard from '@react/editor/components/Cards/AddonCard';
import RadioButton from "@react/editor/components/Radio/RadioButton";
import { AddOnPrices } from '@react/editor/redux/reducer/editor/interface';
import { StyledRadioGroup,FormItem } from '../../styled';
import { isPromoStore } from '@react/editor/helper/editor';

const Shapes = () => {
    const [shape, setShape] = useState<string>(Shape.SQUARE);

    const onShapeChange = (e: any) => {
        setShape(e.target.value);
    };

    return (
        <FormItem
            name="shape"
            initialValue={Shape.SQUARE}
            rules={[{ required: true, message: 'Please select a shape!' }]}
        >
            <StyledRadioGroup
                className="ant-row"
                value={shape}
                onChange={onShapeChange}
            >
                    <Col xs={12} sm={12} md={6}>
                        <RadioButton value={Shape.SQUARE}>
                            <AddonCard
                                title="Square / Rectangle"
                                imageUrl={isPromoStore() ? "https://static.yardsignplus.com/storage/promo-store/Imprint-Color-Icon.svg" : "https://static.yardsignplus.com/assets/Square.png"}
                                ribbonText={
                                    AddOnPrices.SHAPE[Shape.SQUARE] === 0
                                        ? "FREE"
                                        : AddOnPrices.SHAPE[Shape.SQUARE]
                                }
                                ribbonColor={"#1B8A1B"}
                                helpText={
                                    <p className="text-start mb-0 custom-search-addons-popover">
                                        <b>Square / Rectangle Shape:</b><br />
                                        Square or Rectangle Shape allows
                                        printing and cutting along any
                                        defined square or rectangular
                                        border. This is the most common
                                        and popular choice for standard
                                        yard signs, including default sizes.
                                    </p>
                                }
                            />
                        </RadioButton>
                    </Col>
                    <Col xs={12} sm={12} md={6}>
                        <RadioButton value={Shape.CIRCLE}>
                            <AddonCard
                                title="Circle"
                                imageUrl={isPromoStore() ? "https://static.yardsignplus.com/storage/promo-store/Circle-Promo-Icon.svg" : "https://static.yardsignplus.com/assets/Circle.png"}
                                ribbonText={"+" + AddOnPrices.SHAPE[Shape.CIRCLE] + "%"}
                                ribbonColor={"#1d4e9b"}
                                helpText={
                                    <p className="text-start mb-0 custom-search-addons-popover">
                                        <b>Circle Shape:</b><br />
                                        Circle Shape allows printing
                                        and cutting along any circular
                                        border. This includes any
                                        round outlining.
                                    </p>
                                }
                            />
                        </RadioButton>
                    </Col>
                    <Col xs={12} sm={12} md={6}>
                        <RadioButton value={Shape.OVAL}>
                            <AddonCard
                                title="Oval"
                                imageUrl={isPromoStore() ? "https://static.yardsignplus.com/storage/promo-store/Oval-Promo-Icon.svg" : "https://static.yardsignplus.com/assets/Oval.png"}
                                ribbonText={"+" + AddOnPrices.SHAPE[Shape.OVAL] + "%"}
                                ribbonColor={"#1d4e9b"}
                                helpText={
                                    <p className="text-start mb-0 custom-search-addons-popover">
                                        <b>Oval Shape:</b><br />
                                        Oval Shape allows printing
                                        and cutting along any oval
                                        border. This includes any
                                        oval outlining.
                                    </p>
                                }
                            />
                        </RadioButton>
                    </Col>
                    <Col xs={12} sm={12} md={6}>
                        <RadioButton value={Shape.CUSTOM}>
                            <AddonCard
                                title="Custom"
                                imageUrl={isPromoStore() ? "https://static.yardsignplus.com/storage/promo-store/Custom-Promo-Icon.svg" : "https://static.yardsignplus.com/assets/Custom.png"}
                                ribbonText={"+" + AddOnPrices.SHAPE[Shape.CUSTOM] + "%"}
                                ribbonColor={"#1d4e9b"}
                                helpText={
                                    <p className="text-start mb-0 custom-search-addons-popover">
                                        <b>Custom Shape:</b><br />
                                        Custom Shape allows printing and cutting along
                                        any irregular border or die cut. This includes
                                        any undefined outlining for fully custom signs
                                        We will cut along the outer edges of your custom
                                        shape. Please leave a comment if necessary on
                                        your final cut requirements.
                                    </p>
                                }
                            />
                        </RadioButton>
                    </Col>
                    <Col xs={12} sm={12} md={6}>
                        <RadioButton value={Shape.CUSTOM_WITH_BORDER}>
                            <AddonCard
                                title="Custom with Border"
                            imageUrl={isPromoStore() ? "https://static.yardsignplus.com/storage/YS-Steps/choose-your-shape/promo-custom-with-border-blue.webp" : "https://static.yardsignplus.com/storage/YS-Steps/choose-your-shape/custom-with-border.webp"}
                                ribbonText={"+" + AddOnPrices.SHAPE[Shape.CUSTOM_WITH_BORDER] + "%"}
                                ribbonColor={"#1d4e9b"}
                                helpText={
                                    <p className="text-start mb-0 custom-search-addons-popover">
                                        <b>Custom with Border Shape:</b><br />
                                        Custom with Border Shape allows printing and cutting
                                        along any irregular border or die cut. This includes any
                                        undefined outlining for fully custom signs. We will print
                                        and cut along the outer edges of your custom with
                                        border shape. Please leave a comment if necessary on
                                        your final print and cut requirements.
                                    </p>
                                }
                            />
                        </RadioButton>
                    </Col>
            </StyledRadioGroup>
        </FormItem>
    );
};

export default Shapes;
