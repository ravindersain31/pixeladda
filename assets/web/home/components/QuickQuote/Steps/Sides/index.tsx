import React, { useState } from 'react';
import { Col, Row, Form } from 'antd';
import { Sides } from '@react/editor/redux/interface';
import AddonCard from '@react/editor/components/Cards/AddonCard';
import RadioButton from "@react/editor/components/Radio/RadioButton";
import { AddOnPrices } from '@react/editor/redux/reducer/editor/interface';
import { StyledRadioGroup,FormItem } from '../../styled';
import { isPromoStore } from '@react/editor/helper/editor';

const Side = () => {
    const [side, setSide] = useState<string>(Sides.SINGLE);

    const onSidesChange = (e: any) => {
        setSide(e.target.value);
    };


    return (
        <FormItem
            name="sides"
            initialValue={Sides.SINGLE}
            rules={[{ required: true, message: 'Please select a size!' }]}
        >
            <StyledRadioGroup
                className="ant-row"
                value={side}
                onChange={onSidesChange}
            >
                <Col xs={12} sm={12} md={6}>
                    <RadioButton value={Sides.SINGLE}>
                        <AddonCard
                            title="Single Sided"
                            imageUrl={isPromoStore() ? "https://static.yardsignplus.com/storage/promo-store/Step-3-01.svg" : "https://static.yardsignplus.com/assets/side-option-front.png"}
                            ribbonText={AddOnPrices.SIDES[Sides.SINGLE] === 0 ? "FREE" : AddOnPrices.SIDES[Sides.SINGLE]}
                            ribbonColor={'#1B8A1B'}
                            helpText={
                                <p className="text-start mb-0 custom-search-addons-popover">
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
                <Col xs={12} sm={12} md={6}>
                    <RadioButton value={Sides.DOUBLE}>
                        <AddonCard
                            title="Double Sided"
                            imageUrl={isPromoStore() ? "https://static.yardsignplus.com/storage/promo-store/Step-3-02.svg" : "https://static.yardsignplus.com/assets/side-option-front-back.png"}
                            ribbonText={"+" + AddOnPrices.SIDES[Sides.DOUBLE] + "%"}
                            ribbonColor={'#1d4e9b'}
                            placement={'top'}
                            helpText={
                                <p className="text-start mb-0 custom-search-addons-popover">
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
            </StyledRadioGroup>
        </FormItem>
    )
}

export default Side