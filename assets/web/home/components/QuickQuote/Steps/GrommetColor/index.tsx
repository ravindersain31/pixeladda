import React, { useMemo, useState } from 'react';
import { Col, Row, Form } from 'antd';
import { GrommetColor } from '@react/editor/redux/interface';
import AddonCard from '@react/editor/components/Cards/AddonCard';
import RadioButton from "@react/editor/components/Radio/RadioButton";
import { StyledRadioGroup,FormItem } from '../../styled';
import { AddOnPrices } from '@react/editor/redux/reducer/editor/interface';
import { grommetColorImagesForQuote } from '@react/editor/helper/addonGrommetColorImages';

const GrommetColors = ({ addons }: any) => {
    const [grommetColor, setGrommetColor] = useState<string>(GrommetColor.SILVER);

    const addonImages = useMemo(
        () => grommetColorImagesForQuote(addons),
        [addons?.shape]
    );

    const onGrommetColorChange = (e: any) => {
        setGrommetColor(e.target.value);
    };

    return (
        <FormItem
            name="grommetColor"
            initialValue={GrommetColor.SILVER}
            rules={[{ required: true, message: 'Please select a grommet color!' }]}
        >
            <StyledRadioGroup
                className="ant-row"
                value={grommetColor}
                onChange={onGrommetColorChange}
            >
                <Col xs={12} sm={12} md={6}>
                    <RadioButton value={GrommetColor.SILVER}>
                        <AddonCard
                            title="Silver"
                            imageUrl={addonImages[GrommetColor.SILVER]}
                            ribbonText={AddOnPrices.GROMMET_COLOR[GrommetColor.SILVER] === 0 ? 'FREE' : AddOnPrices.GROMMET_COLOR[GrommetColor.SILVER]}
                            ribbonColor={'#1B8A1B'}
                        />
                    </RadioButton>
                </Col>
                {/* <Col xs={12} sm={12} md={6}>
                    <RadioButton value={GrommetColor.BLACK}>
                        <AddonCard
                            title="Black"
                            imageUrl={addonImages[GrommetColor.BLACK]}
                            ribbonText={"+" + AddOnPrices.GROMMET_COLOR[GrommetColor.BLACK] + "%"}
                            ribbonColor={'#1d4e9b'}
                        />
                    </RadioButton>
                </Col> */}
                <Col xs={12} sm={12} md={6}>
                    <RadioButton value={GrommetColor.GOLD}>
                        <AddonCard
                            title="Gold"
                            imageUrl={addonImages[GrommetColor.GOLD]}
                            ribbonText={"+" + AddOnPrices.GROMMET_COLOR[GrommetColor.GOLD] + "%"}
                            ribbonColor={'#1d4e9b'}
                        />
                    </RadioButton>
                </Col>
            </StyledRadioGroup>
        </FormItem>
    );
};

export default GrommetColors;
