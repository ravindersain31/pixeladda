import React, { useContext, useEffect } from 'react';
import { YSPLogoButton, PopoverContent, StyledPopover } from './styled';
import CanvasContext from '@react/editor/context/canvas';
import fabric from '@react/editor/canvas/fabric.ts';
import { useAppDispatch, useAppSelector } from '@react/editor/hook';
import actions from '@react/editor/redux/actions';
import { CanvasProperties } from '@react/editor/canvas/utils';
import { isMobile } from 'react-device-detect';
import { YSP_LOGO_DISCOUNT, YSP_MAX_DISCOUNT_AMOUNT } from '@react/editor/redux/reducer/editor/interface';
import { QuestionCircleOutlined } from "@ant-design/icons";
import { Button } from 'antd';
import { getStoreInfo, isPromoStore } from '@react/editor/helper/editor';
const storeEmail = getStoreInfo().storeEmail;
const logoTitle = getStoreInfo().logoTitle;
const storeName = getStoreInfo().storeName;

const YSPLogo = () => {
    const canvasContext = useContext(CanvasContext);
    const dispatch = useAppDispatch();
    const editor = useAppSelector(state => state.editor);
    const canvas = useAppSelector(state => state.canvas);

    const addYSPLogo = () => {
        if (!canvasContext.canvas) {
            return;
        }
        const logo = isPromoStore() ? "https://static.yardsignplus.com/storage/promo-store/Promo-Logo-Icon.png" : "https://static.yardsignplus.com/storage/icons/logo.png"
        fabric.Image.fromURL(logo, (img) => {
            if (!img) {
                console.error('Failed to load YSP Logo image.');
                return;
            }
            img.scaleToWidth(70);

            img.set({
                custom: {
                    id: 'ysp-logo',
                },
                left: canvasContext.canvas.getCenter().left - (img.width! * img.scaleX!) / 2,
                top: canvasContext.canvas.getCenter().top - (img.height! * img.scaleY!) / 2,
            });

            canvasContext.canvas.add(img);
            canvasContext.canvas.setActiveObject(img);
            canvasContext.canvas.requestRenderAll();
            dispatch(actions.canvas.updateCanvasData(canvasContext.canvas.toJSON(CanvasProperties)));
            dispatch(actions.editor.updateYSPLogoDiscount());
        });
    };

    const handleObjectRemoved = (event: fabric.IEvent) => {
        const removedObject = event.target;
        if (removedObject?.custom?.id === 'ysp-logo') {
            dispatch(actions.canvas.updateCanvasData(canvasContext.canvas.toJSON(CanvasProperties)));
            dispatch(actions.editor.updateYSPLogoDiscount());
        }
    };

    useEffect(() => {
        if (canvasContext.canvas instanceof fabric.Canvas) {
            canvasContext.canvas.on('object:removed', handleObjectRemoved);
            return () => {
                canvasContext.canvas.off('object:removed', handleObjectRemoved);
            };
        }
    }, [canvas]);

    const handlePopoverButtonClick = (event: React.MouseEvent) => {
        event.stopPropagation();
    };

    const LiveChat = (event: React.MouseEvent) => {
        //@ts-ignore
        Tawk_API.toggle();
    };


    return (
        <>
            <YSPLogoButton type="primary" onClick={addYSPLogo}>Add {logoTitle} ({YSP_LOGO_DISCOUNT}% Off)
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
            </YSPLogoButton>
        </>
    );
};

export default YSPLogo;
