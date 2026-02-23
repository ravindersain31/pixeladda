import StepCard from '@react/editor/components/Cards/StepCard';
import AddToCart from './AddToCart';
import {
    StyledCard,
    TotalAmountContainer,
    TableContainer,
    AddToCartContainer,
    StyledCollapse,
    StyledPopover,
    PopoverContent,
    HelpButton,
} from './styled.tsx';
import {NumericFormat} from "react-number-format";
import {useAppSelector} from "@react/editor/hook.ts";
import SaveYourDesign from './SaveYourDesign';
import {StepProps} from "../interface.ts";
import {useContext, useMemo, useState} from "react";
import CanvasContext from "@react/editor/context/canvas.ts";
import dayjs from "dayjs";
import { CanvasProperties } from '@react/editor/canvas/utils.ts';
import {isProductEditable} from "@react/editor/helper/template.ts";
import { isNull } from 'lodash';
import { postDataToCart } from './postDataToCart.ts';
import { calculateYSPLogoDiscountFromItems, getPrePackedDiscount, hasSubAddons } from '@react/editor/helper/pricing.ts';
import SubAddonCollapse from './SubAddonCollapse';
import useNavigationGuard from '@react/editor/hooks/useNavigationGuard.tsx';
import { DeliveryMethod } from '@react/editor/redux/reducer/editor/interface.ts';
import React from 'react';
import { QuestionCircleOutlined } from '@ant-design/icons';
import ShapeHelpText from '../../common/ShapeHelpText/index.tsx';
import Subscribe from './Subscribe/index.tsx';
import { getStoreInfo } from '@react/editor/helper/editor.ts';

const logoTitle = getStoreInfo().logoTitle;

