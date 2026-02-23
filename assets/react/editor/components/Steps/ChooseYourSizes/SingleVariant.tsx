import {
    StyledCard,
    VariantImage,
    VariantName,
    InputQuantity,
    Checkmark,
    Editmark,
    StyledBadgeRibbon,
    StyledBadgeEdit,
} from './styled';
import {isMobile} from "react-device-detect";
import {CheckOutlined, EditOutlined} from "@ant-design/icons";
import {useAppSelector} from "@react/editor/hook.ts";
import { useEffect } from 'react';
import { useDispatch } from 'react-redux';
import actions from "@react/editor/redux/actions";
import { Frame, ImprintColor, Shape } from '@react/editor/redux/interface';
import { calculateCartTotalFrameQuantity } from '@react/editor/helper/quantity';
import { handleNumericKeyDown } from '@react/editor/helper/editor';

interface Props {
    title: string;
    label: string;
    image: string;
    value: number | null;
    onChange?: (value: number) => void;
    active?: boolean;
    isEdit?: boolean;
    ribbonText?: string[];
    ribbonColor?: string[];
    productId: number;
}

export const MAX_ALLOWED_QUANTITY = 100000;

const SingleVariant = (
    {
        title,
        label,
        image,
        value,
        onChange,
        active = false,
        isEdit = false,
        ribbonText,
        ribbonColor,
        productId,
    }: Props
) => {
    const canvas = useAppSelector(state => state.canvas);
    const config = useAppSelector((state) => state.config);
    const editor = useAppSelector((state) => state.editor);
    const dispatch = useDispatch();
    const productMetaData = config.product.productMetaData;
    const hasRibbonColor: boolean = !active && Boolean(ribbonColor?.length);
    const hasBackgroundColor: boolean = Boolean(ribbonColor?.length);
    const { isHandFans } = config.product;

    useEffect(() => {
        const { isYardLetters, isDieCut, isBigHeadCutouts, isHandFans } = config.product;

        if (isYardLetters || isDieCut || isBigHeadCutouts || isHandFans) {
            dispatch(actions.editor.updateShape(Shape.CUSTOM));
        }

        if (isYardLetters) {
            dispatch(actions.editor.updateImprintColor(ImprintColor.UNLIMITED));
        }

        if (isYardLetters) {
            const frameTypes: {[key: string]: number} | null = productMetaData.frameTypes;
            if(frameTypes) {
                const filteredFrameTypes: Frame[] = Object.keys(frameTypes).filter(frameType => frameTypes[frameType] !== 0 && frameTypes[frameType] !== null).map(frameType => Frame[frameType as keyof typeof Frame]);
                dispatch(actions.editor.updateFrame(filteredFrameTypes));
            } else {
                dispatch(actions.editor.updateFrame([Frame.WIRE_STAKE_10X30]));
            }
            calculateFramePrice();
        }
        dispatch(actions.editor.updatePrePackedDiscount());
    }, [config.product]);

    const calculateFramePrice = () => {
        const totalQuantityWithFrames = calculateCartTotalFrameQuantity() ?? 1;
        dispatch(actions.editor.updateFramePrice(totalQuantityWithFrames));
    }

    useEffect(() => {
        const { isYardLetters, isDieCut, isBigHeadCutouts } = config.product;

        if (isYardLetters || isDieCut || isBigHeadCutouts) {
            calculateFramePrice();
        }
    }, [editor.totalQuantity]); 

    return (
        <StyledCard className={`${active ? `active` : ''} ${isMobile ? 'mobile-device' : ''}`} $hasBackgroundColor={hasBackgroundColor} $hasRibbonColor={hasRibbonColor} $isHandFans={isHandFans}>
                {ribbonText && ribbonText.map((ribbon : string, index: number) => (
                    <StyledBadgeRibbon key={index} text={ribbon} color={ribbonColor && ribbonColor[index]} style={{ top: `${8 + index * 20}px` }}/>
                ))}
                <Checkmark className="checkmark">
                    <CheckOutlined style={{color: "#FFF"}}/>
                </Checkmark>
                {isEdit && ribbonText && (
                    <StyledBadgeEdit title={ribbonText[0]} style={{ top: `${3 + ribbonText.length * 15}px` }} text={<EditOutlined style={{ color: "#FFF" }}/>}/>
                )}

                <VariantImage className={isMobile ? 'mobile-device' : ''}>
                    <img src={image} alt={title}/>
                </VariantImage>
                <VariantName className={isMobile ? 'mobile-device' : ''} $textBold={hasBackgroundColor} $isHandFans={isHandFans}>{label || title}</VariantName>
                <InputQuantity
                    disabled={canvas.loading && canvas.item.productId !== productId}
                    type="text"
                    inputMode="numeric"
                    placeholder={'Enter Qty'}
                    min={0}
                    max={MAX_ALLOWED_QUANTITY}
                    maxLength={MAX_ALLOWED_QUANTITY.toString().length}
                    precision={0}
                    parser={(value: any) => parseInt(value).toFixed(0)}
                    value={value}
                    onChange={(value: any) => onChange && onChange(value)}
                    onKeyUp={(e: any) => {
                        if (['Backspace', 'Delete'].includes(e.key) && onChange) {
                            if (e.target.value.length <= 0) {
                                onChange(0);
                            }
                        }
                    }}
                    onKeyDown={handleNumericKeyDown()}
                    changeOnWheel={false}
                />
            </StyledCard>
    )
}

export default SingleVariant;