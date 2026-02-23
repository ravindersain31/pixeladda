import { ImprintColor, Shape } from "../redux/interface";
import { HandFanVariantShape } from "../redux/reducer/editor/interface";
import { getHandFanVariantShape, isPromoStore } from "./editor";

const YARD_SIGN_URLS: Record<Shape, Record<ImprintColor, string>> = {
  [Shape.SQUARE]: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/assets/imprint-color-1.png",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/assets/imprint-color-2.png",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/assets/imprint-color-3.png",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/assets/imprint-color-unlimited.png",
  },
  [Shape.CIRCLE]: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/assets/color-1-6968a331a102c675495868.webp",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/assets/color-2-6968a333305ec760760603.webp",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/assets/color-3-6968a333c08a7844775547.webp",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/assets/color-4-6968a3341a3e5201281688.webp",
  },
  [Shape.OVAL]: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/storage/editor/color-1-6968a52a456fa615712738.webp",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/storage/editor/color-2-6968a52b80055148851489.webp",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/storage/editor/color-3-6968a52bc3518065913652.webp",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/storage/editor/color-4-6968a52c1d4ef643824290.webp",
  },
  [Shape.CUSTOM]: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/storage/editor/color-1-6968a60a84c84475483896.webp",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/storage/editor/color-2-6968a60bcc7d7420229462.webp",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/storage/editor/color-3-6968a60c5696e257880570.webp",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/storage/editor/color-4-6968a60c9861e520372772.webp",
  },
  [Shape.CUSTOM_WITH_BORDER]: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/storage/editor/color-1-6968a69342af2111172356.webp",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/storage/editor/color-2-6968a694a52a9881706134.webp",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/storage/editor/color-3-6968a69545156049985886.webp",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/storage/editor/color-4-6968a69590c5c057199155.webp",
  }
};

const YARD_SIGN_PROMO_URLS: Record<Shape, Record<ImprintColor, string>> = {
  [Shape.SQUARE]: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/storage/promo-store/1-Imprint-Color-icon.svg",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/storage/promo-store/2-Imprint-Color-Icon.svg",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/storage/promo-store/3-Imprint-Color-Icon.svg",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/storage/promo-store/Unlimited-Imprint-Color-Icon.svg",
  },
  [Shape.CIRCLE]: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/storage/editor/color-1-6968b271400a9227136290.webp",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/storage/editor/color-2-6968b272d99d2211798810.webp",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/storage/editor/color-3-6968b27392726253110077.webp",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/storage/editor/color-4-6968b27402877482462690.webp",
  },
  [Shape.OVAL]: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/storage/editor/color-1-6968b32c78585299078621.webp",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/storage/editor/color-2-6968b33359dc2481424863.webp",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/storage/editor/color-3-6968b33408fd2909288951.webp",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/storage/editor/color-4-6968b33466722292280449.webp",
  },
  [Shape.CUSTOM]: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/storage/editor/color-1-6968b36555171991185727.webp",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/storage/editor/color-2-6968b36701d19146430881.webp",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/storage/editor/color-3-6968b367afe0e872784561.webp",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/storage/editor/color-4-6968b368163a5025508427.webp",
  },
  [Shape.CUSTOM_WITH_BORDER]: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/storage/editor/color-1-6968b39323d1f313465999.png",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/storage/editor/color-2-6968b394b6b6d982176556.png",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/storage/editor/color-3-6968b395194ae164124045.png",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/storage/editor/color-4-6968b395772f6293802148.png",
  }
};

