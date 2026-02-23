import React from 'react';
import { useAppDispatch, useAppSelector } from '@react/editor/hook.ts';
import actions from '@react/editor/redux/actions';
import { generateUniqueId } from '@react/editor/helper/template';
import { StyledCheckmark, StyledButton, YSPLogoWrapper, StyledPopover, PopoverContent } from './styled';
import { CheckOutlined, QuestionCircleOutlined } from "@ant-design/icons";
import { isMobile } from 'react-device-detect';
import { Button } from 'antd';
import { CustomArtwork, YSP_LOGO_DISCOUNT, YSP_MAX_DISCOUNT_AMOUNT } from '@react/editor/redux/reducer/editor/interface';
import { getStoreInfo, isPromoStore } from '@react/editor/helper/editor';
import { useCanvasContext } from '@react/editor/context/canvas';
import { CanvasProperties } from '@react/editor/canvas/utils';

const storeEmail = getStoreInfo().storeEmail;
const logoTitle = getStoreInfo().logoTitle;
const storeName = getStoreInfo().storeName;

const YSPLogo = () => {
    const dispatch = useAppDispatch();
    const canvas = useAppSelector(state => state.canvas);
    const editor = useAppSelector(state => state.editor);
    const {canvas: canvasContext} = useCanvasContext();
    const type = CustomArtwork.YSP_LOGO;
    const url = isPromoStore() ? "https://static.yardsignplus.com/storage/promo-store/Promo-Logo-Icon.png" : "https://static.yardsignplus.com/storage/icons/logo.png"
    const side = canvas.view || "front";
    const YspLogo = editor.items?.[canvas.item.id]?.customArtwork?.[type]?.[side]|| [];

    const handleClick = () => {
        const prevData: any = editor.items?.[canvas.item.id]?.customArtwork?.[type]?.[side] || [];
        const existingArtwork = Object.values(prevData).find((item: any) => item.url === url);
        if (existingArtwork) {
            const updatedArtwork = prevData.filter((artwork: any) => artwork.url !== url);
            dispatch(actions.editor.updateCustomArtwork(updatedArtwork, side, type));
            dispatch(actions.canvas.updateCanvasData(canvasContext.toJSON(CanvasProperties)))
            dispatch(actions.editor.updateYSPLogoDiscount());
        } else {
            const newData = [
                ...prevData,
                { id: generateUniqueId(), url: url }
            ];
            dispatch(actions.editor.updateCustomArtwork(newData, side, type));
            dispatch(actions.canvas.updateCanvasData(canvasContext.toJSON(CanvasProperties)))
            dispatch(actions.editor.updateYSPLogoDiscount());
        }
    };

    const handlePopoverButtonClick = (event: React.MouseEvent) => {
        event.stopPropagation();
    };

    const LiveChat = (event: React.MouseEvent) => {
        //@ts-ignore
        Tawk_API.toggle();
    };

    return (
        <YSPLogoWrapper>
            <StyledButton
                onClick={handleClick}
                $disabled={!(YspLogo.length > 0)}
                className={YspLogo.length > 0 ? "active" : ""}
            >
                <StyledCheckmark
                    className={YspLogo ? "checkmark" : ""}
                >
                    <CheckOutlined style={{ color: "#FFF" }} />
                </StyledCheckmark>
                <span>
                    {logoTitle} ({YSP_LOGO_DISCOUNT}% Off)
                    <StyledPopover
                        placement={isMobile ? "bottom" : undefined}
                        content={
                            <PopoverContent onClick={handlePopoverButtonClick}>
                                <>
                                    <p className="text-start mb-0">
                                        <b>{logoTitle} ({YSP_LOGO_DISCOUNT}% Off):</b>
                                        <br />
                                        Add our company logo “{storeName }” anywhere to receive {YSP_LOGO_DISCOUNT}% off the base price of your signs, up to ${YSP_MAX_DISCOUNT_AMOUNT} in savings.
                                        For questions please leave a comment, message us on our<Button size='small' type='link' onClick={LiveChat}>&nbsp;Live Chat</Button>, call
                                        <a href="tel: +1-877-958-1499" className="text-primary">
                                            &nbsp;+1-877-958-1499
                                        </a>, or
                                        email <a className='text-primary' href={`mailto:${storeEmail}`}>{storeEmail}</a>.
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
                </span>
            </StyledButton >
        </YSPLogoWrapper >
    );
};

export default YSPLogo;