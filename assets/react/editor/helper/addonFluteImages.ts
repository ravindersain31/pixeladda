import { Flute, Shape } from "../redux/interface";
import { isPromoStore } from "./editor";

const PRODUCT_TYPE_FLUTES: Record<string, Partial<Record<Flute, string>>> = {
  default: {
    [Flute.VERTICAL]: "https://static.yardsignplus.com/storage/editor/vertical-flutes-695b956f9f344514580184.webp",
    [Flute.HORIZONTAL]: "https://static.yardsignplus.com/storage/editor/horizontal-3-69648246dfcd3030449494.webp",
  },
};

const PRODUCT_TYPE_PROMO_FLUTES: Record<string, Partial<Record<Flute, string>>> = {
  default: {
    [Flute.VERTICAL]: "https://static.yardsignplus.com/storage/editor/promo-flutes-vertical-695de66e6d711588334347.webp",
    [Flute.HORIZONTAL]: "https://static.yardsignplus.com/storage/editor/horizontal-yard-promo-696499133e4c3381291536.webp",
  },
};

const YARD_SIGN_FLUTES: Record<Shape, Partial<Record<Flute, string>>> = {
  [Shape.SQUARE]: {
    [Flute.VERTICAL]: "https://static.yardsignplus.com/storage/editor/vertical-flutes-695b956f9f344514580184.webp",
    [Flute.HORIZONTAL]: "https://static.yardsignplus.com/storage/editor/horizontal-3-69648246dfcd3030449494.webp",
  },
  [Shape.CIRCLE]: {
    [Flute.VERTICAL]: "https://static.yardsignplus.com/storage/editor/vertical-circle-696f15546bb74067581490.webp",
    [Flute.HORIZONTAL]: "https://static.yardsignplus.com/storage/editor/horizontal-circle-696f155213f08243053359.webp",
  },
  [Shape.OVAL]: {
    [Flute.VERTICAL]: "https://static.yardsignplus.com/storage/editor/vertical-oval-696f16221da2c596798431.webp",
    [Flute.HORIZONTAL]: "https://static.yardsignplus.com/storage/editor/horizontal-oval-696f162043cca593468814.webp",
  },
  [Shape.CUSTOM]: {
    [Flute.VERTICAL]: "https://static.yardsignplus.com/storage/editor/vertical-custom-696f165934685343879039.webp",
    [Flute.HORIZONTAL]: "https://static.yardsignplus.com/storage/editor/horizontal-custom-696f1656c37e6219407882.webp",
  },
  [Shape.CUSTOM_WITH_BORDER]: {
    [Flute.VERTICAL]: "https://static.yardsignplus.com/storage/editor/vertical-custom-wb-696f1691d031f982079461.webp",
    [Flute.HORIZONTAL]: "https://static.yardsignplus.com/storage/editor/horizontal-custom-wb-696f168f51d74026029396.webp",
  },
};

const YARD_SIGN_PROMO_FLUTES: Record<Shape, Partial<Record<Flute, string>>> = {
  [Shape.SQUARE]: {
    [Flute.VERTICAL]: "https://static.yardsignplus.com/storage/editor/promo-flutes-vertical-695de66e6d711588334347.webp",
    [Flute.HORIZONTAL]: "https://static.yardsignplus.com/storage/editor/horizontal-yard-promo-696499133e4c3381291536.webp",
  },
  [Shape.CIRCLE]: {
    [Flute.VERTICAL]: "https://static.yardsignplus.com/storage/editor/vertical-circle-promo-696f17ddb0c02370179370.webp",
    [Flute.HORIZONTAL]: "https://static.yardsignplus.com/storage/editor/horizontal-circle-promo-696f17db1416b749215356.webp",
  },
  [Shape.OVAL]: {
    [Flute.VERTICAL]: "https://static.yardsignplus.com/storage/editor/vertical-oval-promo-696f1809f12fa252258595.webp",
    [Flute.HORIZONTAL]: "https://static.yardsignplus.com/storage/editor/horizontal-oval-promo-696f180785422686863627.webp",
  },
  [Shape.CUSTOM]: {
    [Flute.VERTICAL]: "https://static.yardsignplus.com/storage/editor/vertical-custom-promo-696f182a1aa6f881349605.webp",
    [Flute.HORIZONTAL]: "https://static.yardsignplus.com/storage/editor/horizontal-custom-promo-696f1827276de779925930.webp",
  },
  [Shape.CUSTOM_WITH_BORDER]: {
    [Flute.VERTICAL]: "https://static.yardsignplus.com/storage/editor/vertical-custom-wb-promo-696f184b240a0757034396.webp",
    [Flute.HORIZONTAL]: "https://static.yardsignplus.com/storage/editor/horizontal-custom-wb-promo-696f184864a28045503661.webp",
  },
};

export const getYardSignFluteImage = (currentItem: any, flute: Flute): string => {
  const currentShape: Shape = currentItem.isCustomQuickQuote ? currentItem?.addons?.shape ?? Shape.SQUARE : currentItem?.addons?.shape?.key ?? Shape.SQUARE;
  const imagesUrls = isPromoStore() ? YARD_SIGN_PROMO_FLUTES : YARD_SIGN_FLUTES;
  return imagesUrls[currentShape]?.[flute] ?? imagesUrls[Shape.SQUARE]?.[Flute.VERTICAL] ?? "";
};

export const fluteImages = (currentItem: any): Partial<Record<Flute, (product: any) => string | undefined>> => {

  const getImageForFlute = (flute: Flute, product: any) => {
    if (product?.isYardSign) return getYardSignFluteImage(currentItem, flute);
    return isPromoStore() ? PRODUCT_TYPE_PROMO_FLUTES.default[flute] : PRODUCT_TYPE_FLUTES.default[flute];
  };

  return {
    [Flute.VERTICAL]: (product: any) => getImageForFlute(Flute.VERTICAL, product),
    [Flute.HORIZONTAL]: (product: any) => getImageForFlute(Flute.HORIZONTAL, product),
  };
};

