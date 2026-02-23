import React, { useContext, useState, useEffect } from "react";
import { Divider, message } from "antd";
import {
    DownloadOutlined,
    CopyOutlined,
    ShareAltOutlined,
    InstagramOutlined,
    XOutlined,
    FacebookFilled
} from "@ant-design/icons";
import { fabric } from "fabric";
import CanvasContext from "@react/editor/context/canvas.ts";
import { CopyButton, DownloadButton, IconButton, Preview, PreviewCanvas, ShareDesignButton, ShareDesignModal, ShareSpan, SocialIcon, StyledWrapper, } from "./styled";
import { useAppSelector } from "@react/editor/hook.ts";
import { CanvasProperties } from '@react/editor/canvas/utils.ts';
import { isProductEditable } from "@react/editor/helper/template.ts";
import { isNull } from 'lodash';
import { postDataToShareCanvas } from "../Steps/ReviewOrderDetails/postDataToShareCanvas";
import { isMobile } from "react-device-detect";

const ShareDesign = () => {
    const [open, setOpen] = useState(false);
    const [canvasImage, setCanvasImage] = useState<string>("");
    const [loader, setLoader] = useState<string | null>(null);
    const editor = useAppSelector(state => state.editor);
    const canvasContext = useContext(CanvasContext);
    const config = useAppSelector(state => state.config);
    const links = config.links;
    const canvas = useAppSelector(state => state.canvas);
    const [isAddingToCart, setIsAddingToCart] = useState(false);
    const urlParams = new URLSearchParams(window.location.search);
    const cartIdFromUrl = urlParams.get('cartId') ?? null;
    const [isCopied, setIsCopied] = useState(false);
    const [shareLink, setShareLink] = useState<string>("");

    useEffect(() => {
        if (open && canvasContext?.canvas instanceof fabric.Canvas) {
            const json = canvasContext.canvas.toJSON();

            const newCanvas = new fabric.Canvas("new-canvas-id", {
                width: canvasContext.canvas.getWidth(),
                height: canvasContext.canvas.getHeight(),
                selection: false,
            });

            newCanvas.loadFromJSON(json, () => {
                newCanvas.renderAll();

                newCanvas.getObjects().forEach((obj) => {
                    obj.selectable = false;
                    obj.evented = false;
                });

                const dataURL = newCanvas.toDataURL({
                    format: "png",
                    quality: 1,
                    multiplier: 2,
                });
                setCanvasImage(dataURL);
            });

            return () => {
                newCanvas.dispose();
            };
        }
    }, [open, canvasContext]);

    const handleDownload = () => {
        if (!canvasImage) return;

        try {
            const link = document.createElement("a");
            link.download = "design.png";
            link.href = canvasImage;
            link.click();
            message.success({
                content: "Your design has downloaded successfully.",
            });
        } catch (err) {
            message.error({
                content: "Failed to download design.",
            });
        }
    };

    const shareCanvas = async (): Promise<string> => {
        setIsAddingToCart(true);
        const editorData: any = JSON.parse(JSON.stringify(editor));
        const canvasData: any = JSON.parse(JSON.stringify(canvas.data));
        if (isProductEditable(config)) {
            canvasData[canvas.view] = canvasContext.canvas.toJSON(CanvasProperties);
        }
        editorData.items[canvas.item.id].canvasData = canvasData;
        editorData.productType = config.product.productType;
        editorData.isNewItem = isNull(cartIdFromUrl);
        const response = await postDataToShareCanvas(editorData, links.share_canvas, canvas.item.id);
        setIsAddingToCart(false);
        return response?.redirectUrl || "";
    };

    const prepareShareLink = async () => {
        const link = await shareCanvas();
        setShareLink(link);
    };

    useEffect(() => {
        if (open) {
            prepareShareLink();
        }
    }, [open]);

    const copyToClipboard = (text: string): boolean => {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                 navigator.clipboard.writeText(text);
                return true;
            } else {
                const textarea = document.createElement("textarea");
                textarea.value = text;
                textarea.style.position = "absolute";
                textarea.style.left = "-9999px";
                textarea.setAttribute("readonly", "");
                document.body.appendChild(textarea);
                textarea.select();
                textarea.setSelectionRange(0, text.length);
                const success = document.execCommand("copy");
                document.body.removeChild(textarea);

                return success;
            }
        } catch (err) {
            return false;
        }
    };

    const handleCopyLink = () => {
        try {
            setIsCopied(true);
            const success = copyToClipboard(shareLink); 

            if (success) {
                message.success("The link has been copied to your clipboard.");
            } else {
                message.error("Failed to copy link");
            }
        } catch (err) {
            message.error("Failed to copy link");
        } finally {
            setTimeout(() => setIsCopied(false), 1500);
        }
    };

    const handlePlatformShare = (platform: "facebook" | "twitter") => {
        const url = encodeURIComponent(window.location.href);
        let shareUrl = "";

        if (platform === "facebook") {
            shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
        } else if (platform === "twitter") {
            shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=Check%20out%20my%20design!`;
        }

        window.open(shareUrl, "_blank", "width=600,height=400");
    };

    const handleShareClick = (platform: "facebook" | "twitter") => {
        setLoader(platform);
        handlePlatformShare(platform);
        setTimeout(() => setLoader(null), 1000);
    };

    const socialButtons = [
        {
            key: "facebook",
            icon: <>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" className="feather feather-facebook"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
            </>,
            type: "share",
        },
        {
            key: "twitter",
            icon: <XOutlined />,
            type: "share",
        },
        {
            key: "instagram",
            icon: <InstagramOutlined />,
            type: "link",
            url: "https://www.instagram.com/yardsign_plus/"
        },
       
    ];

    const handleSocialButtonClick = (
        e: React.MouseEvent<HTMLButtonElement>,
        btn: { key: string; type: string; url?: string }
    ) => {
        e.currentTarget.blur();

        if (btn.type === "share") {
            handleShareClick(btn.key as "facebook" | "twitter");
        } else if (btn.type === "link" && btn.url) {
            window.open(btn.url, "_blank");
        }
    };

    return (
        <>
            <ShareDesignButton
                type="primary"
                icon={<ShareAltOutlined style={{ color: '#0075d5' }} />}
                title={"Share"}
                onClick={(e: React.MouseEvent<HTMLButtonElement>) => {
                    e.currentTarget.blur();
                    setOpen(true);
                }}
            />
            <ShareDesignModal
                title="Design Preview"
                open={open}
                onCancel={() => setOpen(false)}
                footer={null}
                width={800}
                zIndex={isMobile ? 2000 : 1000} 
            >
                <Preview>
                    <PreviewCanvas id="new-canvas-id" />
                </Preview>
                <StyledWrapper>
                    <DownloadButton icon={<DownloadOutlined />} onClick={handleDownload} >
                        Download as Image
                    </DownloadButton>
                    <CopyButton icon={<CopyOutlined />} onClick={handleCopyLink} disabled={isCopied} >
                        {isCopied ? "Link copied!" : "Copy Order Page Link"}
                    </CopyButton>
                </StyledWrapper>
                <Divider>OR</Divider>
                <SocialIcon>
                    <ShareSpan>Share Via:</ShareSpan>
                        {socialButtons.map((btn) => (
                            <IconButton
                                key={btn.key}
                                shape="circle"
                                className="social-border"
                                loading={loader === btn.key}
                                icon={btn.icon}
                                onClick={(e: React.MouseEvent<HTMLButtonElement>) => handleSocialButtonClick(e, btn)}
                            />
                        ))}
                </SocialIcon>
            </ShareDesignModal>
        </>
    );
};

export default ShareDesign;
