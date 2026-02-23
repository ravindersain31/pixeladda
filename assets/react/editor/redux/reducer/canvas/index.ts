import CanvasState from "./interface.ts";

export {default as updateVariant} from "./updateVariant.case.ts";
export {default as updateCanvasData} from "./updateCanvasData.case.ts";
export {default as updateView} from "./updateView.case.ts";
export {default as changeUpdateCount} from "./changeUpdateCount.case.ts";
export { default as changeLoaderCount } from "./changeLoaderCount.case.ts";
export {default as updateActiveObject} from "./updateActiveObject.case.ts";
export {default as updateCanvasLoader} from "./updateCanvasLoader.case.ts";
export { default as updateCustomVariant } from "./updateCustomVariant.case.ts";
export { default as updateCustomSize } from "./updateCustomSize.case.ts";

const initialState: CanvasState = {
    updateCount: 0,
    loaderCount: 0,
    item: {
        productId: 0,
        itemId: null,
        id: 0,
        name: '12x12',
        image: '',
        sku: '',
        quantity: 1,
        template: '',
        canvasData: {
            front: null,
            back: null,
        },
        templateJson: null,
        isEmailArtworkLater: false,
        isHelpWithArtwork: false,
        isCustomSize: false,
        templateSize: {
            width: 6,
            height: 18,
        }
    },
    view: 'front',
    data: {
        front: null,
        back: null,
    },
    activeObject: 0,
    templateSize: {
        width: 12,
        height: 12,
    },
    customSize: {
        templateSize: {
            width: 12,
            height: 12,
        },
        closestVariant: '12x12',
    },
    loading: true,
}

export default initialState;