import { Radio, Col, Button } from "antd";
import { useAppDispatch, useAppSelector } from "@react/editor/hook.ts";
import StepCard from '@react/editor/components/Cards/StepCard';
import RadioButton from "@react/editor/components/Radio/RadioButton";
import AddonCard from "@react/editor/components/Cards/AddonCard";
import { Frame } from "@react/editor/redux/interface.ts";
import { StepProps } from "../interface.ts";
import actions from "@react/editor/redux/actions";
import { useEffect, useState } from "react";
import { isDisallowedFrameSize } from "@react/editor/helper/template.ts";
import { getPriceFromPriceChart, number_format } from "@react/editor/helper/pricing.ts";
import { calculateCartTotalFrameQuantity } from "@react/editor/helper/quantity.ts";
import { AddOnPrices, Shape } from "@react/editor/redux/reducer/editor/interface.ts";
import { isMobile } from "react-device-detect";
import { AlertMessage } from "../ChooseDeliveryDate/styled.tsx";
import { StyledButton, StyledPopoverButton, StyledRibbon } from "./styled.tsx";
import { AdditionalCol, PopoverContent, StyledPopover } from "../../Cards/AddonCard/styled.tsx";
import { QuestionCircleOutlined } from "@ant-design/icons";
import AdditionalStakesModal from "./AdditionalStakesModal.tsx";
import { getStakeOptions } from "@react/editor/helper/stakes.tsx";

