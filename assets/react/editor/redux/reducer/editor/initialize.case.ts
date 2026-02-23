import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";
import initialState from "@react/editor/redux/reducer/editor/index.ts";
import { Flute ,Frame, PRE_PACKED_DISCOUNT, Shape, YSP_LOGO_DISCOUNT } from "./interface";
import { hasSubAddons } from "@react/editor/helper/pricing";

const initializeCase: CaseReducer = (state: AppState, action) => {
    const {cart} = action.payload;

    if (cart) {
        state.editor.deliveryDate = cart?.deliveryDate || initialState.deliveryDate;
        if (cart.items) {

            let totalQuantity = 0;
            let subTotal = 0;

            const items: any = {};
            for (const key of Object.keys(cart.items)) {
                const item = cart.items[key];
                const itemConfig = state.config.product.variants.find((variant) => variant.id === item.data.id);

                items[item.data.id] = item.data;
                items[item.data.id].itemId = item.itemId;
                items[item.data.id].canvasData = item.canvasData;
                items[item.data.id].previewType = item.previewType ?? itemConfig ? itemConfig?.previewType : 'canvas';
                items[item.data.id].YSPLogoDiscount = item.data.YSPLogoDiscount || {
                    hasLogo: false,
                    discount: YSP_LOGO_DISCOUNT,
                    type: "PERCENTAGE",
                    discountAmount: 0,
                };

                if (item.data.addons) {
                    state.editor.sides = item.data.addons.sides.key;
                    state.editor.imprintColor = item.data.addons.imprintColor.key;
                    state.editor.grommets = item.data.addons.grommets.key;
                    state.editor.grommetColor = item.data.addons.grommetColor.key;
                    if (item.data.addons.frame) {
                        if(hasSubAddons(item.data.addons.frame)) {
                            const FrameTypes: Frame[] = Object.keys(item.data.addons.frame).filter(frameType => item.data.addons.frame[frameType] !== 0 && item.data.addons.frame[frameType] !== null).map(frameType => Frame[frameType as keyof typeof Frame]);
                            state.editor.frame = FrameTypes;
                        } else {
                            state.editor.frame = item.data.addons.frame.key;
                        }
                    }
                    state.editor.shape = item.data.addons?.shape?.key || Shape.SQUARE;
                    state.editor.flute = item.data.addons?.flute?.key || Flute.VERTICAL;
                }

                const templateSizeWidthAndHeight = item.data.name.split('x');

                items[item.data.id].templateSize = item.data.templateSize || {
                    width: parseInt(templateSizeWidthAndHeight[0]) || 12,
                    height: parseInt(templateSizeWidthAndHeight[1]) || 12,
                }

                items[item.data.id].customSize = item.data.customSize || {
                    isCustomSize: true,
                    templateSize: {
                        width: 6,
                        height: 18,
                    },
                    productId: null,
                    sku: null,
                    image: null,
                    category: 'custom-signs',
                    closestVariant: '6x18',
                };

                items[item.data.id].prePackedDiscount = item.data.prePackedDiscount || {
                    hasPrePacked: false,
                    discount: PRE_PACKED_DISCOUNT,
                    type: "PERCENTAGE",
                    discountAmount: 0,
                };

                state.canvas.item = {
                    productId: items[item.data.id].productId,
                    itemId: items[item.data.id].itemId,
                    id: items[item.data.id].id,
                    name: items[item.data.id].name,
                    image: items[item.data.id].image,
                    sku: items[item.data.id].sku,
                    quantity: items[item.data.id].quantity,
                    template: items[item.data.id].template,
                    canvasData: items[item.data.id].canvasData || {
                        front: null,
                        back: null,
                    },
                    templateJson: items[item.data.id].templateJson || {},
                    isEmailArtworkLater: items[item.data.id].isEmailArtworkLater,
                    isHelpWithArtwork: items[item.data.id].isHelpWithArtwork,
                    isCustomSize: items[item.data.id].isCustomSize,
                    templateSize: items[item.data.id].templateSize || {
                        width: 6,
                        height: 18
                    }
                };
                state.canvas.data = items[item.data.id].canvasData || {
                    front: null,
                    back: null,
                };

                const widthAndHeight = state.canvas.item.name.split('x');
                state.canvas.templateSize = {
                    width: parseInt(widthAndHeight[0]) || 12,
                    height: parseInt(widthAndHeight[1]) || 12,
                }

                state.canvas.customSize = {
                    templateSize: state.canvas.templateSize,
                    closestVariant: state.canvas.item.name
                }

                totalQuantity += item.quantity;
                subTotal += item.data.totalAmount;
            }
            state.editor.isBlindShipping = cart.isBlindShipping;
            state.editor.isFreeFreight = cart.isFreeFreight;
            state.editor.items = items;
            state.editor.shipping = cart.shipping;
            state.editor.deliveryMethod = cart.deliveryMethod || initialState.deliveryMethod;
            state.editor.totalShippingDiscount = cart.totalShippingDiscount;
            state.editor.deliveryDate = cart.shipping;
            state.editor.totalShipping = parseFloat(cart.totalShipping);
            state.editor.totalQuantity = totalQuantity;
            state.editor.subTotalAmount = parseFloat(subTotal.toFixed(2));
            state.editor.totalAmount = (state.editor.subTotalAmount + state.editor.totalShipping);
            state.canvas.loaderCount = 1;

        }
    }
}

export default initializeCase;