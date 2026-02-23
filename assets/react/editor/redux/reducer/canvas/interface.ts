import { templateSizeProps } from "../config/interface";

export interface CanvasDataProps {
    front: object | string | null | [];
    back: object | string | null | [];
}
export interface CanvasItemProps {
    productId: number;
    id: number;
    itemId: string | null;
    name: string;
    image: string;
    sku: string;
    quantity: number;
    template: string;
    canvasData: CanvasDataProps;
    templateJson: object | string | null;
    isEmailArtworkLater: boolean;
    isHelpWithArtwork: boolean;
    isCustomSize: boolean;
    templateSize: templateSizeProps;
}

export default interface CanvasState {
    updateCount: number;
    loaderCount: number;
    item: CanvasItemProps;
    view: 'front' | 'back';
    data: CanvasDataProps;
    activeObject: number;
    templateSize: {
        width: number;
        height: number;
    },
    customSize: {
        templateSize: {
            width: number;
            height: number;
        },
        closestVariant: string
    };
    loading: boolean;
}