const ReviewOrderDetails = ({stepNumber}: StepProps) => {
    const editor = useAppSelector(state => state.editor);
    const canvasContext = useContext(CanvasContext);
    const config = useAppSelector(state => state.config);
    const links = config.links;
    const canvas = useAppSelector(state => state.canvas);

    const [isAddingToCart, setIsAddingToCart] = useState(false);
    const { isModalVisible, confirmLeave, cancelLeave } = useNavigationGuard(!isAddingToCart);
    const itemsTotalAmount = editor.totalAmount - editor.totalShipping;
    const wireStakeQuantity = useMemo(() => {
        return Object.values(editor.items)
            .filter(item => item.isWireStake)
            .reduce((sum, item) => sum + (item.quantity || 0), 0);
    }, [editor.items]);
    const totalQuantity = editor.totalQuantity;
    const totalQuantitySigns = totalQuantity - wireStakeQuantity;

    const hasWireStakeItem = useMemo(() => {
        return Object.values(editor.items)
            .some(item => item.isWireStake);
    }, [editor.items]);
    const hasOnlyWireStakeItem = useMemo(() => {
        const items = Object.values(editor.items);

        const hasWireStakeWithQuantity = items.some(item => item.isWireStake && item.quantity > 0);
        const hasNonWireStakeWithQuantity = items.some(item => !item.isWireStake && item.quantity > 0);

        return hasWireStakeWithQuantity && !hasNonWireStakeWithQuantity;
    }, [editor.items]);

    const urlParams = new URLSearchParams(window.location.search);
    const cartIdFromUrl = urlParams.get('cartId') ?? null;
    const isRequestPickup = editor.deliveryMethod.key === DeliveryMethod.REQUEST_PICKUP;
    const { YSPLogoDiscount, hasYspLogo } = calculateYSPLogoDiscountFromItems(Object.values(editor.items));
    const { prePackedTotalDiscount, prePackedDiscountPercent } = getPrePackedDiscount(editor.items);
    const { isYardLetters } = config.product;

    const onAddToCart = async (data: any) => {
        setIsAddingToCart(true);
        const editorData: any = JSON.parse(JSON.stringify(editor));
        const canvasData: any = JSON.parse(JSON.stringify(canvas.data));
        if (isProductEditable(config)) {
            canvasData[canvas.view] = canvasContext.canvas.toJSON(CanvasProperties);
        }
        editorData.items[canvas.item.id].canvasData = canvasData;
        editorData.additionalData = data;
        editorData.productType = config.product.productType;
        editorData.isNewItem = isNull(cartIdFromUrl) ? true : false;
        await postDataToCart(editorData, links.add_to_cart);
        setIsAddingToCart(false);
    }

    const ImprintHelpText = ({ type }: { type: string }) => {
        switch (type) {
            case "TWO":
                return (
                    <p className="text-start mb-0">
                        <b>2 Imprint Colors: </b>2 Imprint Colors offers you to choose <br />
                        two colors for your text, numbers, and / or artwork. This <br />
                        choice is best for customizations requiring only two<br /> colors.
                        White is included (free).
                    </p>
                );
            case "THREE":
                return (
                    <p className="text-start mb-0">
                        <b>3 Imprint Colors: </b> 3 Imprint Colors offers you to choose<br />
                        three colors for your text, numbers, and / or artwork. This<br />
                        choice is best for customizations requiring only three<br /> colors.
                        White is included (free).
                    </p>
                );
            case "UNLIMITED":
                return (
                    <p className="text-start mb-0">
                        <b>Unlimited Imprint Colors:</b> <br />Unlimited Imprint Colors offer you
                        to choose four or<br /> more colors for your text, numbers, and / or 
                        artwork.<br /> This choice is best for customizations requiring<br /> various colors.
                        White is included (free).
                    </p>
                );
            default:
                return null;
        }
    };

    return <StepCard title="Review Order Details" stepNumber={stepNumber}>
        <StyledCard>
            <TotalAmountContainer>
                <h2>Total Amount</h2>
                <h3>
                    <NumericFormat
                        value={editor.totalAmount - editor.totalShippingDiscount - YSPLogoDiscount - prePackedTotalDiscount}
                        prefix={'$'}
                        displayType="text"
                        decimalScale={2}
                        fixedDecimalScale
                    />
                </h3>
            </TotalAmountContainer>
            {editor.totalQuantity > 0 && <TableContainer>
                <table className="table mb-0">
                    <thead>
                    <tr className="desktop-only">
                        <th>{isYardLetters ? 'Custom Yard Sign Packs' : 'Custom Yard Signs'}</th>
                        <th>
                            <NumericFormat
                                value={(itemsTotalAmount / totalQuantity) || 0}
                                prefix={'$'}
                                suffix={isYardLetters ? '/Pack' : hasOnlyWireStakeItem ? '/Stake' : hasWireStakeItem ? '/Sign + Stake' : '/Yard Sign'}
                                displayType="text"
                                decimalScale={2}
                                fixedDecimalScale
                            />
                        </th>
                        <th>{hasOnlyWireStakeItem ? wireStakeQuantity : totalQuantitySigns} {isYardLetters ? (totalQuantity > 1 ? 'Packs' : 'Pack') : hasOnlyWireStakeItem ? 'Stakes' : 'Yard Signs'}</th>
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
                            <span>{isYardLetters ? 'Custom Yard Sign Packs' : 'Yard Signs'}</span>
                            <small>
                                <NumericFormat
                                    value={(itemsTotalAmount / totalQuantity) || 0}
                                    prefix={'$'}
                                    suffix={isYardLetters ? '/Pack' : '/Yard Sign'}
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
                            <small>{totalQuantity} {isYardLetters ? (totalQuantity > 1 ? 'Packs' : 'Pack') : 'Yard Signs'}</small>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    {Object.keys(editor.items).length > 0 && <tr>
                        <td colSpan={4}>{isYardLetters ? 'Custom Yard Sign Packs Cost Breakdown' : 'Custom Yard Signs Cost Breakdown'} </td>
                    </tr>}
                    <tr>
                        <td colSpan={4} style={{padding: 0}}>
                            {editor.totalQuantity > 0 && <StyledCollapse
                                bordered={false}
                                expandIconPosition="start"
                                items={Object.keys(editor.items).filter((pid) => {
                                    const item = editor.items[pid];
                                    return item.quantity > 0;
                                }).map((productId) => {
                                    const item = editor.items[productId];

                                    const sortedAddons = Object.entries(item.addons).sort(([aName], [bName]) => {
                                        if (!isYardLetters) return 0;

                                        if (aName === 'frame') return 1;
                                        if (bName === 'frame') return -1;
                                        return 0;
                                    });

                                    let label = `Size ${item.isCustomSize ? `CUSTOM-SIZE (${item.name})` : `(${item.name})`}: $`;
                                    if (item.isWireStake) {
                                        label = `Frame (${item.label}): $`;
                                    }

                                    return {
                                        key: `breakdown_for_${productId}`,
                                        label: <NumericFormat
                                            value={item.price}
                                            prefix={label}
                                            suffix={` | Qty: ${item.quantity} ${ isYardLetters ? (item.quantity > 1 ? 'Packs' : 'Pack') : ''}`}
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
                                                        value={config.product.productType.slug === 'yard-letters'? item.price * (config.product.productMetaData.totalSigns ?? 1) : item.price}
                                                        prefix={'$'}
                                                        displayType="text"
                                                        decimalScale={2}
                                                        fixedDecimalScale
                                                    />
                                                </td>
                                                </tr>
                                                {
                                                sortedAddons.map(([addonName, addon]: [string, any]) => {
                                                    if (hasSubAddons(addon)) {
                                                        return <SubAddonCollapse key={`addon_${addon.key}`} addonName={addonName} addon={addon} quantity={item.quantity}/>;
                                                    } else {
                                                        if (addon.amount <= 0) {
                                                            return null;
                                                        }
                                                        return (
                                                            <tr key={`addon_${addon.key}`}>
                                                                <td className="bg-white text-muted small">
                                                                    {isYardLetters ? addon.displayText : addon.label}
                                                                    {(addonName === 'imprintColor' || (isYardLetters && addonName === 'shape')) && (
                                                                        <StyledPopover
                                                                            trigger="hover"
                                                                            content={
                                                                                <PopoverContent>
                                                                                    {addonName === 'imprintColor' ? (
                                                                                        <ImprintHelpText type={addon.key} />
                                                                                    ) : (
                                                                                        <ShapeHelpText addon={addon} />
                                                                                    )}
                                                                                </PopoverContent>
                                                                            }
                                                                        >
                                                                            <HelpButton icon={<QuestionCircleOutlined style={{ fontSize: '14px' }} />} />
                                                                        </StyledPopover>
                                                                    )}
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
                                                    }
                                                })
                                            }
                                            </tbody>
                                        </table>,
                                    }
                                })}
                            />}
                        </td>
                    </tr>
                    {Object.keys(editor.items).length > 0 && hasYspLogo && YSPLogoDiscount > 0 && (
                        <tr>
                            <td colSpan={3}>{logoTitle} (Discount)</td>
                            <td className="text-end">-${YSPLogoDiscount.toFixed(2)}</td>
                        </tr>
                    )}
                    {prePackedTotalDiscount > 0 && (
                        <tr>
                            <td colSpan={3}>
                                Yard Letters Discount{prePackedDiscountPercent ? ` (${prePackedDiscountPercent}%)` : ''}
                            </td>
                            <td className="text-end">
                                -${prePackedTotalDiscount.toFixed(2)}
                            </td>
                        </tr>
                    )}
                    <tr>
                        <td colSpan={3}>
                            Shipping Cost {editor.shipping.date &&
                            <span className="small text-muted">(
                                {isRequestPickup ? 'Pickup' : 'Delivery'} Date: {dayjs(editor.shipping.date).format('MMM DD, YYYY')}
                            )</span>}
                        </td>
                        <td className="text-end">
                            {editor.totalShipping <= 0 && editor.totalShippingDiscount <= 0 && <span className="text-success fw-bold">FREE</span>}
                            {editor.totalShipping <= 0 && editor.totalShippingDiscount > 0 && <span className="text-success fw-bold">FREE -${editor.totalShippingDiscount.toFixed(2)} OFF</span>}
                            {editor.totalShipping > 0 && <NumericFormat
                                value={editor.totalShipping}
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
            <Subscribe/>
            <AddToCartContainer>
                <AddToCart
                    onAddToCart={onAddToCart}
                    isAddingToCart={isAddingToCart}
                />
            </AddToCartContainer>
        </StyledCard>
        <SaveYourDesign
            onAddToCart={onAddToCart}
            isAddingToCart={isAddingToCart}
        />
    </StepCard>
}

export default ReviewOrderDetails;