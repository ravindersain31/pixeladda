import React, { memo, useEffect, useState, useMemo } from "react";
import { isEmpty, isNull } from "lodash";
import { QuestionCircleOutlined } from "@ant-design/icons";
import { Button, Col, RadioChangeEvent } from "antd";
import { NumericFormat } from "react-number-format";
import { isMobile } from "react-device-detect";
import dayjs from "dayjs";

import { StepProps } from "@orderSample/utils/interface";
import StepCard from "@orderSample/components/Cards/StepCard";
import { useAppDispatch, useAppSelector } from "@orderSample/hook";
import { DeliveryMethod, SHIPPING_MAX_DISCOUNT_AMOUNT } from "@orderSample/redux/reducer/cart/interface";
import actions from "@orderSample/redux/actions";
import {
    calculateRemainingAmountForFreeShipping,
    checkSaturdayDeliveryEligibility,
    getShippingFromShippingChart,
    getShippingRateByDayNumber,
    hasSaturdayDelivery,
} from "@orderSample/utils/helper";
import {
    AlertMessage,
    DeliveryCost,
    MonthYear,
    StyledCard,
    StyledRadioButton,
    StyledRadioGroup,
    Date
} from "./styled";
import { StyledPopover } from "@orderSample/components/Cards/AddonCard/styled";
import { PopoverContent } from "@orderSample/components/Ribbon/styled";
import { shallowEqual } from "react-redux";
import ChooseDeliveryMethod from "@orderSample/components/Steps/ChooseDeliveryMethod";

const ChooseDeliveryDate = ({ stepNumber = 4 }: StepProps) => {
    const dispatch = useAppDispatch();

    const { config, cartStage } = useAppSelector((state) => ({
        config: state.config,
        cartStage: state.cartStage,
    }), shallowEqual);

    const isRequestPickup = cartStage.deliveryMethod.key === DeliveryMethod.REQUEST_PICKUP;

    const [shippingDates, setShippingDates] = useState<{ [key: string]: any }>({});
    const [lastSelectedShippingDay, setLastSelectedShippingDay] = useState<any>(config.cart.currentItemShipping);
    const [showSaturdayDelivery, setShowSaturdayDelivery] = useState<boolean>(true);

    const cartSubtotal = Number(config.cart?.subTotal || 0) + Number(cartStage?.subTotalAmount || 0) - Number(config.cart?.currentItemSubtotal || 0);

    const memoizedShippingDates = useMemo(() => {
        const shippingOptions = getShippingFromShippingChart({ config, cartStage });
        return shippingOptions;
    }, [config, cartStage]);

    useEffect(() => {
        const saturdayEligible = checkSaturdayDeliveryEligibility();
        setShowSaturdayDelivery(saturdayEligible);

        const dates = Object.values(memoizedShippingDates).filter((d: any) => !d.isSaturday);
        setShippingDates(dates);
        if (dates.length === 0) return;

        const firstDay = dates[0];
        const lastDay = dates[dates.length - 1];

        const selectedMatch = dates.find((d: any) => lastSelectedShippingDay?.day === d.day);
        const shouldApplySelected = selectedMatch && !isNull(lastSelectedShippingDay);

        const dayToSelect = shouldApplySelected ? lastSelectedShippingDay : lastDay || firstDay;

        dispatch(
            actions.cartStage.updateShipping(dayToSelect)
        );
    }, [memoizedShippingDates, cartStage.totalQuantity, cartStage.subTotalAmount, showSaturdayDelivery]);

    const handleShippingChange = (day: any) => {
        setLastSelectedShippingDay(day);
        dispatch(actions.cartStage.updateShipping(day));
    };

    return (
        <StepCard title={isRequestPickup ? "Choose Pickup Date" : "Choose Delivery Date"} stepNumber={stepNumber}>
            <StyledRadioGroup
                className="ant-row"
                value={cartStage.deliveryDate?.date}
                onChange={(event: RadioChangeEvent | any) => handleShippingChange(JSON.parse(event.target['data-day']))}
            >
                {cartStage.totalQuantity <= 0 && (
                    <Col span={24}>
                        <AlertMessage>Please add 1 or more quantity to see delivery dates</AlertMessage>
                    </Col>
                )}

                {cartStage.totalQuantity > 0 && Object.entries(shippingDates).map(([key, day]: [string, any]) => {

                    // Skip days based on subtotal and Saturday delivery logic
                    if (cartSubtotal < 50 && day.free) return null;
                    if (!showSaturdayDelivery && day.isSaturday) return null;
                    if (day.discount > 0) return null;

                    const shippingRate = getShippingRateByDayNumber(day.day, shippingDates, { config, cartStage });

                    return (
                        <Col
                            key={key}
                            xs={8}
                            md={6}
                            lg={4}
                        >
                            <StyledRadioButton value={day.date} data-day={JSON.stringify(day)}>
                                <StyledCard>
                                    <Date>{dayjs(day.date).format('DD')}</Date>
                                    <MonthYear>
                                        {isMobile ? dayjs(day.date).format("MMM, YYYY") : dayjs(day.date).format("MMMM, YYYY")}
                                    </MonthYear>
                                    <DeliveryCost>
                                        {shippingRate === 0 && day.discount === 0 && <span className="free-shipping">Free</span>}
                                        {shippingRate > 0 && !day.free && (
                                            <NumericFormat
                                                displayType="text"
                                                prefix="+$"
                                                suffix="/pc"
                                                decimalScale={2}
                                                value={shippingRate / (cartStage.totalQuantity || 1)}
                                                fixedDecimalScale
                                            />
                                        )}
                                        {day.free && day.discount > 0 && (
                                            <span className="free-shipping discount-shipping">
                                                Free +{day.discount}% OFF
                                                <StyledPopover
                                                    placement="left"
                                                    content={
                                                        <PopoverContent>
                                                            <p className="text-start mb-0">
                                                                <b>Free +{day.discount}% OFF:</b>
                                                                <br />
                                                                Choose this delivery date to receive {day.discount}% off your order,
                                                                <br />
                                                                up to ${SHIPPING_MAX_DISCOUNT_AMOUNT} in savings!
                                                            </p>
                                                        </PopoverContent>
                                                    }
                                                >
                                                    <Button shape="circle" icon={<QuestionCircleOutlined />} />
                                                </StyledPopover>
                                            </span>
                                        )}
                                    </DeliveryCost>
                                </StyledCard>
                            </StyledRadioButton>
                        </Col>
                    );
                })}
            </StyledRadioGroup>
            <ChooseDeliveryMethod />
        </StepCard>
    );
};

export default memo(ChooseDeliveryDate);
