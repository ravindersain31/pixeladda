import React, {useContext, useEffect, useState} from "react";
import {useAppDispatch, useAppSelector} from "@react/editor/hook.ts";
import {Alert} from 'antd';
import StepCard from '@react/editor/components/Cards/StepCard';
import actions from "@react/editor/redux/actions";
import {StepProps} from "../interface.ts";
import {recalculateOnUpdateQuantity} from "@react/editor/helper/quantity.ts";
import EnterQuantity from "./EnterQuantity";
import ByDefaultSizes from "./ByDefaultSizes";
import {isProductEditable, updateEditorHeading} from "@react/editor/helper/template.ts";
import { CanvasProperties } from "@react/editor/canvas/utils.ts";
import CanvasContext from "@react/editor/context/canvas.ts";
import {isMobile} from "react-device-detect";
import { getRoles, toggleFreeFreightBasedOnItems } from "@react/editor/helper/editor.ts";
import { UserLogin, LoginButton } from "./styled.tsx";


const isPromoDomain = window.location.href.includes("yardsignpromo");
const roles = getRoles();
const isWholeSeller = roles.includes("ROLE_WHOLE_SELLER");

const ChooseYourSizes = ({stepNumber = 1}: StepProps) => {
    const initialData = useAppSelector((state) => state.config.initialData);
    const canvas = useAppSelector((state) => state.canvas);
    const product = useAppSelector((state) => state.config.product);
    const editor = useAppSelector((state) => state.editor);
    const config = useAppSelector((state) => state.config);
    const dispatch = useAppDispatch();
    const canvasContext = useContext(CanvasContext);

    const [showCustomVariant, setShowCustomVariant] = useState<boolean>(false);
    const [showAllSizes, setShowAllSizes] = useState<boolean>(false);
    const { isHandFans } = product;

    const handleToggleCustomVariant = () => {
        setShowCustomVariant(!showCustomVariant);
    };

    const handleToggleViewMore = () => {
        setShowAllSizes(!showAllSizes);
    };

    const customSizesButton = document.getElementById('custom-sizes-btn') as HTMLButtonElement;
    if (customSizesButton) {
        customSizesButton.addEventListener('click', () => {
            setShowCustomVariant(true);
            const qtyInput = document.querySelector('.custom-variant-qty') as HTMLInputElement;
            qtyInput.focus();
            qtyInput.scrollIntoView({behavior: "smooth", block: "center"});
        });
    }

    const maxVisibleSizes = isMobile ? 3 : 4;
    const hasMoreThanMaxVariants = product.variants.some((item: any, index: number) => {
        const itemData = editor.items[item.id];
        return itemData && itemData.productId === item.productId && index > maxVisibleSizes;
    });

    const handleQuantityChange = (quantity: number, _item: any) => {
        if (!Number(quantity) && quantity !== 0) {
            return;
        }
        const item = JSON.parse(JSON.stringify(_item));
        item.templateSize = {
            width: Number(item.name.split('x')[0]),
            height: Number(item.name.split('x')[1]),
        };

        const data = recalculateOnUpdateQuantity(item, quantity);

        dispatch(actions.editor.updateQty(data));

        dispatch(actions.editor.refreshShipping());

        const currentVariantName = `${canvas.templateSize.width}x${canvas.templateSize.height}`;
        if (item.name.toString() !== currentVariantName.toString()) {
            const editorItem = (Object.values(data.items).find((it) => it.productId === _item.productId)) ?? _item;
            if (isProductEditable(config)) {
                // save the canvas data before changing the variant name
                dispatch(actions.canvas.updateCanvasData(canvasContext.canvas.toJSON(CanvasProperties)));
            }
            dispatch(actions.canvas.updateVariant(editorItem));
            updateEditorHeading(editorItem);
        }

        // set the first item from an item list on canvas if selected variant quantity set to zero
        if (quantity <= 0) {
            if (config.product.isCustom) {
                dispatch(actions.canvas.updateCanvasData(canvasContext.canvas.toJSON(CanvasProperties)));
            }
            const autoPickedItem = Object.values(data.items).find((it) => it.quantity > 0 && it.id !== item.id);
            if (autoPickedItem) {
                dispatch(actions.canvas.updateVariant(autoPickedItem));
                dispatch(actions.canvas.updateCustomVariant(autoPickedItem));
                updateEditorHeading(autoPickedItem);
                if (config.product.isCustom) {
                    dispatch(actions.canvas.updateCanvasData(canvasContext.canvas.toJSON(CanvasProperties)));
                }
            }
        }
    }

    useEffect(() => {
        if (editor.totalQuantity <= 0) {
            // set initial quantity if not set
            let variant: any = product.variants.find((item: any) => item.name === initialData.variant);
            if (!variant) {
                variant = product.customVariant[product.customVariant.length - 1];
                if (variant) {
                    variant = JSON.parse(JSON.stringify(variant));
                    variant.name = initialData.variant;
                }
            }
            if (variant.isCustomSize) {
                setShowCustomVariant(true);
            }
            handleQuantityChange(initialData.quantity, variant);
            dispatch(actions.canvas.updateVariant(variant));
        } else {
            if (canvas.item.isCustomSize) {
                setShowCustomVariant(true);
            }
        }
    }, []);

    useEffect(()=>{
        toggleFreeFreightBasedOnItems(editor.items, editor.isFreeFreight, dispatch);
        dispatch(actions.editor.updatePrePackedDiscount());
    },[editor.totalQuantity])

    useEffect(()=>{
        setShowAllSizes(hasMoreThanMaxVariants);
    },[hasMoreThanMaxVariants])

    
    const defaultRibbons = {
        '18x12': ['Best Seller'],
        '24x18': ['Best Seller', 'Standard'],
    }
    
    const handFansribbons = {
        '7x14': ['Best Seller'],
        '8x12': ['Best Seller', 'Standard'],
    }
    
    const ribbons: { [key: string]: string[] } = isHandFans ? handFansribbons : defaultRibbons;

    const defaultSizeRibbonsColors = {
        "18x12": ["#3398d9"],
        "24x18": ["#3398d9", "#66b94d"],
    };
    
    const handFansSizeRibbonsColors = {
        "7x14": ["#3398d9"],
        "8x12": ["#3398d9", "#66b94d"],
    };  

    const sizeRibbonsColor: { [key: string]: string[] } = isHandFans ? handFansSizeRibbonsColors : defaultSizeRibbonsColors;

    const getStepTitle = () => {
        const stepTitleByProductType: any = {
            'default': 'Choose Your Sizes (inches)',
        };
        const currentProductType = config.product.productType.slug ?? 'default';
        return stepTitleByProductType[currentProductType] || stepTitleByProductType.default;
    }

    return (
        <>
            {isPromoDomain && !isWholeSeller && (
                <UserLogin>
                    <LoginButton
                        onClick={() => (window.location.href = "/whole-seller-login")}
                    >
                        WHOLESALE CLIENTS ONLY: <span className="click-to-login"> &nbsp; CLICK TO LOGIN</span>
                    </LoginButton>
                </UserLogin>
            )}
            <StepCard
                id="choose-your-sizes"
                title={getStepTitle()}
                stepNumber={stepNumber}
            >
                {config.product.productType.quantityType === 'BY_QUANTITY' && <EnterQuantity
                    product={product}
                    ribbons={ribbons}
                    sizeRibbonsColor={sizeRibbonsColor}
                    handleQuantityChange={handleQuantityChange}
                />}

                {config.product.productType.quantityType === 'BY_SIZES' && <ByDefaultSizes
                    product={product}
                    ribbons={ribbons}
                    sizeRibbonsColor={sizeRibbonsColor}
                    handleQuantityChange={handleQuantityChange}
                    showCustomVariant={showCustomVariant}
                    handleToggleCustomVariant={handleToggleCustomVariant}
                    showAllSizes={showAllSizes}
                    handleToggleViewMore={handleToggleViewMore}
                />}

                {product.variants.length <= 0 && (
                    <div className="text-center">
                        <Alert
                            type="info"
                            message="This product doesn't have any sizes available. Please contact support."
                        />
                    </div>
                )}
            </StepCard>
        </>
    );
}

export default ChooseYourSizes;