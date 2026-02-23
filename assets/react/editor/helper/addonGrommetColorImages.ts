import { GrommetColor, Shape } from "../redux/interface";
import { isPromoStore } from "./editor";

const PRODUCT_TYPE_GROMMET_COLOR: Record<string, Record<GrommetColor, string>> = {
    default: {
      [GrommetColor.SILVER]: "https://static.yardsignplus.com/assets/grommet-silver.jpeg",
      [GrommetColor.BLACK]: "https://static.yardsignplus.com/assets/grommet-black.jpeg",
      [GrommetColor.GOLD]: "https://static.yardsignplus.com/assets/grommet-gold.jpeg",
    }    
}

const PRODUCT_TYPE_PROMO_GROMMET_COLOR: Record<string, Record<GrommetColor, string>> = {
    default: {
      [GrommetColor.SILVER]: "https://static.yardsignplus.com/storage/YS-Steps/Choose-Grommets-Color/Promo-Silver.webp",
      [GrommetColor.BLACK]: "https://static.yardsignplus.com/storage/YS-Steps/Choose-Grommets-Color/Promo-Gold.webp",
      [GrommetColor.GOLD]: "https://static.yardsignplus.com/storage/YS-Steps/Choose-Grommets-Color/Promo-Gold.webp",
    }
}

const YARD_SIGN_GROMMET_COLOR: Record<Shape, Record<GrommetColor, string>> = {
  [Shape.SQUARE]: {
    [GrommetColor.SILVER]: "https://static.yardsignplus.com/assets/grommet-silver.jpeg",
    [GrommetColor.BLACK]: "https://static.yardsignplus.com/assets/grommet-black.jpeg",
    [GrommetColor.GOLD]: "https://static.yardsignplus.com/assets/grommet-gold.jpeg",
  },
  [Shape.CIRCLE]: {
    [GrommetColor.SILVER]: "https://static.yardsignplus.com/storage/editor/1-silver-6968d94daf524067349752.webp",
    [GrommetColor.BLACK]: "https://static.yardsignplus.com/storage/editor/2-black-6968d94f5c971850166460.webp",
    [GrommetColor.GOLD]: "https://static.yardsignplus.com/storage/editor/3-gold-6968d94fb6810979695222.webp",
  },
  [Shape.OVAL]: {
    [GrommetColor.SILVER]: "https://static.yardsignplus.com/storage/editor/1-silver-6968d9d23000b889802570.webp",
    [GrommetColor.BLACK]: "https://static.yardsignplus.com/storage/editor/2-black-6968d9d3d462b722244837.webp",
    [GrommetColor.GOLD]: "https://static.yardsignplus.com/storage/editor/3-gold-6968d9d437acb663221164.webp",
  },
  [Shape.CUSTOM]: {
    [GrommetColor.SILVER]: "https://static.yardsignplus.com/storage/editor/1-silver-6968da37886da553502358.webp",
    [GrommetColor.BLACK]: "https://static.yardsignplus.com/storage/editor/2-black-6968da3933575086646386.webp",
    [GrommetColor.GOLD]: "https://static.yardsignplus.com/storage/editor/3-gold-6968da3989f50018577296.webp",
  },
  [Shape.CUSTOM_WITH_BORDER]: {
    [GrommetColor.SILVER]: "https://static.yardsignplus.com/storage/editor/1-silver-6968db05affaf503519087.webp",
    [GrommetColor.BLACK]: "https://static.yardsignplus.com/storage/editor/2-black-6968db074e59b719694955.webp",
    [GrommetColor.GOLD]: "https://static.yardsignplus.com/storage/editor/3-gold-6968db07b3502031689545.webp",
  },
};

