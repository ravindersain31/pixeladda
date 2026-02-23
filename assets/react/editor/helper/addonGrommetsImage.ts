import { Grommets, Shape } from "../redux/interface";
import { HandFanVariantShape } from "../redux/reducer/editor/interface";
import { getHandFanVariantShape, isPromoStore } from "./editor";

const HAND_FAN_GROMMETS: Record<HandFanVariantShape, Record<Grommets, string>> = {
  rectangle: {
    [Grommets.NONE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/0-None-Rectangle.webp",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/1-Top-Corner-Rectangle.webp",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/2-Top-Corners-Rectangle.webp",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/3-Four-Corners-Rectangle.webp",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/4-Six-Corners-Rectangle.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/custom-placement-rectangle-693803884fd96441248852.webp",
  },
  hourglass: {
    [Grommets.NONE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/0-None-Hourglass.webp",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/1-Top-Corner-Hourglass.webp",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/2-Top-Corners-Hourglass.webp",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/3-Four-Corners-Hourglass.webp",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/4-Six-Corners-Hourglass.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/custom-placement-hourglass-693803867bdae832733028.webp",
  },
  paddle: {
    [Grommets.NONE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/0-None-Paddle.webp",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/1-Top-Corner-Paddle.webp",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/2-Top-Corners-Paddle.webp",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/3-Four-Corners-Paddle.webp",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/4-Six-Corners-Paddle.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/custom-placement-paddle-693803873825a191061690.webp",
  },
  square: {
    [Grommets.NONE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/0-None-Square.webp",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/1-Top-Corner-Square.webp",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/2-Top-Corners-Square.webp",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/3-Four-Corners-Square.webp",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/4-Six-Corners-Square.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/custom-placement-square-6938038aa60ea242349839.webp",
  },
  circle: {
    [Grommets.NONE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/HF-Grommets-None.webp",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/HF-Grommets-Top-Center.webp",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/HF-Grommets-Top-Corners.webp",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/HF-Grommets-Four-Corners.webp",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/HF-Grommets-Six-Corners.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/custom-placement-circle-69380383d72ad281971176.webp",
  }
};

const PRODUCT_TYPE_GROMMETS: Record<string, Record<Grommets, string>> = {
  dieCut: {
    [Grommets.NONE]: "https://static.yardsignplus.com/storage/DC-steps/Choose-Your-Grommets/0-Nonewebp.webp",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/storage/DC-steps/Choose-Your-Grommets/1-Top-Centerwebp.webp",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/storage/DC-steps/Choose-Your-Grommets/2-Top-Cornerswebp.webp",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/storage/DC-steps/Choose-Your-Grommets/3-Four-Cornerswebp.webp",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/DC-steps/Choose-Your-Grommets/4-Six-Cornerswebp.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/custom-placement-die-cut-69380385b7351092459900.webp",
  },
  bigHeadCutouts: {
    [Grommets.NONE]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Your-Grommets/0-Nonewebp.webp",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Your-Grommets/1-Top-Centerwebp.webp",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Your-Grommets/2-Top-Cornerswebp.webp",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Your-Grommets/3-Four-Cornerswebp.webp",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Your-Grommets/4-Six-Cornerswebp.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/custom-placement-big-head-6938038208542483415682.webp",
  },
  default: {
    [Grommets.NONE]: "https://static.yardsignplus.com/assets/grommets-none.png",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/assets/grommets-top-center.png",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/assets/grommets-top-corners.png",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/assets/grommets-four-corners.png",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/images/six_grommets.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/grommets-copy-69708b1e575aa261512486.webp",
  },
};

const HAND_FAN_PROMO_GROMMETS: Record<HandFanVariantShape, Record<Grommets, string>> = {
  rectangle: {
    [Grommets.NONE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/Promo-None-Rectangle.webp",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/Promo-Top-Corner-Rectangle.webp",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/Promo-Top-Corners-Rectangle.webp",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/Promo-Four-Corners-Rectangle.webp",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/Promo-Six-Corners-Rectangle.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/custom-placement-rectangle-693803884fd96441248852-copy-694128376eb9f643035448.webp",
  },
  hourglass: {
    [Grommets.NONE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/Promo-None-Hourglass.webp",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/Promo-Top-Corner-Hourglass.webp",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/Promo-Top-Corners-Hourglass.webp",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/Promo-Four-Corners-Hourglass.webp",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/Promo-Six-Corners-Hourglass.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/custom-placement-hourglass-6941289509c95063300572.webp",
  },
  paddle: {
    [Grommets.NONE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/Promo-None-Paddle.webp",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/Promo-Top-Corner-Paddle.webp",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/Promo-Top-Corners-Paddle.webp",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/Promo-Four-Corners-Paddle.webp",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/Promo-Six-Corners-Paddle.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/custom-placement-paddle-6941290ae0a5e166693722.webp",
  },
  square: {
    [Grommets.NONE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/0-None-Square.webp",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/1-Top-Corner-Square.webp",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/2-Top-Corners-Square.webp",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/3-Four-Corners-Square.webp",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/4-Six-Corners-Square.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/custom-placement-square-6938038aa60ea242349839.webp",
  },
  circle: {
    [Grommets.NONE]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/Promo-HF-Circle-None.webp",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/Promo-HF-Circle-Top-Center.webp",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/Promo-HF-Circle-Top-Corners.webp",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/Promo-HF-Circle-Four-Corners.webp",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/HF-steps/Choose-Your-Grommets/Promo-HF-Circle-Six-Corners.webp.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/promo-custom-placement-circle-694104f02bab4955917924.webp",
  }
};

const PRODUCT_TYPE_PROMO_GROMMETS: Record<string, Record<Grommets, string>> = {
  dieCut: {
    [Grommets.NONE]: "https://static.yardsignplus.com/storage/editor/promo-dc-none-1-6943f2f7a5371220402136.webp",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/storage/editor/promo-dc-top-center-1-6943ef2746eef939502634.webp",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/storage/editor/promo-dc-top-corners-1-6943f03663202739114081.webp",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/storage/editor/promo-dc-four-corners-1-6943f1e21ddfd141098502.webp",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/editor/promo-dc-six-corners-1-6943f396b8964708237477.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/custom-placement-die-cut-69380385b7351092459900.webp",
  },
  bigHeadCutouts: {
    [Grommets.NONE]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Your-Grommets/Promo-BHC-None-resize.webp",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Your-Grommets/Promo-BHC-Top-Center-resize.webp",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Your-Grommets/Promo-BHC-Top-Corners-resize.webp",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Your-Grommets/Promo-BHC-Four-Corners-resize.webp",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/BHC-Steps/Choose-Your-Grommets/Promo-BHC-Five-Corners-resize.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/custom-placement-big-head-6938038208542483415682.webp",
  },
  default: {
    [Grommets.NONE]: "https://static.yardsignplus.com/storage/editor/promo-none-1-6943f9516282b403767593.webp",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/storage/editor/promo-top-corners-1-6943fad4c4a07316390797.webp",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/storage/editor/promo-top-center-1-6943fa2970ad6968824621.webp",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/storage/editor/promo-four-corners-1-6943f76643fe3850076691.webp",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/editor/promo-six-corners-1-6943f85f87213016832155.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/grommets-copy-69708b1e575aa261512486.webp",
  },
};

const YARD_SIGN_GROMMETS: Record<Shape, Record<Grommets, string>> = {
  [Shape.SQUARE]: {
    [Grommets.NONE]: "https://static.yardsignplus.com/assets/grommets-none.png",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/assets/grommets-top-center.png",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/assets/grommets-top-corners.png",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/assets/grommets-four-corners.png",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/images/six_grommets.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/grommets-copy-69708b1e575aa261512486.webp",
  },
  [Shape.CIRCLE]: {
    [Grommets.NONE]: "https://static.yardsignplus.com/storage/editor/1-none-6968bb128a7e9282669251.webp",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/storage/editor/2-top-center-6968bb144c4b3618063658.webp",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/storage/editor/3-top-corners-6968bb15101ea919553949.webp",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/storage/editor/4-four-corners-6968bb156804b364056239.webp",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/editor/5-six-corners-6968bb15c06af442195663.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/custom-placement-6968bb162adbd904055518.webp",
  },
  [Shape.OVAL]: {
    [Grommets.NONE]: "https://static.yardsignplus.com/storage/editor/1-none-6968bc8ed7def229527406.webp",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/storage/editor/2-top-center-6968bc924d763206358838.webp",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/storage/editor/3-top-corners-6968bc9304f4f864075789.webp",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/storage/editor/4-four-corners-6968bc935c22f884257636.webp",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/editor/5-six-corners-6968bc93bc73d838219383.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/custom-placement-6968bc9441a54294424346.webp",
  },
  [Shape.CUSTOM]: {
    [Grommets.NONE]: "https://static.yardsignplus.com/storage/editor/1-none-6968bd3d8ea83072541414.webp",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/storage/editor/2-top-center-6968bd3f1b016427385972.webp",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/storage/editor/3-top-corners-6968bd3f7269b876396048.webp",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/storage/editor/4-four-corners-6968bd3fcfb7e350278912.webp",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/editor/5-six-corners-6968bd4037e1f108099758.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/custom-placement-6968bd408e00e070763620.webp",
  },
  [Shape.CUSTOM_WITH_BORDER]: {
    [Grommets.NONE]: "https://static.yardsignplus.com/storage/editor/1-none-6968be2b21928837825233.webp",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/storage/editor/2-top-center-6968be2c98cff073409382.webp",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/storage/editor/3-top-corners-6968be2d00e75333642738.webp",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/storage/editor/4-four-corners-cpia-6968be2d58dcb073564639.webp",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/editor/5-six-corners-6968be2db087c256066819.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/12-custom-placement-6968be2e118cd460511135.webp",
  },
};

const YARD_SIGN_PROMO_GROMMETS: Record<Shape, Record<Grommets, string>> = {
  [Shape.SQUARE]: {
    [Grommets.NONE]: "https://static.yardsignplus.com/storage/editor/promo-none-1-6943f9516282b403767593.webp",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/storage/editor/promo-top-corners-1-6943fad4c4a07316390797.webp",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/storage/editor/promo-top-center-1-6943fa2970ad6968824621.webp",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/storage/editor/promo-four-corners-1-6943f76643fe3850076691.webp",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/editor/promo-six-corners-1-6943f85f87213016832155.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/grommets-copy-69708b1e575aa261512486.webp",
  },
  [Shape.CIRCLE]: {
    [Grommets.NONE]: "https://static.yardsignplus.com/storage/editor/1-none-6968bffd55414084208966.webp",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/storage/editor/2-top-center-6968bffef07ba533555613.webp",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/storage/editor/3-top-corners-6968bfffb607f785252391.webp",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/storage/editor/4-four-corners-6968c000280c7085607120.webp",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/editor/5-six-corners-6968c0008a0b4176858541.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/6-custom-placement-6968c000dfe9d438035022.webp",
  },
  [Shape.OVAL]: {
    [Grommets.NONE]: "https://static.yardsignplus.com/storage/editor/1-none-6968c156a8307764994705.webp",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/storage/editor/2-top-center-6968c1584fd3a739167237.webp",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/storage/editor/3-top-corners-6968c159065ae801806073.webp",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/storage/editor/4-four-corners-6968c15960729936406843.webp",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/editor/5-six-corners-6968c159c1dda877410696.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/6-custom-placement-6968c15a23de3501708335.webp",
  },
  [Shape.CUSTOM]: {
    [Grommets.NONE]: "https://static.yardsignplus.com/storage/editor/1-none-6968c1a346ac4507103722.webp",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/storage/editor/2-top-center-6968c1a4e8f1d344012034.webp",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/storage/editor/3-top-corners-6968c1a557a62125879525.webp",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/storage/editor/4-four-corners-6968c1a5bb8c8139195831.webp",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/editor/5-six-corners-6968c1a62b66b756688757.webp",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/6-custom-placement-6968c1a68fa9f218436746.webp",
  },
  [Shape.CUSTOM_WITH_BORDER]: {
    [Grommets.NONE]: "https://static.yardsignplus.com/storage/editor/1-none-6968c1e99e35c739273841.png",
    [Grommets.TOP_CENTER]: "https://static.yardsignplus.com/storage/editor/2-top-center-6968c1edc9e1e856148054.png",
    [Grommets.TOP_CORNERS]: "https://static.yardsignplus.com/storage/editor/3-top-corners-6968c1ee2c4ed776934548.png",
    [Grommets.FOUR_CORNERS]: "https://static.yardsignplus.com/storage/editor/4-four-corners-6968c1ee82d6e476750363.png",
    [Grommets.SIX_CORNERS]: "https://static.yardsignplus.com/storage/editor/5-six-corners-6968c1eed7f18909267374.png",
    [Grommets.CUSTOM_PLACEMENT]: "https://static.yardsignplus.com/storage/editor/6-custom-placement-6968c1ef39520984046858.png",
  },
};

export const getHandFanGrommetImage = (label: string, grommet: Grommets) => {
  const handFanGrommets = isPromoStore() ? HAND_FAN_PROMO_GROMMETS : HAND_FAN_GROMMETS;
  const normalizedLabel = label.toLowerCase();
  const shape = (Object.keys(handFanGrommets).find(k => normalizedLabel.includes(k)) || "circle") as HandFanVariantShape;
  return handFanGrommets[shape][grommet];
};

export const getYardSignGrommetImageQuote = (addons: any, grommet: Grommets): string => {
  const currentShape: Shape = addons?.shape ?? Shape.SQUARE;
  const imagesUrls = isPromoStore() ? YARD_SIGN_PROMO_GROMMETS : YARD_SIGN_GROMMETS;
  return imagesUrls[currentShape][grommet];
};

export const getYardSignGrommetImage = (currentItem: any, grommet: Grommets): string => {
  const currentShape: Shape = currentItem?.addons?.shape?.key ?? Shape.SQUARE;
  const imagesUrls = isPromoStore() ? YARD_SIGN_PROMO_GROMMETS : YARD_SIGN_GROMMETS;
  return imagesUrls[currentShape][grommet];
};

export const grommetImages = (currentItem: any) => {
  const itemLabel = getHandFanVariantShape(currentItem?.name || '', currentItem?.label || '');

  const getImageForGrommet = (grommet: Grommets, product: any) => {
    if (product.isDieCut) return isPromoStore() ? PRODUCT_TYPE_PROMO_GROMMETS.dieCut[grommet] : PRODUCT_TYPE_GROMMETS.dieCut[grommet];
    if (product.isBigHeadCutouts) return isPromoStore() ? PRODUCT_TYPE_PROMO_GROMMETS.bigHeadCutouts[grommet] : PRODUCT_TYPE_GROMMETS.bigHeadCutouts[grommet];
    if (product.isHandFans) return getHandFanGrommetImage(itemLabel, grommet);
    if (product.isYardSign) return getYardSignGrommetImage(currentItem, grommet);
    return isPromoStore() ? PRODUCT_TYPE_PROMO_GROMMETS.default[grommet] : PRODUCT_TYPE_GROMMETS.default[grommet];
  };

  return {
    [Grommets.NONE]: (product: any) => getImageForGrommet(Grommets.NONE, product),
    [Grommets.TOP_CENTER]: (product: any) => getImageForGrommet(Grommets.TOP_CENTER, product),
    [Grommets.TOP_CORNERS]: (product: any) => getImageForGrommet(Grommets.TOP_CORNERS, product),
    [Grommets.FOUR_CORNERS]: (product: any) => getImageForGrommet(Grommets.FOUR_CORNERS, product),
    [Grommets.SIX_CORNERS]: (product: any) => getImageForGrommet(Grommets.SIX_CORNERS, product),
    [Grommets.CUSTOM_PLACEMENT]: (product: any) => getImageForGrommet(Grommets.CUSTOM_PLACEMENT, product),
  };
};

export const grommetImagesForQuote = (addons: any): Record<Grommets, string> => {
  return {
    [Grommets.NONE]: getYardSignGrommetImageQuote(addons, Grommets.NONE),
    [Grommets.TOP_CENTER]: getYardSignGrommetImageQuote(addons, Grommets.TOP_CENTER),
    [Grommets.TOP_CORNERS]: getYardSignGrommetImageQuote(addons, Grommets.TOP_CORNERS),
    [Grommets.FOUR_CORNERS]: getYardSignGrommetImageQuote(addons, Grommets.FOUR_CORNERS),
    [Grommets.SIX_CORNERS]: getYardSignGrommetImageQuote(addons, Grommets.SIX_CORNERS),
    [Grommets.CUSTOM_PLACEMENT]: getYardSignGrommetImageQuote(addons, Grommets.CUSTOM_PLACEMENT),
  };
};
