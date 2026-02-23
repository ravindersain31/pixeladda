import { Sides } from "../redux/interface";
import { HandFanVariantShape } from "../redux/reducer/editor/interface";
import { getHandFanVariantShape, isPromoStore } from "./editor";

const HAND_FAN_URLS: Record<HandFanVariantShape, Record<Sides, string>> = {
  rectangle: {
    [Sides.SINGLE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Sides/HF-Single-sided-Regtangle-resize.webp",
    [Sides.DOUBLE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Sides/HF-Double-sided-Regtangle-resize.webp",
  },
  hourglass: {
    [Sides.SINGLE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Sides/HF-Single-sided-Hoursglass-resize.webp",
    [Sides.DOUBLE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Sides/HF-Double-sided-Hourglass-resize.webp",
  },
  paddle: {
    [Sides.SINGLE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Sides/HF-Single-sided-Paddle-resize.webp",
    [Sides.DOUBLE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Sides/HF-Double-sided-Paddle-resize.webp",
  },
  circle: {
    [Sides.SINGLE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Sides/Hand-Fans-Single-Sided.webp",
    [Sides.DOUBLE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Sides/Hand-Fans-Double-Sided.webp",
  },
  square: {
    [Sides.SINGLE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Sides/HF-single-sided-Square-resize.webp",
    [Sides.DOUBLE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Sides/HF-Double-sided-Square-resize.webp",
  }
};

const PRODUCT_TYPE_URLS: Record<string, Record<Sides, string>> = {
  dieCut: {
    [Sides.SINGLE]: "https://static.yardsignplus.com/storage/DC-steps/Choose-Your-Sides/YSP-DC-Single-Sided.webp",
    [Sides.DOUBLE]: "https://static.yardsignplus.com/storage/DC-steps/Choose-Your-Sides/YSP-DC-Double-Sided.webp",
  },
  bigHeadCutouts: {
    [Sides.SINGLE]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Your-Sides/Single-Sided.webp",
    [Sides.DOUBLE]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Your-Sides/Double-Sided.webp",
  },
  default: {
    [Sides.SINGLE]: "https://static.yardsignplus.com/assets/side-option-front.png",
    [Sides.DOUBLE]: "https://static.yardsignplus.com/assets/side-option-front-back.png",
  },
};

const HAND_FAN_PROMO_URLS: Record<HandFanVariantShape, Record<Sides, string>> = {
  rectangle: {
    [Sides.SINGLE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Sides/Promo-Rectangle-Single-Sided.webp",
    [Sides.DOUBLE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Sides/Promo-Rectangle-Double-Sided.webp",
  },
  hourglass: {
    [Sides.SINGLE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Sides/Promo-Single-Sided-Hoursglass.webp",
    [Sides.DOUBLE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Sides/Promo-Double-Sided-Hourglass.webp",
  },
  paddle: {
    [Sides.SINGLE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Sides/Promo-Single-Sided-Paddle.webp",
    [Sides.DOUBLE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Sides/Promo-Double-Sided-Paddle.webp",
  },
  circle: {
    [Sides.SINGLE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Sides/Promo-HF-Single-Sided-Circle.webp",
    [Sides.DOUBLE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Sides/Promo-HF-Double-Sided-Circle.webp",
  },
  square: {
    [Sides.SINGLE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Sides/single-sided-Square-resize.webp",
    [Sides.DOUBLE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Sides/Double-sided-Square-resize.webp",
  }
};

const PRODUCT_TYPE_PROMO_URLS: Record<string, Record<Sides, string>> = {
  dieCut: {
    [Sides.SINGLE]: "https://static.yardsignplus.com/storage/DC-steps/Choose-Your-Sides/Promo-Single-Sided.webp",
    [Sides.DOUBLE]: "https://static.yardsignplus.com/storage/DC-steps/Choose-Your-Sides/Promo-DC-Double-Sided.webp.webp",
  },
  bigHeadCutouts: {
    [Sides.SINGLE]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Your-Sides/Promo-Single-Sided.webp.webp",
    [Sides.DOUBLE]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Your-Sides/Promo-BHC-Double-Sided.webp.webp",
  },
  default: {
    [Sides.SINGLE]: "https://static.yardsignplus.com/storage/promo-store/Step-3-01.svg",
    [Sides.DOUBLE]: "https://static.yardsignplus.com/storage/promo-store/Step-3-02.svg",
  },
};

export const getHandFanImage = (label: string, side: Sides): string => {
  const handFanUrls = isPromoStore() ? HAND_FAN_PROMO_URLS : HAND_FAN_URLS;
  const normalizedLabel = label.toLowerCase();
  const key = Object.keys(handFanUrls).find(k => normalizedLabel.includes(k)) as HandFanVariantShape | undefined;
  return handFanUrls[key || 'circle'][side];
};

export const sideImages = (currentItem: any) => {
  const itemLabel = getHandFanVariantShape(currentItem?.name || '', currentItem?.label || '');

  const getImageForSide = (side: Sides, product: any) => {
    if (product.isDieCut) return isPromoStore() ? PRODUCT_TYPE_PROMO_URLS.dieCut[side] : PRODUCT_TYPE_URLS.dieCut[side];
    if (product.isBigHeadCutouts) return isPromoStore() ? PRODUCT_TYPE_PROMO_URLS.bigHeadCutouts[side] : PRODUCT_TYPE_URLS.bigHeadCutouts[side];
    if (product.isHandFans) return getHandFanImage(itemLabel, side);
    return isPromoStore() ? PRODUCT_TYPE_PROMO_URLS.default[side] : PRODUCT_TYPE_URLS.default[side];
  };

  return {
    [Sides.SINGLE]: (product: any) => getImageForSide(Sides.SINGLE, product),
    [Sides.DOUBLE]: (product: any) => getImageForSide(Sides.DOUBLE, product),
  };
};

