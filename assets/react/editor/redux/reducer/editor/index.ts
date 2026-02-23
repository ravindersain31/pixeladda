import EditorState, { DeliveryMethod, Frame, Flute, GrommetColor, Grommets, ImprintColor, Shape, Sides } from "./interface.ts"

export {default as initialize} from "./initialize.case.ts";
export {default as updateGrommets} from "./updateGrommets.case.ts";
export {default as updateGrommetColor} from "./updateGrommetColor.case.ts";
export {default as updateImprintColor} from "./updateImprintColor.case.ts";
export {default as updateQty} from "./updateQty.case.ts";
export {default as updateShipping} from "./updateShipping.case.ts";
export {default as refreshShipping} from "./refreshShipping.case.ts";
export {default as refreshPricing} from "./refreshPricing.case.ts";
export {default as updateSides} from "./updateSides.case.ts";
export {default as prepareCartData} from "./prepareCartData.case.ts";
export {default as updateAdditionalNote} from "./updateAdditionalNote.case.ts";
export { default as updateFrame } from "./updateFrame.case.ts";
export { default as updateFlute } from "./updateFlute.case.ts";
export {default as updateFramePrice} from "./updateFramePrice.case.ts";
export {default as clearItems} from "./clearItems.case.ts";
export {default as updateDesignOption} from "./updateDesignOption.case.ts";
export {default as updateUploadedArtworks} from "./updateUploadedArtworks.case.ts";
export {default as updateShape} from "./updateShape.case.ts";
export {default as updateDeliveryMethod} from "./updateDeliveryMethod.case.ts"
export {default as updateBlindShipping} from "./updateBlindShipping.case.ts";
export {default as updateFreeFreight} from "./updateFreeFreight.case.ts";
export {default as updateYSPLogoDiscount} from "./updateYSPLogoDiscount.case.ts";
export {default as updateCustomArtwork} from "./updateCustomArtwork.case.ts";
export {default as updateCustomOriginalArtwork} from "./updateCustomOriginalArtwork.case.ts";
export {default as updatePrePackedDiscount } from "./updatePrePackedDiscount.case.ts";
export {default as updateNotes} from "./updateNotes.case.ts";

const initialState: EditorState = {
    subTotalAmount: 0,
    totalAmount: 0,
    totalShipping: 0,
    totalShippingDiscount: 0,
    totalQuantity: 0,
    isHelpWithArtwork: false,
    isEmailArtworkLater: false,
    items: {},
    shipping: {
        day: 0,
        date: "",
        amount: 0,
    },
    sides: Sides.SINGLE,
    imprintColor: ImprintColor.UNLIMITED,
    grommets: Grommets.NONE,
    grommetColor: GrommetColor.SILVER,
    frame: Frame.NONE,
    flute: Flute.VERTICAL,
    shape: Shape.SQUARE,
    deliveryMethod: {
        key: DeliveryMethod.DELIVERY,
        label: "Delivery",
        type: "percentage",
        discount: 0
    },
    deliveryDate: {
        day: 0,
        isSaturday: false,
        free: false,
        date: "",
        discount: 0,
        timestamp: 0,
        pricing: {}
    },
    isBlindShipping: false,
    isFreeFreight: false,
    readyForCart: false,
    uploadedArtworks: [],
}

export default initialState;