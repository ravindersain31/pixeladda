import React, { useMemo, useState } from 'react';
import { Col, Row, Form } from 'antd';
import { Grommets } from '@react/editor/redux/interface';
import AddonCard from '@react/editor/components/Cards/AddonCard';
import RadioButton from "@react/editor/components/Radio/RadioButton";
import { AddOnPrices } from '@react/editor/redux/reducer/editor/interface';
import { StyledRadioGroup, FormItem } from '../../styled';
import { grommetImagesForQuote } from '@react/editor/helper/addonGrommetsImage';
import { isMobile } from 'react-device-detect';

const Grommet = ({ addons }: any) => {
    const [selectedGrommet, setSelectedGrommet] = useState<string>(Grommets.NONE);

    const addonImages = useMemo(
        () => grommetImagesForQuote(addons),
        [addons?.shape]
    );

    const onGrommetChange = (e: any) => {
        setSelectedGrommet(e.target.value);
    };

    return (
        <FormItem
            name="grommets"
            initialValue={Grommets.NONE}
            rules={[{ required: true, message: 'Please select a grommet option!' }]}
        >
            <StyledRadioGroup
                className="ant-row"
                value={selectedGrommet}
                onChange={onGrommetChange}
            >
                <Col xs={12} sm={12} md={8} lg={5}>
                    <RadioButton value={Grommets.NONE}>
                        <AddonCard
                            title="None"
                            imageUrl={addonImages[Grommets.NONE]}
                            ribbonText={AddOnPrices.GROMMETS[Grommets.NONE] === 0 ? 'FREE' : AddOnPrices.GROMMETS[Grommets.NONE]}
                            ribbonColor={'#1B8A1B'}
                        />
                    </RadioButton>
                </Col>
                <Col xs={12} sm={12} md={8} lg={5}>
                    <RadioButton value={Grommets.TOP_CENTER}>
                        <AddonCard
                            title="Top Center"
                            imageUrl={addonImages[Grommets.TOP_CENTER]}
                            ribbonText={"+" + AddOnPrices.GROMMETS[Grommets.TOP_CENTER] + "%"}
                            ribbonColor={'#1d4e9b'}
                        />
                    </RadioButton>
                </Col>
                <Col xs={12} sm={12} md={8} lg={5}>
                    <RadioButton value={Grommets.TOP_CORNERS}>
                        <AddonCard
                            title="Top Corners"
                            imageUrl={addonImages[Grommets.TOP_CORNERS]}
                            ribbonText={"+" + AddOnPrices.GROMMETS[Grommets.TOP_CORNERS] + "%"}
                            ribbonColor={'#1d4e9b'}
                        />
                    </RadioButton>
                </Col>
                <Col xs={12} sm={12} md={8} lg={5}>
                    <RadioButton value={Grommets.FOUR_CORNERS}>
                        <AddonCard
                            title="Four Corners"
                            imageUrl={addonImages[Grommets.FOUR_CORNERS]}
                            ribbonText={"+" + AddOnPrices.GROMMETS[Grommets.FOUR_CORNERS] + "%"}
                            ribbonColor={'#1d4e9b'}
                        />
                    </RadioButton>
                </Col>
                <Col xs={12} sm={12} md={8} lg={5}>
                    <RadioButton value={Grommets.SIX_CORNERS}>
                        <AddonCard
                            title="Six Corners"
                            imageUrl={addonImages[Grommets.SIX_CORNERS]}
                            ribbonText={"+" + AddOnPrices.GROMMETS[Grommets.SIX_CORNERS] + "%"}
                            ribbonColor={'#1d4e9b'}
                        />
                    </RadioButton>
                </Col>
                <Col xs={12} sm={12} md={8} lg={5}>
                    <RadioButton value={Grommets.CUSTOM_PLACEMENT}>
                        <AddonCard
                            title="Custom Placement"
                            imageUrl={addonImages[Grommets.CUSTOM_PLACEMENT]}
                            ribbonText={"+" + AddOnPrices.GROMMETS[Grommets.CUSTOM_PLACEMENT] + "%"}
                            ribbonColor={'#1d4e9b'}
                            placement={isMobile ? 'bottomLeft' : 'left'}
                            helpText={
                                <p className="text-start mb-0">
                                    <b>Custom Placement</b><br />
                                    Custom Placement allows you to choose the number of grommets and position for each. Please leave a comment with the total number of grommets and positions for each required. We will apply this to each sign.
                                </p>
                            }
                        />
                    </RadioButton>
                </Col>
            </StyledRadioGroup>
        </FormItem>
    );
};

export default Grommet;
