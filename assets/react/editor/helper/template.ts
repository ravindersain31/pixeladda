import axios from "axios";
import store from "@react/editor/redux/store.ts";
import ConfigState, {VariantProps, templateSizeProps} from "../redux/reducer/config/interface";
import {AddOnPrices, AddOnProps, CustomArtwork, Frame, ItemProps, PreviewType, Shape} from "@react/editor/redux/reducer/editor/interface.ts";
import AppState from "../redux/interface";
import { CanvasDataProps } from "../redux/reducer/canvas/interface";
import fabric from "@react/editor/canvas/fabric";
import { isPromoStore } from "./editor";

export const fetchTemplate = async (sku: string) => {
    const state = store.getState();
    const product = state.storage.products[sku] ?? null;
    if (product) {
        return {
            product: JSON.parse(JSON.stringify(product)),
        };
    }
    const {data, status} = await axios.get(`${state.config.links.product_config}/${sku}`);
    if (status === 200) {
        return data;
    }
    return null;
}
export const fetchTemplateJson = async (url: string) => {
    const response = await fetch(url);
    const template = await response.json();
    if (template.overlayImage) {
        delete template.overlayImage;
    }
    return template;
}

export const disallowFrameSize = [
    {width: 6, height: 18},
    {width: 6, height: 24},
    {width: 9, height: 12},
    {width: 9, height: 24},
];


export const isDisallowedFrameSize = (templateSize: any, shape?: string) => {
    if (typeof templateSize === 'string') {
        const widthAndHeight = templateSize.split('x');
        templateSize = {
            width: parseInt(widthAndHeight[0]) || 12,
            height: parseInt(widthAndHeight[1]) || 12,
        };
    }

    if (templateSize.width < 12 && shape == Shape.CIRCLE) {
        return true;
    }

    return false;
}

export const isDisallowedFrame = (templateSize: any) => {
    if (typeof templateSize === 'string') {
        const widthAndHeight = templateSize.split('x');
        templateSize = {
            width: parseInt(widthAndHeight[0]) || 12,
            height: parseInt(widthAndHeight[1]) || 12,
        };
    }

    for (const size of disallowFrameSize) {
        if (size.width === templateSize.width && size.height === templateSize.height) {
            return true;
        }
    }
    if (templateSize.width <= 9) {
        return true;
    }
    return false;
}

export const updateFramePriceAccordingToShape = (state: AppState, items: { [key: string]: ItemProps }): { [key: string]: ItemProps } => {
    const updatedItems: { [key: string]: ItemProps } = {};
    const shape = state.editor.shape;
    const config = state.config;
    for (const key in items) {
        const item = items[key];
        const isWireStake = item.isWireStake;

        if ((item.templateSize.width < 12) && shape === Shape.CIRCLE && !isWireStake) {
            if(!Array.isArray(state.editor.frame)){
                item.addons.frame = {
                    ...config.addons.frame.NONE,
                    unitAmount: AddOnPrices.FRAME[Frame.NONE],
                };
            }
        }

        updatedItems[key] = item;
    }

    return updatedItems;
};

export const getClosestVariantCanvasData = (templateSize: templateSizeProps, variants: VariantProps[]): any => {
    const {width, height} = templateSize;
    const totalSize = width * height;
    let closestVariant: any;
    let minDifference = Infinity;

    // First, check for an exact match by name
    for (const variant of variants) {
        if (variant.name === `${width}x${height}`) {
            return variant.templateJson;
        }
    }

    // If no exact match, find the closest variant by size
    for (const variant of variants) {
        const [variantWidth, variantHeight] = variant.name.split("x").map(Number);
        const variantTotalSize = variantWidth * variantHeight;
        const difference = Math.abs(variantTotalSize - totalSize);

        if (difference < minDifference) {
            closestVariant = variant.templateJson;
            minDifference = difference;
        }
    }

    return closestVariant;
};