const HAND_FAN_URLS: Record<HandFanVariantShape, Record<ImprintColor, string>> = {
  rectangle: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/HF-1-Imprint-Colors-Regtangle-resize.webp",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/HF-2-Imprint-Colors-Regtangle-resize.webp",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/HF-3-Imprint-Colors-Regtangle-resize.webp",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/HF-Unlimited-Imprint-Colors-Regtangle-resiz.webp",
  },
  hourglass: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/HF-1-Imprint-Colors-Hourglass-resize.webp",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/HF-2-Imprint-Colors-Hourglass.webp",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/HF-3-Imprint-Colors-Hourglass.webp",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/HF-Unlimited-Imprint-Colors-Hourglass.webp",
  },
  paddle: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/HF-1-Imprint-Colors-Paddle.webp",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/HF-2-Imprint-Colors-Paddle.webp",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/HF-3-Imprint-Colors-Paddle.webp",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/HF-Unlimited-Imprint-Colors-Paddle.webp",
  },
  square: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/HF-1-Imprint-Colors-Square.webp",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/HF-2-Imprint-Colors-Square.webp",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/HF-3-Imprint-Colors-Square.webp",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/HF-Unlimited-Imprint-Colors-Square.webp",
  },
  circle: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/HF-1-Imprint-Color.webp",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/HF-2-Imprint-Colors.webp",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/HF-3-Imprint-Colors.webp",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/HF-Unlimited-Imprint-Colors.webp",
  }
};

const HAND_FAN_PROMO_URLS: Record<HandFanVariantShape, Record<ImprintColor, string>> = {
  rectangle: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/Promo-1-Imprint-Colors-Regtangle.webp",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/Promo-2-Imprint-Colors-Regtangle.webp",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/Promo-3-Imprint-Colors-Regtangle.webp",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/Promo-Unlimited-Imprint-Colors-Regtangle.webp",
  },
  hourglass: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/Promo-1-Imprint-Colors-Hourglass.webp",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/Promo-2-Imprint-Colors-Hourglass.webp",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/Promo-3-Imprint-Colors-Hourglass.webp",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/Promo-Unlimited-Imprint-Colors-Hourglass.webp",
  },
  paddle: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/Promo-1-Imprint-Colors-Paddle.webp",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/Promo-2-Imprint-Colors-Paddle.webp",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/Promo-3-Imprint-Colors-Paddle.webp",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/Promo-Unlimited-Imprint-Colors-Paddle.webp",
  },
  square: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/1-Imprint-Colors-Square.webp",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/2-Imprint-Colors-Square.webp",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/3-Imprint-Colors-Square.webp",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/Unlimited-Imprint-Colors-Square.webp",
  },
  circle: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/Promo-HF-1-Imprint-Color.webp",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/Promo-HF-2-Imprint-Color.webp",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/Promo-HF-3-Imprint-Color.webp",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Imprint-Color/Promo-HF-4-Imprint-Color.webp",
  }
};

const PRODUCT_TYPE_URLS: Record<string, Record<ImprintColor, string>> = {
  dieCut: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/storage/DC-steps/Choose-Imprint-Color/1-Imprint-Color.webp",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/storage/DC-steps/Choose-Imprint-Color/2-Imprint-Colors.webp",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/storage/DC-steps/Choose-Imprint-Color/3-Imprint-Colors.webp",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/storage/DC-steps/Choose-Imprint-Color/Unlimited-Imprint-Colors.webp",
  },
  bigHeadCutouts: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Imprint-Color/1-Imprint-Color.webp",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Imprint-Color/2-Imprint-Colors.webp",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Imprint-Color/3-Imprint-Colors.webp",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Imprint-Color/Unlimited-Imprint-Colors.webp",
  },
  default: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/assets/imprint-color-1.png",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/assets/imprint-color-2.png",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/assets/imprint-color-3.png",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/assets/imprint-color-unlimited.png",
  },
};