const ChooseYourFrame = ({ stepNumber }: StepProps) => {
    const frame = useAppSelector(state => state.editor.frame);
    const editor = useAppSelector(state => state.editor);
    const config = useAppSelector(state => state.config);
    const canvas = useAppSelector(state => state.canvas);
    const currentItem = useAppSelector(state => state.editor.items[canvas.item.id]);
    const [framePrices, setFramePrices] = useState<{ [key: string]: number }>(AddOnPrices.FRAME);
    const searchParams = new URLSearchParams(window.location.search);
    const urlFrame = searchParams.get('frame');
    const dispatch = useAppDispatch();
    const [frameName, setFrameName] = useState<string | string[]>(urlFrame ?? editor.frame);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const frameOptions = getStakeOptions(config.product, currentItem, framePrices, true);
    const handleOpenStakesModal = (open: boolean) => setIsModalOpen(open);
    const [popoverVisible, setPopoverVisible] = useState(false);

    const onFrameChange = (name: string) => {
        dispatch(actions.editor.updateFrame(name));
        setFrameName(name);
    }

    const disallowedFrameForShape = editor.shape == Shape.CIRCLE ? Object.values(editor.items).some((item) =>
        (!isDisallowedFrameSize(item.name, editor.shape) && item.quantity > 0)
    ) || false : true;

    const calculateFramePrice = () => {
        const totalQuantityWithFrames = calculateCartTotalFrameQuantity();

        const getFramePrice = (frameType: Frame): number => {
            const quantity = (totalQuantityWithFrames.totalQuantity + (config.cart.totalFrameQuantity[frameType] || 0)) - (config.cart.currentFrameQuantity[frameType] || 0);
            const pricing = config.product.framePricing.frames[`pricing_${frameType}`].pricing;
            const basePrice = getPriceFromPriceChart(pricing, quantity <= 0 ? 1 : quantity);
            const price = basePrice ? basePrice : AddOnPrices.FRAME[frameType as keyof typeof AddOnPrices.FRAME];
            return parseFloat(price.toFixed(2));
        };

        const framePrices = {
            [Frame.NONE]: getFramePrice(Frame.NONE),
            [Frame.WIRE_STAKE_10X30]: getFramePrice(Frame.WIRE_STAKE_10X30),
            [Frame.WIRE_STAKE_10X24]: getFramePrice(Frame.WIRE_STAKE_10X24),
            [Frame.WIRE_STAKE_10X30_PREMIUM]: (number_format((getFramePrice(Frame.WIRE_STAKE_10X30_PREMIUM)), 2)),
            [Frame.WIRE_STAKE_10X24_PREMIUM]: (number_format((getFramePrice(Frame.WIRE_STAKE_10X24_PREMIUM)), 2)),
            [Frame.WIRE_STAKE_10X30_SINGLE]: (number_format((getFramePrice(Frame.WIRE_STAKE_10X30_SINGLE)), 2)),
        };

        setFramePrices(framePrices);
        dispatch(actions.editor.updateFramePrice(totalQuantityWithFrames));
    }

    useEffect(() => {
        if (!config.product.isYardLetters) {
            dispatch(actions.editor.updateFrame(frameName));
            if (!disallowedFrameForShape) {
                dispatch(actions.editor.updateFrame(Frame.NONE));
            }
            calculateFramePrice();
        }
    }, [editor.totalQuantity, editor.frame, canvas.customSize.templateSize, editor.shape]);

    const handlePopoverButtonClick = (event: React.MouseEvent) => {
        event.stopPropagation();
    };

    const handlePopoverModalOpen = (e: React.MouseEvent) => {
        e.stopPropagation();
        handleOpenStakesModal(true);
        setPopoverVisible(false);
    };

    const OutOfStockRibbon = () => {
        return (
            <StyledRibbon
                title="edit"
                placement={'start'}
                text={
                    <span>
                        Out of Stock
                        <StyledPopover
                            placement={isMobile ? "bottom" : undefined}
                            content={<PopoverContent>
                                <p className="text-start mb-0">
                                    <b>In Stock Aug 28:</b><br />
                                    We will be in stock on our 10"W x 30"H wire stakes<br />
                                    by August 28, 2024. You may still order wire<br />
                                    stakes. We will immediately ship wire stakes once<br />
                                    we are back in stock.
                                </p>
                            </PopoverContent>}
                        >
                            <Button shape="circle" icon={<QuestionCircleOutlined />} />
                        </StyledPopover>
                    </span>
                }
            />
        );
    }

    const mobilePlacementMap: any = {
        [Frame.WIRE_STAKE_10X30_SINGLE]: 'right',
        [Frame.WIRE_STAKE_10X24_PREMIUM]: 'bottom',
    };

    const desktopPlacementMap: Record<string, string> = {
        [Frame.WIRE_STAKE_10X30_SINGLE]: 'right',
    };

    return <StepCard id="choose-your-frame" title="Choose Your Frame" stepNumber={stepNumber}>
        <Radio.Group
            className="ant-row"
            value={frame}
            onChange={(e) => onFrameChange(e.target.value)}
        >
            {(!disallowedFrameForShape) && (
                <Col xs={24} md={24} lg={24}>
                    <AlertMessage>Wire stakes are unavailable for {!disallowedFrameForShape && 'circle shaped signs less than 12'} inches wide. Please increase the width to order wire stakes.</AlertMessage>
                </Col>
            )}

            {frameOptions.map((option) => (
                <Col xs={12} sm={12} md={8} lg={6} key={option.key}>
                    <RadioButton value={option.key} disabled={!disallowedFrameForShape}>
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
                            disable={!disallowedFrameForShape}
                            helpText={option.helpText}
                        />
                    </RadioButton>
                </Col>
            ))}
            <AdditionalCol xs={24}>
                <StyledButton
                    onClick={() => handleOpenStakesModal(true)}
                >
                    Add Additional Stakes
                    <StyledPopover
                        placement={isMobile ? "bottom" : undefined}
                        overlayStyle={{ width: 300 }}
                        open={popoverVisible}
                        onOpenChange={setPopoverVisible}
                        content={
                            <PopoverContent onClick={handlePopoverButtonClick}>
                                <>
                                    <p className="text-start mb-0">
                                        <b>Add Additional Stakes:</b>
                                        <br />
                                        If you need more stakes than the number of signs in your order, click&nbsp;
                                        <StyledPopoverButton className="p-0" type="link" onClick={handlePopoverModalOpen}>Add Additional Stakes&nbsp;</StyledPopoverButton>
                                        to enter the additional stake quantity. If you want the same number of stakes as signs
                                        (default), you can leave this blank. This add-on will not change your sign quantity,
                                        production time, or delivery fee.
                                    </p>
                                </>
                            </PopoverContent>
                        }
                    >
                        <Button
                            shape="circle"
                            icon={<QuestionCircleOutlined />}
                            onClick={handlePopoverButtonClick}
                        />
                    </StyledPopover>
                </StyledButton>
            </AdditionalCol>
        </Radio.Group>
        <AdditionalStakesModal
            visible={isModalOpen}
            onClose={() => handleOpenStakesModal(false)}
            framePrices={framePrices}
        />
    </StepCard>
}

export default ChooseYourFrame;