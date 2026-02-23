import { Frame, Shape } from "../redux/interface";
import { isPromoStore } from "./editor";

const PRODUCT_TYPE_FRAMES: Record<string, Partial<Record<Frame, string>>> = {
  dieCut: {
    [Frame.NONE]: "https://static.yardsignplus.com/storage/DC-steps/Choose-Your-Frame/No-Wire-Stake.webp",
    [Frame.WIRE_STAKE_10X24]: "https://static.yardsignplus.com/storage/DC-steps/Choose-Your-Frame/Standard-10W-x-24H-Wire-Stake.webp",
    [Frame.WIRE_STAKE_10X24_PREMIUM]: "https://static.yardsignplus.com/storage/DC-steps/Choose-Your-Frame/Premium-10W-x-24H-Wire-Stake.webp",
    [Frame.WIRE_STAKE_10X30_SINGLE]: "https://static.yardsignplus.com/storage/DC-steps/Choose-Your-Frame/Single-30H-Wire-Stake.webp",
  },
  bigHeadCutouts: {
    [Frame.NONE]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Your-Frame/No-Wire-Stake.webp",
    [Frame.WIRE_STAKE_10X24]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Your-Frame/Premium-10W-x-24H-Wire-Stake.webp",
    [Frame.WIRE_STAKE_10X24_PREMIUM]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Your-Frame/Premium-10W-x-24H-Wire-Stake.webp",
    [Frame.WIRE_STAKE_10X30_SINGLE]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Your-Frame/Single-30H-Wire-Stake.webp",
  },
  default: {
    [Frame.NONE]: "https://static.yardsignplus.com/assets/frame-none.png",
    [Frame.WIRE_STAKE_10X24]: "https://static.yardsignplus.com/assets/editor/add-ons/wire-stakes/standard-stakes.webp",
    [Frame.WIRE_STAKE_10X24_PREMIUM]: "https://static.yardsignplus.com/assets/editor/add-ons/wire-stakes/premium-stakes.webp",
    [Frame.WIRE_STAKE_10X30_SINGLE]: "https://static.yardsignplus.com/assets/editor/add-ons/wire-stakes/single-stakes.webp",
  },
};

const PRODUCT_TYPE_PROMO_FRAMES: Record<string, Partial<Record<Frame, string>>> = {
  dieCut: {
    [Frame.NONE]: "https://static.yardsignplus.com/storage/editor/promo-dc-no-wire-stake-1-6943ee76633a5659376122.webp",
    [Frame.WIRE_STAKE_10X24]: "https://static.yardsignplus.com/storage/editor/promo-dc-standard-wire-stake-1-6943eb86598ee768751435.webp",
    [Frame.WIRE_STAKE_10X24_PREMIUM]: "https://static.yardsignplus.com/storage/editor/promo-dc-premium-wire-stake-1-6943ed2eb44cb902306757.webp",
    [Frame.WIRE_STAKE_10X30_SINGLE]: "https://static.yardsignplus.com/storage/editor/promo-dc-single-wire-stake-1-6943f62d8a37a005287983.webp",
  },
  bigHeadCutouts: {
    [Frame.NONE]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Your-Frame/Promo-BHC-No-Wire-Stake-resize.webp",
    [Frame.WIRE_STAKE_10X24]: "https://static.yardsignplus.com/storage/editor/promo-bhc-standard-wire-stake-1-6943db835874a152549016.webp",
    [Frame.WIRE_STAKE_10X24_PREMIUM]: "https://static.yardsignplus.com/storage/editor/promo-bhc-premium-wire-stake-1-6943ddf8d18d6826232964.webp",
    [Frame.WIRE_STAKE_10X30_SINGLE]: "https://static.yardsignplus.com/storage/editor/promo-bhc-single-wire-stake-1-6943dedf35d6f675476297.webp",
  },
  default: {
    [Frame.NONE]: "https://static.yardsignplus.com/storage/promo-store/WS-04.svg",
    [Frame.WIRE_STAKE_10X24]: "https://static.yardsignplus.com/storage/promo-store/WS-1.svg",
    [Frame.WIRE_STAKE_10X24_PREMIUM]: "https://static.yardsignplus.com/storage/promo-store/WS-02.svg",
    [Frame.WIRE_STAKE_10X30_SINGLE]: "https://static.yardsignplus.com/storage/promo-store/WS-03.svg",
  },
};