export const buildImageList = (variantName: string) => {
    const defaultCategories = ["business-ads", "contractor", "for-sale", "political", "real-estate"];
    const imageBaseUrl = 'https://static.yardsignplus.com/assets/editor/fit-in/1100x1100/preview-templates';
    const corrugatedImg1 = 'secondary-Image-corrugated/YSP-corrugated-1.webp';
    const corrugatedImg2 = 'secondary-Image-corrugated/YSP-corrugated-2.webp';
    const isPromo = isPromoStore();
    let weatherResistantImg;
    if (isPromo) {
        weatherResistantImg = 'secondary-Image-corrugated/weather-resistant-img.png';
    } else {
        weatherResistantImg = 'secondary-Image-corrugated/weather-resistant-img.png';
    }
    const YSPDieCutImages = [
        "https://static.yardsignplus.com/product/img/DC-CUSTOM/6874d15e17f65130537933.webp",
        "https://static.yardsignplus.com/storage/product-images/1080-birthday-6881ccf290fdb959296412.webp",
        "https://static.yardsignplus.com/storage/product-images/1080-graduation-6881ccf2ac7f3985672693.webp",
        "https://static.yardsignplus.com/storage/product-images/1080-wedding-6881ccf2b4606885045443.webp",
        "https://static.yardsignplus.com/storage/product-images/die-cut-1-6881ccf2cb9be270521214.webp",
        "https://static.yardsignplus.com/storage/product-images/die-cut-2-6881ccf2d2cd5301855544.webp",
        "https://static.yardsignplus.com/storage/product-images/die-cut-3-6881ccf2da2ef718287887.webp"
    ];

    const PromoDieCutImages = [
        "https://static.yardsignplus.com/product/img/DC-CUSTOM/6916fad0e2bde969328232.webp",
        "https://static.yardsignplus.com/storage/product-images/1080-birthday-6881ccf290fdb959296412.webp",
        "https://static.yardsignplus.com/storage/product-images/1080-graduation-6881ccf2ac7f3985672693.webp",
        "https://static.yardsignplus.com/storage/product-images/1080-wedding-6881ccf2b4606885045443.webp",
        "https://static.yardsignplus.com/storage/promo-store/DC1-YSP-PROMO.webp",
        "https://static.yardsignplus.com/storage/promo-store/DC2-YSP-PROMO.webp",
        "https://static.yardsignplus.com/storage/promo-store/DC3-YSP-PROMO.webp",
    ];

    const YSPBigHeadCutouts = [
        "https://static.yardsignplus.com/product/img/BHC-CUSTOM/68e893767b38c862186272.webp",
        "https://static.yardsignplus.com/storage/product-images/copy-of-material1-6881cd51adeb2442486932.webp",
        "https://static.yardsignplus.com/storage/product-images/copy-of-material-2-6881cd51c8f30992829380.webp",
        "https://static.yardsignplus.com/storage/product-images/copy-of-material-3-6881cd51ed29a622354075.webp",
        "https://static.yardsignplus.com/storage/product-images/copy-of-material-4-6881cd5200337508648137.webp",
        "https://static.yardsignplus.com/storage/product-images/copy-of-material-5-6881cd52079c7378514481.webp",
        "https://static.yardsignplus.com/storage/product-images/copy-of-material-6-6881cd5219548622348594.webp",
        "https://static.yardsignplus.com/storage/product-images/copy-of-material-7-6881cd522daa0563889655.webp",
        "https://static.yardsignplus.com/storage/product-images/copy-of-material-8-6881cd523c5ba818376278.webp",
        "https://static.yardsignplus.com/storage/product-images/copy-of-material-9-6881cd5243c4a560202285.webp"
    ];

    const PromoBigHeadCutouts = [
        "https://static.yardsignplus.com/storage/editor/6915955340549825287294-1-6943e0dc73675652974529.webp",
        "https://static.yardsignplus.com/storage/promo-store/BH1-YSP-PROMO.webp",
        "https://static.yardsignplus.com/storage/promo-store/BH2-YSP-PROMO.webp",
        "https://static.yardsignplus.com/storage/promo-store/BH3-YSP-PROMO.webp",
        "https://static.yardsignplus.com/storage/promo-store/BH4-YSP-PROMO.webp",
        "https://static.yardsignplus.com/storage/promo-store/BH5-YSP-PROMO.webp",
        "https://static.yardsignplus.com/storage/promo-store/BH6-YSP-PROMO.webp",
        "https://static.yardsignplus.com/storage/promo-store/BH7-YSP-PROMO.webp",
        "https://static.yardsignplus.com/storage/promo-store/BH8-YSP-PROMO.webp",
        "https://static.yardsignplus.com/storage/promo-store/BH9-YSP-PROMO.webp",
    ];

    const YSPHandFansImages = [
        "https://static.yardsignplus.com/product/img/HF-CUSTOM/68df6aad0ae9d812046292.webp",
        "https://static.yardsignplus.com/storage/product-images/hand-fans-1.webp",
        "https://static.yardsignplus.com/storage/product-images/hand-fans-2.webp",
        "https://static.yardsignplus.com/storage/product-images/hand-fans-3.webp",
        "https://static.yardsignplus.com/storage/product-images/1080-Christmas.webp",
        "https://static.yardsignplus.com/storage/product-images/1080-Easter.webp",
        "https://static.yardsignplus.com/storage/product-images/1080-Valentines-Day.webp",
        "https://static.yardsignplus.com/storage/product-images/1080-Fathers-Day.webp"
    ];

    const PromoHandFansImages = [
        "https://static.yardsignplus.com/product/img/HF-CUSTOM/68ff504b76df8546005459.webp",
        "https://static.yardsignplus.com/storage/promo-store/HF1-YSP-PROMO.webp",
        "https://static.yardsignplus.com/storage/promo-store/HF2-YSP-PROMO.webp",
        "https://static.yardsignplus.com/storage/promo-store/HF3-YSP-PROMO.webp",
        "https://static.yardsignplus.com/storage/product-images/1080-Christmas.webp",
        "https://static.yardsignplus.com/storage/product-images/1080-Easter.webp",
        "https://static.yardsignplus.com/storage/product-images/1080-Valentines-Day.webp",
        "https://static.yardsignplus.com/storage/product-images/1080-Fathers-Day.webp"
    ];

    let primaryImages: any = {
        withWireStake: {},
        withoutWireStake: {}
    };

    let secondaryImages: any = {
        withWireStake: {},
        withoutWireStake: {}
    }

    if (typeof primaryImages.withWireStake[variantName] === 'undefined' && !isDisallowedFrame(variantName)) {
        primaryImages.withWireStake[variantName] = [];
    }
    if (typeof primaryImages.withoutWireStake[variantName] === 'undefined') {
        primaryImages.withoutWireStake[variantName] = [];
    }
    if (typeof secondaryImages.withWireStake[variantName] === 'undefined') {
        secondaryImages.withWireStake[variantName] = [];
    }
    if (typeof secondaryImages.withoutWireStake[variantName] === 'undefined') {
        secondaryImages.withoutWireStake[variantName] = [];
    }

    for (let category of defaultCategories) {        
        if (!isDisallowedFrame(variantName)) {
            primaryImages.withWireStake[variantName].push(`${imageBaseUrl}/${category}/${variantName}-${category}-with-wire-stakes.png`);
        }
        primaryImages.withoutWireStake[variantName].push(`${imageBaseUrl}/${category}/${variantName}-${category}.png`);
    }


    for (let i = 1; i <= 4; i++) {
        // if(!isDisallowedFrameSize(size)) {
        secondaryImages.withWireStake[variantName].push(`${imageBaseUrl}/secondary-wire-stake/wire-stake-${variantName}/${variantName}-0${i}.png`);

        // }
    }
    let yspPromocorrugatedImg1;
    let yspPromocorrugatedImg2;
    if (isPromo) {
        yspPromocorrugatedImg1 = "https://static.yardsignplus.com/storage/promo-store/C6-YSP-Promo.webp";
        yspPromocorrugatedImg2 = "https://static.yardsignplus.com/storage/promo-store/C5-YSP-Promo.webp";
    } else {
        yspPromocorrugatedImg1 = `${imageBaseUrl}/${corrugatedImg1}`;
        yspPromocorrugatedImg2 = `${imageBaseUrl}/${corrugatedImg2}`;
    }
    secondaryImages.withWireStake[variantName].push(yspPromocorrugatedImg1);
    secondaryImages.withWireStake[variantName].push(yspPromocorrugatedImg2);
    isPromo ? '' : secondaryImages.withWireStake[variantName].push(`${imageBaseUrl}/${weatherResistantImg}`);;
    const HandFansImages = isPromo ? PromoHandFansImages : YSPHandFansImages;
    const BigHeadCutouts = isPromo ? PromoBigHeadCutouts : YSPBigHeadCutouts;
    const DieCutImages = isPromo ? PromoDieCutImages : YSPDieCutImages;

    return {
        primaryImages,
        secondaryImages,
        DieCutImages,
        BigHeadCutouts,
        HandFansImages
    }
}

