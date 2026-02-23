import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";
import {Frame, GrommetColor, Grommets, ImprintColor, Sides} from "@react/editor/redux/interface.ts";
import { isDisallowedFrameSize } from "@react/editor/helper/template";
import { Addons } from "./interface";
import { hasSubAddons } from "@react/editor/helper/pricing";

const initializeCase: CaseReducer = (state: AppState, action) => {
    const {store, product, wireStakeProduct, links, categories, artwork, initialData, cartOverview, cart} = action.payload;

    state.config.cart = {...state.config.cart, ...cartOverview};
    if (cart) {
        let hasBiggerSizes = cart.hasBiggerSizes;
        for (const item of Object.values(cart.items) as any[]) {
            if(Object.keys(cart.biggerSizes).length == 1 && Object.keys(cart.biggerSizes).map(Number).includes(parseInt(item.id))) {
                hasBiggerSizes = false;
            }
            state.config.cart.currentItemQuantity += item.quantity;
            state.config.cart.currentItemSubtotal += item.data.totalAmount;
            state.config.cart.currentItemShipping = item.data.shipping;
            if (item.data.addons.frame) {
                if (hasSubAddons(item.data.addons.frame)) {
                    Object.entries(item.data.addons.frame).forEach(([key, frame]: any) => {
                        const frameKey = frame.key || key;
                        const frameQuantity = frame.totalQuantity ?? item.quantity;
                        state.config.cart.currentFrameQuantity[frameKey] =
                        (item.data.addons.frame[frameKey].quantity ?? 0) * frameQuantity;
                    });
                } else {
                    const frameKey = item.data.addons.frame.key;
                    const frameQuantity = item.data.addons.frame.totalQuantity ?? item.quantity;

                    if (
                        frameKey === Frame.WIRE_STAKE_10X30 || 
                        frameKey === Frame.WIRE_STAKE_10X24 ||
                        frameKey === Frame.WIRE_STAKE_10X30_PREMIUM || 
                        frameKey === Frame.WIRE_STAKE_10X24_PREMIUM || 
                        frameKey === Frame.WIRE_STAKE_10X30_SINGLE
                    ) {
                        state.config.cart.currentFrameQuantity[frameKey] = 
                            (state.config.cart.currentFrameQuantity[frameKey] ?? 0) + frameQuantity;
                    }
                }
            }
        }
        state.config.cart.hasBiggerSizes = hasBiggerSizes;
        state.config.cart.biggerSizes = cart.biggerSizes;
    }

    state.config.store = store;
    state.config.product = product;
    state.config.wireStakeProduct = wireStakeProduct;
    state.config.links = links;
    state.config.artwork = artwork;
    state.config.categories = categories;
    state.config.initialData = initialData;

    state.config.initialized = true;

    state.config.addons = Addons;
}

export default initializeCase;