const YARD_SIGN_FRAMES: Record<Shape, Partial<Record<Frame, string>>> = {
  [Shape.SQUARE]: {
    [Frame.NONE]: "https://static.yardsignplus.com/assets/frame-none.png",
    [Frame.WIRE_STAKE_10X24]: "https://static.yardsignplus.com/assets/editor/add-ons/wire-stakes/standard-stakes.webp",
    [Frame.WIRE_STAKE_10X24_PREMIUM]: "https://static.yardsignplus.com/assets/editor/add-ons/wire-stakes/premium-stakes.webp",
    [Frame.WIRE_STAKE_10X30_SINGLE]: "https://static.yardsignplus.com/assets/editor/add-ons/wire-stakes/single-stakes.webp",
  },
  [Shape.CIRCLE]: {
    [Frame.NONE]: "https://static.yardsignplus.com/storage/editor/1-no-wire-stakes-6969d40a4c403569614815.webp",
    [Frame.WIRE_STAKE_10X24]: "https://static.yardsignplus.com/storage/editor/2-standar-6969d40c6816f998582126.webp",
    [Frame.WIRE_STAKE_10X24_PREMIUM]: "https://static.yardsignplus.com/storage/editor/3-premium-6969d40d1fea5772112930.webp",
    [Frame.WIRE_STAKE_10X30_SINGLE]: "https://static.yardsignplus.com/storage/editor/4-single-6969d40d77095944703800.webp",
  },
  [Shape.OVAL]: {
    [Frame.NONE]: "https://static.yardsignplus.com/storage/editor/1-no-wire-stake-6969d57dbf8c4429656574.webp",
    [Frame.WIRE_STAKE_10X24]: "https://static.yardsignplus.com/storage/editor/2-staandar-6969d57f78cde182480795.webp",
    [Frame.WIRE_STAKE_10X24_PREMIUM]: "https://static.yardsignplus.com/storage/editor/3-premium-6969d580329f1108626925.webp",
    [Frame.WIRE_STAKE_10X30_SINGLE]: "https://static.yardsignplus.com/storage/editor/4-single-6969d58096cf8221648517.webp",
  },
  [Shape.CUSTOM]: {
    [Frame.NONE]: "https://static.yardsignplus.com/storage/editor/1-no-wire-stake-s-6969d6577eb10454501695.webp",
    [Frame.WIRE_STAKE_10X24]: "https://static.yardsignplus.com/storage/editor/2-standar-6969d6598af30727321190.webp",
    [Frame.WIRE_STAKE_10X24_PREMIUM]: "https://static.yardsignplus.com/storage/editor/3-premium-6969d65b0cbf0962353612.webp",
    [Frame.WIRE_STAKE_10X30_SINGLE]: "https://static.yardsignplus.com/storage/editor/4-single-6969d65bbc829380526749.webp",
  },
  [Shape.CUSTOM_WITH_BORDER]: {
    [Frame.NONE]: "https://static.yardsignplus.com/storage/editor/1-no-wire-stake-s-6969d6ae1364a029557993.webp",
    [Frame.WIRE_STAKE_10X24]: "https://static.yardsignplus.com/storage/editor/2-standar-6969d6af8d2cf099056642.webp",
    [Frame.WIRE_STAKE_10X24_PREMIUM]: "https://static.yardsignplus.com/storage/editor/3-premium-6969d6b04fcb4057181228.webp",
    [Frame.WIRE_STAKE_10X30_SINGLE]: "https://static.yardsignplus.com/storage/editor/4-single-6969d6b0b385d537595180.webp",
  },
};

