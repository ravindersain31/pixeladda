import React, { useEffect, useMemo, useState } from 'react';
import { Col, Row, Form } from 'antd';
import { Frame } from '@react/editor/redux/interface';
import AddonCard from '@react/editor/components/Cards/AddonCard';
import RadioButton from "@react/editor/components/Radio/RadioButton";
import { AddOnPrices } from '@react/editor/redux/reducer/editor/interface';
import { StyledRadioGroup, FormItem, AlertMessage } from '../../styled';
import { isMobile } from 'react-device-detect';
import { frameImagesForQuote } from '@react/editor/helper/addonFrameImages';

interface FrameProps {
    framePrices: { [key: string]: number };
    disallowedFrameForShape: boolean;
    addons?: { [key: string]: string };
}

const Frames = ({ framePrices, disallowedFrameForShape, addons }: FrameProps) => {
    const [selectedFrame, setSelectedFrame] = useState<string>(Frame.NONE);

    const addonImages = useMemo(
        () => frameImagesForQuote(addons),
        [addons?.shape]
    );

    const onFrameChange = (e: any) => {
        setSelectedFrame(e.target.value);
    };

    const ribbons: { [key: string]: string[] } = {
        'WIRE_STAKE_10X24': ['Best Seller', 'Standard'],
    };

    const sizeRibbonsColor: { [key: string]: string[] } = {
        'WIRE_STAKE_10X24': ['#3398d9', '#66b94d'],
    };

    return (
        <FormItem
            name="frame"
            initialValue={Frame.NONE}
            rules={[{ required: true, message: 'Please select a frame!' }]}
        >
            <StyledRadioGroup
                className="ant-row"
                id='frame'
                value={selectedFrame}
                onChange={onFrameChange}
            >
                {(disallowedFrameForShape) && (
                    <Col xs={24} md={24} lg={24}>
                        <AlertMessage>Wire stakes are unavailable for {disallowedFrameForShape && 'circle shaped signs less than 12'} inches wide. Please increase the width to order wire stakes.</AlertMessage>
                    </Col>
                )}
                <Col xs={12} sm={12} md={8} lg={6}>
                    <RadioButton value={Frame.NONE}>
                        <AddonCard
                            title="No Wire Stake"
                            imageUrl={addonImages[Frame.NONE]}
                            ribbonText="FREE"
                            ribbonColor={'#1B8A1B'}
                        />
                    </RadioButton>
                </Col>
                <Col  xs={12} sm={12} md={8} lg={6}>
                    <RadioButton value={Frame.WIRE_STAKE_10X24} disabled={disallowedFrameForShape}>
                        <AddonCard
                            title={"Standard 10\"W x 24\"H Wire Stake"}
                            imageUrl={addonImages[Frame.WIRE_STAKE_10X24]}
                            ribbonText={[
                                `$${framePrices[Frame.WIRE_STAKE_10X24]}`,
                                ...(ribbons[Frame.WIRE_STAKE_10X24]),
                            ]}
                            ribbonColor={[
                                '#1d4e9b',
                                ...(sizeRibbonsColor[Frame.WIRE_STAKE_10X24]),
                            ]}
                            disable={disallowedFrameForShape}
                            helpText={
                                <p className="text-start mb-0 custom-search-addons-popover">
                                    <b>10"W x 24"H Wire Stake:</b><br />
                                    Increase exposure with our durable wire H-stakes.
                                    All signs include corrugated holes or flutes along the
                                    top and bottom edges, allowing for easy and instant
                                    installation of wire stakes. Simply insert the wire
                                    stake directly into the corrugated holes. Then place
                                    the wire stake in any soft ground (e.g. grass or dirt)
                                    for support. 3.4mm thick, 10 gauge (wire diameter).
                                    Recommended for all standard and custom sizes
                                    with a minimum of 10" width.
                                </p>
                            }
                        />
                    </RadioButton>
                </Col>
                <Col xs={12} sm={12} md={8} lg={6}>
                    <RadioButton value={Frame.WIRE_STAKE_10X24_PREMIUM} disabled={disallowedFrameForShape}>
                        <AddonCard
                            title={"Premium 10\"W x 24\"H Wire Stake"}
                            imageUrl={addonImages[Frame.WIRE_STAKE_10X24_PREMIUM]}
                            ribbonText={`$${framePrices[Frame.WIRE_STAKE_10X24_PREMIUM]}`}
                            ribbonColor={'#1d4e9b'}
                            disable={disallowedFrameForShape}
                            helpText={
                                <p className="text-start mb-0 custom-search-addons-popover">
                                    <b>Premium 10"W x 24"H Wire Stake:</b><br/>
                                    Increase exposure with our premium, thicker,
                                    and greater durability wire U-stakes. All signs
                                    include corrugated holes along the top and
                                    bottom edges, allowing for easy and instant
                                    installation of wire stakes. Simply insert
                                    the wire stake directly into the corrugated
                                    holes. Then place the wire stake in any soft
                                    ground (e.g. grass or dirt) for support.
                                    3.4mm thickness near top, 5.0mm thickness at base,
                                    1/4” galvanized steel, 10 gauge (wire diameter)
                                    near top, 6 gauge near base, and 0.2kg weight.
                                    Recommended for all standard and custom
                                    sizes with a minimum of 10" width.
                                </p>
                            }
                        />

                    </RadioButton>
                </Col>
                <Col xs={12} sm={12} md={8} lg={6}>
                    <RadioButton value={Frame.WIRE_STAKE_10X30_SINGLE} disabled={disallowedFrameForShape}>
                        <AddonCard
                            title={"Single 30\"H Wire Stake"}
                            imageUrl={addonImages[Frame.WIRE_STAKE_10X30_SINGLE]}
                            ribbonText={`$${framePrices[Frame.WIRE_STAKE_10X30_SINGLE].toFixed(2)}`}
                            ribbonColor={'#1d4e9b'}
                            disable={disallowedFrameForShape}
                            helpText={
                                <p className="text-start mb-0 custom-search-addons-popover">
                                    <b>Single 30"H Wire Stake:</b><br/>
                                    Increase exposure with our durable single
                                    wire stakes. All signs include corrugated
                                    holes along the top and bottom edges,
                                    allowing for easy and instant installation
                                    of wire stakes. Simply insert the single wire
                                    stake directly into the corrugated holes. Then
                                    place the wire stake in any soft ground
                                    (e.g. grass or dirt) for support. 3.4mm
                                    thick, 10 gauge (wire diameter). Recommended
                                    for all standard and custom sizes requiring
                                    only one single stake for support.
                                </p>
                            }
                        />
                    </RadioButton>
                </Col>
            </StyledRadioGroup>
        </FormItem>
    );
};

export default Frames;