export const isProductEditable = (config: ConfigState) => {
    if (config.product.isCustomizable && config.product.productType.isCustomizable) {
        // if product is customizable and product type is customizable then show canvas
        return true;
    } else if (!config.product.isCustomizable && config.product.productType.isCustomizable) {
        // if product is not customizable and product type is customizable then show swiper or custom preview
        return false;
    } else if (config.product.productType.isCustomizable) {
        // if product type is customizable then show canvas
        return true;
    }
    // if none of the above then show swiper or custom preview
    return false;
}

export const getVariantLabel = (variantName: string | templateSizeProps, variants: VariantProps[]) => {
    if (typeof variantName !== 'string') {
        variantName = `${variantName.width}x${variantName.height}`;
    }
    variantName = variantName.replaceAll('pricing_', '');
    for (const variant of variants) {
        if (variant.name === variantName && variant.label) {
            return variant.label;
        }
    }
    return variantName;
}

export const updateEditorHeading = (item: ItemProps) => {
    const defaultCustomSizes = ["20x30", "24x6", "24x30", "24x36", "24x48", "30x24", "36x18", "36x24", "48x24", "48x48", "48x72", "48x96", "96x48"]
    const variantName = `${item.templateSize.width}x${item.templateSize.height}`;
    const variantLabel = `${item.templateSize.width}" x ${item.templateSize.height}"`;
    const currentTitle = document.title;
    if (item.isCustom && (!defaultCustomSizes.includes(variantName) || !currentTitle.includes(variantLabel))) {
        const headingTag = document.getElementById('editor_heading');
        if (headingTag) {
            headingTag.innerHTML = `${variantLabel} Custom Yard Signs`;
        }
        document.title = `${variantLabel} Yard Signs - Custom Corrugated Plastic Lawn Signs`;
    }
}