const YARD_SIGN_PROMO_GROMMET_COLOR: Record<Shape, Record<GrommetColor, string>> = {
  [Shape.SQUARE]: {
    [GrommetColor.SILVER]: "https://static.yardsignplus.com/storage/YS-Steps/Choose-Grommets-Color/Promo-Silver.webp",
    [GrommetColor.BLACK]: "https://static.yardsignplus.com/storage/YS-Steps/Choose-Grommets-Color/Promo-Gold.webp",
    [GrommetColor.GOLD]: "https://static.yardsignplus.com/storage/YS-Steps/Choose-Grommets-Color/Promo-Gold.webp",
  },
  [Shape.CIRCLE]: {
    [GrommetColor.SILVER]: "https://static.yardsignplus.com/storage/editor/silver-6968de9e5c14c504787187.webp",
    [GrommetColor.BLACK]: "https://static.yardsignplus.com/storage/editor/black-6968de9c5554e276040591.webp",
    [GrommetColor.GOLD]: "https://static.yardsignplus.com/storage/editor/gold-6968de9deda84341582311.webp",
  },
  [Shape.OVAL]: {
    [GrommetColor.SILVER]: "https://static.yardsignplus.com/storage/editor/silver-6968ded86b0b7473258908.webp",
    [GrommetColor.BLACK]: "https://static.yardsignplus.com/storage/editor/black-6968ded694261588177785.webp",
    [GrommetColor.GOLD]: "https://static.yardsignplus.com/storage/editor/gold-6968ded808227368789591.webp",
  },
  [Shape.CUSTOM]: {
    [GrommetColor.SILVER]: "https://static.yardsignplus.com/storage/editor/1-silver-6968df0571e8b565996217.webp",
    [GrommetColor.BLACK]: "https://static.yardsignplus.com/storage/editor/2-black-6968df06d7826848712191.webp",
    [GrommetColor.GOLD]: "https://static.yardsignplus.com/storage/editor/3-gold-6968df073c337612642477.webp",
  },
  [Shape.CUSTOM_WITH_BORDER]: {
    [GrommetColor.SILVER]: "https://static.yardsignplus.com/storage/editor/1-silver-6968df2e0d84c388993023.webp",
    [GrommetColor.BLACK]: "https://static.yardsignplus.com/storage/editor/2-black-6968df2f6f298847479863.webp",
    [GrommetColor.GOLD]: "https://static.yardsignplus.com/storage/editor/3-gold-6968df2fc599d150502714.webp",
  },
};

export const getYardSignImprintColorImageQuote = (addons: any, grommetColor: GrommetColor): string => {
  const currentShape: Shape = addons?.shape ?? Shape.SQUARE;
  const imagesUrls = isPromoStore() ? YARD_SIGN_PROMO_GROMMET_COLOR : YARD_SIGN_GROMMET_COLOR;
  return imagesUrls[currentShape][grommetColor];
};

export const getYardSignImprintColorImage = (currentItem: any, grommetColor: GrommetColor): string => {
  const currentShape: Shape = currentItem?.addons?.shape?.key ?? Shape.SQUARE;
  const imagesUrls = isPromoStore() ? YARD_SIGN_PROMO_GROMMET_COLOR : YARD_SIGN_GROMMET_COLOR;
  return imagesUrls[currentShape][grommetColor];
};

export const grommetColorImages = (currentItem: any) => {

  const getImage = (grommetColor: GrommetColor, product: any) => {
    if (product.isYardSign) return getYardSignImprintColorImage(currentItem, grommetColor);
    return isPromoStore() ? PRODUCT_TYPE_PROMO_GROMMET_COLOR.default[grommetColor] : PRODUCT_TYPE_GROMMET_COLOR.default[grommetColor];
  };

  return {
    [GrommetColor.SILVER]: (product: any) => getImage(GrommetColor.SILVER, product),
    [GrommetColor.BLACK]: (product: any) => getImage(GrommetColor.BLACK, product),
    [GrommetColor.GOLD]: (product: any) => getImage(GrommetColor.GOLD, product)
  };
};

export const grommetColorImagesForQuote = (addons: any): Record<GrommetColor, string> => {
  return {
    [GrommetColor.SILVER]: getYardSignImprintColorImageQuote(addons, GrommetColor.SILVER),
    [GrommetColor.BLACK]: getYardSignImprintColorImageQuote(addons, GrommetColor.BLACK),
    [GrommetColor.GOLD]: getYardSignImprintColorImageQuote(addons, GrommetColor.GOLD),
  };
};