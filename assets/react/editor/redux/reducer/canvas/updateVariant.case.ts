import {CaseReducer} from "@reduxjs/toolkit";
import AppState from "@react/editor/redux/reducer/interface.ts";
import {getClosestVariantFromPricing} from "@react/editor/helper/size-calc.ts";

const updateVariantCase: CaseReducer = (state: AppState, action) => {
    const prevCanvas = state.canvas;
    if(action.payload.isWireStake) {
        return;
    }
    if ((prevCanvas.data.front !== null || prevCanvas.data.back !== null) && prevCanvas.item.productId !== 0) {
        state.editor.items[prevCanvas.item.id] = {
            ...state.editor.items[prevCanvas.item.id],
            canvasData: state.canvas.data,
        };

        const product = state.config.product;
        if(typeof state.storage.products[product.sku] === 'undefined') {
            state.storage.products[product.sku] = product;
        }
        state.storage.products[product.sku] = {
            ...state.storage.products[product.sku],
            variants: product.variants.map((variant) => {
                if (variant.name === state.canvas.item.name) {
                    variant.canvasData = state.canvas.data;
                }
                return variant;
            }),
        };
    }

    let canvasData = action.payload.canvasData || {
        front: null,
        back: null,
    };

    state.canvas.item = {
        productId: action.payload.productId,
        id: action.payload.id,
        itemId: action.payload.itemId,
        name: action.payload.name,
        image: action.payload.image,
        sku: action.payload.sku,
        quantity: action.payload.quantity,
        template: action.payload.template,
        canvasData: action.payload.canvasData,
        templateJson: action.payload.templateJson,
        isEmailArtworkLater: action.payload.isEmailArtworkLater,
        isHelpWithArtwork: action.payload.isHelpWithArtwork,
        isCustomSize: action.payload.isCustomSize,
        templateSize: action.payload.templateSize
    };

    state.canvas.data = canvasData;

    const widthAndHeight = action.payload.name.split('x');
    state.canvas.templateSize = {
        width: parseInt(widthAndHeight[0]) || 12,
        height: parseInt(widthAndHeight[1]) || 12,
    }

    state.canvas.loaderCount = 0;

    state.canvas.customSize = {
        templateSize: state.canvas.templateSize,
        closestVariant: getClosestVariantFromPricing(state.canvas.item.name, state.config.product.pricing),
    }
}

export default updateVariantCase;