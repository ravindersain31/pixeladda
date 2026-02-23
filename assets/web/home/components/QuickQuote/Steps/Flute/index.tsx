import React, { useEffect, useState } from 'react';
import { Col, Row, Form } from 'antd';
import { Flute } from '@react/editor/redux/interface';
import AddonCard from '@react/editor/components/Cards/AddonCard';
import RadioButton from "@react/editor/components/Radio/RadioButton";
import { AddOnPrices } from '@react/editor/redux/reducer/editor/interface';
import { StyledRadioGroup, FormItem, AlertMessage } from '../../styled';
import { isMobile } from 'react-device-detect';
import { isPromoStore } from '@react/editor/helper/editor';
import { getFluteOptions } from '@react/editor/helper/flute';

interface FluteProps {
    product: any;
    addons: any;
}

const Flutes = ({ product, addons }: FluteProps) => {
    const [selectedFlute, setSelectedFlute] = useState<string>(Flute.VERTICAL);

    const onFluteChange = (e: any) => {
        setSelectedFlute(e.target.value);
    };

    const currentItem = {
        addons,
        isCustomQuickQuote: true,
    };

    const ribbons: { [key: string]: string[] } = {
        'VERTICAL': ['Best Seller', 'Most Popular'],
    };

    const sizeRibbonsColor: { [key: string]: string[] } = {
        'VERTICAL': ['#3398d9', '#66b94d'],
    };

    const fluteOptions = getFluteOptions(product, currentItem, AddOnPrices.Flute);

    const mobilePlacementMap: any = {
        [Flute.VERTICAL]: 'top',
        [Flute.HORIZONTAL]: 'topLeft',
    };

    const desktopPlacementMap: Record<string, string> = {
        [Flute.VERTICAL]: 'right',
    };
    return (
        <FormItem
            name="flute"
            initialValue={Flute.VERTICAL}
            rules={[{ required: true, message: 'Please select a Flutes!' }]}
        >
            <StyledRadioGroup
                className="ant-row"
                id='flute'
                value={selectedFlute}
                onChange={onFluteChange}
            >
                {fluteOptions.map((option: any) => (
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
            </StyledRadioGroup>
        </FormItem>
    );
};

export default Flutes;