const YARD_SIGN_PROMO_FRAMES: Record<Shape, Partial<Record<Frame, string>>> = {
  [Shape.SQUARE]: {
    [Frame.NONE]: "https://static.yardsignplus.com/storage/promo-store/WS-04.svg",
    [Frame.WIRE_STAKE_10X24]: "https://static.yardsignplus.com/storage/promo-store/WS-1.svg",
    [Frame.WIRE_STAKE_10X24_PREMIUM]: "https://static.yardsignplus.com/storage/promo-store/WS-02.svg",
    [Frame.WIRE_STAKE_10X30_SINGLE]: "https://static.yardsignplus.com/storage/promo-store/WS-03.svg",
  },
  [Shape.CIRCLE]: {
    [Frame.NONE]: "https://static.yardsignplus.com/storage/editor/no-wire-stakes-6969d8d6ceed1089081055.webp",
    [Frame.WIRE_STAKE_10X24]: "https://static.yardsignplus.com/storage/editor/2-standar-6969d8d458752065780741.webp",
    [Frame.WIRE_STAKE_10X24_PREMIUM]: "https://static.yardsignplus.com/storage/editor/3-premium-6969d8d5c334d068307207.webp",
    [Frame.WIRE_STAKE_10X30_SINGLE]: "https://static.yardsignplus.com/storage/editor/4-single-6969d8d67a991165852977.webp",
  },
  [Shape.OVAL]: {
    [Frame.NONE]: "https://static.yardsignplus.com/storage/editor/no-wire-stakes-6969d9136b034657770795.webp",
    [Frame.WIRE_STAKE_10X24]: "https://static.yardsignplus.com/storage/editor/2-standar-6969d910d5bef056869762.webp",
    [Frame.WIRE_STAKE_10X24_PREMIUM]: "https://static.yardsignplus.com/storage/editor/3-premium-6969d9124fad8129261649.webp",
    [Frame.WIRE_STAKE_10X30_SINGLE]: "https://static.yardsignplus.com/storage/editor/4-single-6969d91313f11901145031.webp",
  },
  [Shape.CUSTOM]: {
    [Frame.NONE]: "https://static.yardsignplus.com/storage/editor/no-wire-stakes-6969d94c38872776564474.webp",
    [Frame.WIRE_STAKE_10X24]: "https://static.yardsignplus.com/storage/editor/2-standar-6969d949aea71798046627.webp",
    [Frame.WIRE_STAKE_10X24_PREMIUM]: "https://static.yardsignplus.com/storage/editor/3-premium-6969d94b1f213095409888.webp",
    [Frame.WIRE_STAKE_10X30_SINGLE]: "https://static.yardsignplus.com/storage/editor/4-single-6969d94bd3dae505214743.webp",
  },
  [Shape.CUSTOM_WITH_BORDER]: {
    [Frame.NONE]: "https://static.yardsignplus.com/storage/editor/no-wire-stakes-6969d982b8793669626066.webp",
    [Frame.WIRE_STAKE_10X24]: "https://static.yardsignplus.com/storage/editor/2-standar-6969d980359db338002847.webp",
    [Frame.WIRE_STAKE_10X24_PREMIUM]: "https://static.yardsignplus.com/storage/editor/3-premium-6969d981abec8831168895.webp",
    [Frame.WIRE_STAKE_10X30_SINGLE]: "https://static.yardsignplus.com/storage/editor/4-single-6969d9825fddc388969677.webp",
  },
};

export const getYardSignFrameImageForQuote = (addons: any, frame: Frame): string => {
  const currentShape: Shape = addons?.shape ?? Shape.SQUARE;
  const imagesUrls = isPromoStore() ? YARD_SIGN_PROMO_FRAMES : YARD_SIGN_FRAMES;
  return imagesUrls[currentShape]?.[frame] ?? imagesUrls[Shape.SQUARE]?.[Frame.NONE] ?? "";
};

export const getYardSignFrameImage = (currentItem: any, frame: Frame): string => {
  const currentShape: Shape = currentItem?.addons?.shape?.key ?? Shape.SQUARE;
  const imagesUrls = isPromoStore() ? YARD_SIGN_PROMO_FRAMES : YARD_SIGN_FRAMES;
  return imagesUrls[currentShape]?.[frame] ?? imagesUrls[Shape.SQUARE]?.[Frame.NONE] ?? "";
};

export const frameImages = (currentItem: any): Partial<Record<Frame, (product: any) => string | undefined>> => {

  const getImageForFrame = (frame: Frame, product: any) => {
    if (product.isDieCut) return isPromoStore() ? PRODUCT_TYPE_PROMO_FRAMES.dieCut[frame] : PRODUCT_TYPE_FRAMES.dieCut[frame];
    if (product.isBigHeadCutouts) return isPromoStore() ? PRODUCT_TYPE_PROMO_FRAMES.bigHeadCutouts[frame] : PRODUCT_TYPE_FRAMES.bigHeadCutouts[frame];
    if (product.isYardSign) return getYardSignFrameImage(currentItem, frame);
    return isPromoStore() ? PRODUCT_TYPE_PROMO_FRAMES.default[frame] : PRODUCT_TYPE_FRAMES.default[frame];
  };

  return {
    [Frame.NONE]: (product: any) => getImageForFrame(Frame.NONE, product),
    [Frame.WIRE_STAKE_10X24]: (product: any) => getImageForFrame(Frame.WIRE_STAKE_10X24, product),
    [Frame.WIRE_STAKE_10X24_PREMIUM]: (product: any) => getImageForFrame(Frame.WIRE_STAKE_10X24_PREMIUM, product),
    [Frame.WIRE_STAKE_10X30_SINGLE]: (product: any) => getImageForFrame(Frame.WIRE_STAKE_10X30_SINGLE, product),
  };
};

export const frameImagesForQuote = (addons: any): Partial<Record<Frame, string>> => {
  return {
    [Frame.NONE]: getYardSignFrameImageForQuote(addons, Frame.NONE),
    [Frame.WIRE_STAKE_10X24]: getYardSignFrameImageForQuote(addons, Frame.WIRE_STAKE_10X24),
    [Frame.WIRE_STAKE_10X24_PREMIUM]: getYardSignFrameImageForQuote(addons, Frame.WIRE_STAKE_10X24_PREMIUM),
    [Frame.WIRE_STAKE_10X30_SINGLE]: getYardSignFrameImageForQuote(addons, Frame.WIRE_STAKE_10X30_SINGLE),
  };
};