export const isBiggerSize = (size: string, referenceSize: string = '48x24') => {
    const [width, height] = size.split('x').map(Number);
    const [refWidth, refHeight] = referenceSize.split('x').map(Number);
    // Check if the size fits within one of the two orientations (width x height or height x width)
    const fitsInOrientation1 = width <= refWidth && height <= refHeight;
    const fitsInOrientation2 = width <= refHeight && height <= refWidth;

    // Return true if the size exceeds both orientations
    return !(fitsInOrientation1 || fitsInOrientation2);
}

export const generateUniqueId = (): number => {
    const timestamp = Date.now();
    const randomPart = Math.floor(Math.random() * 10000);
    return timestamp + randomPart;
};

export const hasYSPLogo = (canvasData: CanvasDataProps): boolean => {
    const checkCustomIdInCanvas = (canvas: object | string | null | []): boolean => {
        if (!canvas || typeof canvas === 'string' || Array.isArray(canvas)) return false;
        
        if (canvas && (canvas as any).objects) {
            return (canvas as any).objects.some((obj: any) => obj.custom?.id === 'ysp-logo');
        }
        return false;
    };

    return checkCustomIdInCanvas(canvasData.front) || checkCustomIdInCanvas(canvasData.back);
};

export const imageUrlToBlob = async (
  fileUrl: string
): Promise<{ blob: Blob | null; error: string | null }> => {
  if (!fileUrl) return { blob: null, error: "File URL is required" };

  try {
    const response = await fetch(fileUrl);
    if (!response.ok) {
      return { blob: null, error: `Unable to fetch file (Status: ${response.status})` };
    }

    const blob = await response.blob();

    const allowedMimeTypes = [
      "image/jpg",
      "image/jpeg", 
      "image/png",
      "image/gif",
      "image/webp",
      "image/svg+xml", 
      "image/tiff", 
      "image/heic", 
      "image/heif",
      "image/vnd.adobe.photoshop",
      "text/csv", 
      "application/pdf", 
      "application/postscript",
      "application/vnd.ms-excel", 
      "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", 
      "application/vnd.ms-powerpoint", 
      "application/vnd.openxmlformats-officedocument.presentationml.presentation",       
    ];

    const maxSizeBytes = 50 * 1024 * 1024; 

    if (!allowedMimeTypes.includes(blob.type)) {
      return { blob: null, error: `Invalid file type: ${blob.type}` };
    }

    if (blob.size > maxSizeBytes) {
      return { blob: null, error: "File size exceeds 50 MB limit" };
    }

    return { blob, error: null };
  } catch (err) {
    return { blob: null, error: "Failed to fetch file. Check URL or CORS policy." };
  }
};