const PRODUCT_TYPE_PROMO_URLS: Record<string, Record<ImprintColor, string>> = {
  dieCut: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/storage/editor/promo-dc-1-imprint-color-1-6943f56f23c54664323210.webp",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/storage/editor/promo-dc-2-imprint-color-1-6943ea1140544776432938.webp",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/storage/editor/promo-dc-3-imprint-color-1-6943eaee84428454592702.webp",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/assets/promo-dc-4-imprint-color-1-6943f4b075660112432455.webp",
  },
  bigHeadCutouts: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Imprint-Color/Promo-BHC-1-Imprint-Color-resize.webp",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Imprint-Color/Promo-BHC-2-Imprint-Color-resize.webp",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Imprint-Color/Promo-BHC-3-Imprint-Color-resize.webp",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Imprint-Color/Promo-BHC-4-Imprint-Color-resize.webp",
  },
  default: {
    [ImprintColor.ONE]: "https://static.yardsignplus.com/storage/promo-store/1-Imprint-Color-icon.svg",
    [ImprintColor.TWO]: "https://static.yardsignplus.com/storage/promo-store/2-Imprint-Color-Icon.svg",
    [ImprintColor.THREE]: "https://static.yardsignplus.com/storage/promo-store/3-Imprint-Color-Icon.svg",
    [ImprintColor.UNLIMITED]: "https://static.yardsignplus.com/storage/promo-store/Unlimited-Imprint-Color-Icon.svg",
  },
};

export const getHandFanImprintImage = (label: string, color: ImprintColor) => {
  const handFanUrls = isPromoStore() ? HAND_FAN_PROMO_URLS : HAND_FAN_URLS;
  const normalizedLabel = label.toLowerCase();
  const shape = (Object.keys(handFanUrls).find(k => normalizedLabel.includes(k)) || 'circle') as HandFanVariantShape;
  return handFanUrls[shape][color];
};

export const getYardSignImprintImageQuote = (addons: any, imprintColor: ImprintColor): string => {
  const currentShape: Shape = addons?.shape ?? Shape.SQUARE;
  const imagesUrls = isPromoStore() ? YARD_SIGN_PROMO_URLS : YARD_SIGN_URLS;
  return imagesUrls[currentShape][imprintColor];
};

export const getYardSignImprintImage = (currentItem: any, color: ImprintColor): string => {
  const currentShape: Shape = currentItem?.addons?.shape?.key ?? Shape.SQUARE;
  const imagesUrls = isPromoStore() ? YARD_SIGN_PROMO_URLS : YARD_SIGN_URLS;
  return imagesUrls[currentShape][color];
};

export const imprintImages = (currentItem: any) => {
  const itemLabel = getHandFanVariantShape(currentItem?.name || '', currentItem?.label || '');

  const getImageForColor = (color: ImprintColor, product: any) => {
    if (product.isDieCut) return isPromoStore() ? PRODUCT_TYPE_PROMO_URLS.dieCut[color] : PRODUCT_TYPE_URLS.dieCut[color];
    if (product.isBigHeadCutouts) return isPromoStore() ? PRODUCT_TYPE_PROMO_URLS.bigHeadCutouts[color] : PRODUCT_TYPE_URLS.bigHeadCutouts[color];
    if (product.isHandFans) return getHandFanImprintImage(itemLabel, color);
    if (product.isYardSign) return getYardSignImprintImage(currentItem, color);
    return isPromoStore() ? PRODUCT_TYPE_PROMO_URLS.default[color] : PRODUCT_TYPE_URLS.default[color];
  };

  return {
    [ImprintColor.ONE]: (product: any) => getImageForColor(ImprintColor.ONE, product),
    [ImprintColor.TWO]: (product: any) => getImageForColor(ImprintColor.TWO, product),
    [ImprintColor.THREE]: (product: any) => getImageForColor(ImprintColor.THREE, product),
    [ImprintColor.UNLIMITED]: (product: any) => getImageForColor(ImprintColor.UNLIMITED, product),
  };
};

export const imprintImagesForQuote = (addons: any): Record<ImprintColor, string> => {
  return {
    [ImprintColor.ONE]: getYardSignImprintImageQuote(addons, ImprintColor.ONE),
    [ImprintColor.TWO]: getYardSignImprintImageQuote(addons, ImprintColor.TWO),
    [ImprintColor.THREE]: getYardSignImprintImageQuote(addons, ImprintColor.THREE),
    [ImprintColor.UNLIMITED]: getYardSignImprintImageQuote(addons, ImprintColor.UNLIMITED),
  };
};

