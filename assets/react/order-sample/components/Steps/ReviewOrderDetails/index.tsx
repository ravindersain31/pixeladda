import React, { memo, useState } from "react";
import { NumericFormat } from "react-number-format";
import { shallowEqual } from "react-redux";
import dayjs from "dayjs";
import { isNull } from "lodash";

// internal imports
import { StepProps } from "@orderSample/utils/interface";
import StepCard from "@orderSample/components/Cards/StepCard";
import { useAppSelector } from "@orderSample/hook";
import { postDataToCart } from "@orderSample/components/Steps/ReviewOrderDetails/postDataToCart";
import AddToCart from "./AddToCart";
import { AddToCartContainer, PopoverContent, StyledCard, StyledCollapse, StyledPopover, TableContainer, TotalAmountContainer } from "./styled";
import { DeliveryMethod } from "@orderSample/redux/reducer/cart/interface";
import { message } from "antd";
import { MAX_ALLOWED_QUANTITY } from "@react/order-sample/utils/constant";

const ReviewOrderDetails = ({stepNumber = 6}: StepProps) => {

    const {config, cartStage} = useAppSelector((state) => ({
        config: state.config,
        cartStage: state.cartStage
    }), shallowEqual);

    const links = config.links;

    const [isAddingToCart, setIsAddingToCart] = useState<boolean>(false);
    const isRequestPickup = cartStage.deliveryMethod.key === DeliveryMethod.REQUEST_PICKUP;
    const itemsTotalAmount = cartStage.subTotalAmount;
    const totalQuantity = cartStage.totalQuantity;

    const totalAmount = (((cartStage.subTotalAmount || 0) + (cartStage.totalShipping || 0)) - (cartStage.totalShippingDiscount || 0)).toFixed(2);

    const urlParams = new URLSearchParams(window.location.search);
    const cartIdFromUrl = urlParams.get('cartId') ?? null;

    const onAddToCart = async (data: any) => {
        setIsAddingToCart(true);

        const preCart: any = JSON.parse(JSON.stringify(cartStage));

        preCart.productType = config.product.productType;
        preCart.isNewItem = isNull(cartIdFromUrl) ? true : false;

        const hasQtyOverLimit = Object.values(preCart.items).some(
            (item: any) => Number(item.quantity) > MAX_ALLOWED_QUANTITY
        );

        if (hasQtyOverLimit) {
            message.error(`Quantity cannot be more than ${MAX_ALLOWED_QUANTITY}`);

            const chooseYourSizesSection = document.getElementById("choose-your-sizes");
            if (chooseYourSizesSection) {
                chooseYourSizesSection.scrollIntoView({ behavior: "smooth", block: "start" });
            }
            
            setIsAddingToCart(false);
            return;
        }

        const isSamplesExceedsQuantity = config?.cart?.quantityBySizes['SAMPLE'] || 0;

        if (Number(cartStage.totalQuantity) + Number(isSamplesExceedsQuantity) > 20 || isSamplesExceedsQuantity > 20) {
            message.error("Maximum order quantity of 20 samples per order. Please reduce your total quantity below 20.");
            setIsAddingToCart(false);
            return;
        }

        await postDataToCart(preCart, links.add_to_cart);
        setIsAddingToCart(false);
    }

    return (
        <>
            <StepCard title="Review Order Details" stepNumber={stepNumber}>
                <StyledCard>
                    <TotalAmountContainer>
                        <h2>Total Amount</h2>
                        <h3>
                            <NumericFormat
                                value={cartStage.totalQuantity > 0 ? totalAmount : 0}
                                prefix={'$'}
                                displayType="text"
                                decimalScale={2}
                                fixedDecimalScale
                            />
                        </h3>
                    </TotalAmountContainer>
                    {cartStage.totalQuantity > 0 && <TableContainer>
                        <table className="table mb-0">
                            <thead>
                            <tr className="desktop-only">
                                <th>Review Order Details</th>
                                <th>
                                    <NumericFormat
                                        value={(itemsTotalAmount / totalQuantity) || 0}
                                        prefix={'$'}
                                        suffix={'/Sample'}
                                        displayType="text"
                                        decimalScale={2}
                                        fixedDecimalScale
                                    />
                                </th>
                                <th>{totalQuantity} {'Samples'}</th>
                                <th className="text-end">
                                    <NumericFormat
                                        value={itemsTotalAmount}
                                        prefix={'$'}
                                        displayType="text"
                                        decimalScale={2}
                                        fixedDecimalScale
                                    />
                                </th>
                            </tr>
                            <tr className="mobile-only">
                                <th colSpan={2}>
                                    <span>Order Samples</span>
                                    <small>
                                        <NumericFormat
                                            value={(itemsTotalAmount / totalQuantity) || 0}
                                            prefix={'$'}
                                            suffix={'/Samples'}
                                            displayType="text"
                                            decimalScale={2}
                                            fixedDecimalScale
                                        />
                                    </small>
                                </th>
                                <th className="text-end" colSpan={2}>
                                    <NumericFormat
                                        value={itemsTotalAmount}
                                        prefix={'$'}
                                        displayType="text"
                                        decimalScale={2}
                                        fixedDecimalScale
                                    />
                                    <small>{totalQuantity} Samples</small>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            {Object.keys(cartStage.items).length > 0 && <tr>
                                <td colSpan={4}>Samples Cost Breakdown</td>
                            </tr>}
                            <tr>
                                <td colSpan={4} style={{padding: 0}}>
                                    {cartStage.totalQuantity > 0 && <StyledCollapse
                                        bordered={false}
                                        expandIconPosition="start"
                                        items={Object.keys(cartStage.items).filter((pid) => {
                                            const item = cartStage.items[pid];
                                            return item.quantity > 0;
                                        }).map((productId) => {
                                            const item = cartStage.items[productId];
                                            return {
                                                key: `breakdown_for_${productId}`,
                                                label: <NumericFormat
                                                    value={item.price}
                                                    prefix={`Size ${item.isCustomSize ? `CUSTOM-SIZE (${item.name})` : `(${item.name})`}: $`}
                                                    suffix={` | QTY: ${item.quantity}`}
                                                    displayType="text"
                                                    decimalScale={2}
                                                    fixedDecimalScale
                                                />,
                                                extra: <NumericFormat
                                                    value={item.totalAmount}
                                                    prefix={'$'}
                                                    displayType="text"
                                                    decimalScale={2}
                                                    fixedDecimalScale
                                                />,
                                                children: <table className="table m-0">
                                                    <tbody>
                                                    <tr>
                                                        <td className="bg-white text-muted small">Base Price</td>
                                                        <td className="bg-white text-muted small text-end">
                                                            <NumericFormat
                                                                value={item.price}
                                                                prefix={'$'}
                                                                displayType="text"
                                                                decimalScale={2}
                                                                fixedDecimalScale
                                                            />
                                                        </td>
                                                    </tr>
                                                    {
                                                        Object.entries(item.addons).map(([addonName, addon]: [string, any]) => {
                                                            if (addon.amount <= 0) {
                                                                return null;
                                                            }
                                                            return (
                                                                <tr key={`addon_${addon.key}`}>
                                                                    <td className="bg-white text-muted small">
                                                                        {addon.label}
                                                                    </td>
                                                                    <td className="bg-white text-muted small text-end">
                                                                        <NumericFormat
                                                                            value={addon.unitAmount}
                                                                            prefix={"$"}
                                                                            displayType="text"
                                                                            decimalScale={2}
                                                                            fixedDecimalScale
                                                                        />
                                                                    </td>
                                                                </tr>
                                                            );
                                                        })
                                                    }
                                                    </tbody>
                                                </table>,
                                            }
                                        })}
                                    />}
                                </td>
                            </tr>
                            <tr>
                                <td colSpan={3}>
                                    Shipping Cost {cartStage.shipping.date &&
                                    <span className="small text-muted">(
                                        {isRequestPickup ? 'Pickup' : 'Delivery'} Date: {dayjs(cartStage.shipping.date).format('MMM DD, YYYY')}
                                    )</span>}
                                </td>
                                <td className="text-end">
                                    {cartStage.totalShipping <= 0 && cartStage.totalShippingDiscount <= 0 && <span className="text-success fw-bold">FREE</span>}
                                    {cartStage.totalShipping <= 0 && cartStage.totalShippingDiscount > 0 && <span className="text-success fw-bold">FREE -${cartStage.totalShippingDiscount.toFixed(2)} OFF</span>}
                                    {cartStage.totalShipping > 0 && <NumericFormat
                                        value={cartStage.totalShipping}
                                        prefix={'$'}
                                        displayType="text"
                                        decimalScale={2}
                                        fixedDecimalScale
                                    />}
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </TableContainer>}
                    <AddToCartContainer>
                        <AddToCart
                            onAddToCart={onAddToCart}
                            isAddingToCart={isAddingToCart}
                        />
                    </AddToCartContainer>
                </StyledCard>
            </StepCard>
        </>
    );
};

export default memo(ReviewOrderDetails);