import {
  InputQuantity,
  StyledBadgeRibbon,
} from "../../styled";

import { isMobile } from "react-device-detect";
import { useAppSelector } from "@react/editor/hook.ts";
import { EnterSizeWrapper } from "./styled";
import { useDispatch } from "react-redux";
import { useEffect } from "react";
import actions from "@react/editor/redux/actions";
import { Frame, ImprintColor, Shape } from "@react/editor/redux/interface";
import { calculateCartTotalFrameQuantity } from "@react/editor/helper/quantity";

interface Props {
  title: string;
  image: string;
  value: number | null;
  onChange?: (value: number) => void;
  active?: boolean;
  isEdit?: boolean;
  ribbonText?: string;
  ribbonColor?: string;
  productId: number;
}

const MAX_ALLOWED_QUANTITY = 100000;

const SingleSizeVariant = ({
  title,
  image,
  value,
  onChange,
  active = false,
  isEdit = false,
  ribbonText,
  ribbonColor,
  productId,
}: Props) => {
  const canvas = useAppSelector((state) => state.canvas);
  const config = useAppSelector((state) => state.config);
  const editor = useAppSelector((state) => state.editor);
  const dispatch = useDispatch();
  const productMetaData = config.product.productMetaData;

  useEffect(() => {
      const { isYardLetters, isDieCut, isBigHeadCutouts, isHandFans } = config.product;
      
      if (isYardLetters || isDieCut || isBigHeadCutouts || isHandFans) {
        dispatch(actions.editor.updateShape(Shape.CUSTOM));
      }

      if (isYardLetters) {
        dispatch(actions.editor.updateImprintColor(ImprintColor.UNLIMITED));
      }

      if (isYardLetters) {
        const frameTypes: {[key: string]: number} | null = productMetaData.frameTypes;
        if(frameTypes) {
          const filteredFrameTypes: Frame[] = Object.keys(frameTypes).filter(frameType => frameTypes[frameType] !== 0 && frameTypes[frameType] !== null).map(frameType => Frame[frameType as keyof typeof Frame]);
          dispatch(actions.editor.updateFrame(filteredFrameTypes));
        } else {
          dispatch(actions.editor.updateFrame([Frame.WIRE_STAKE_10X30]));
        }
        calculateFramePrice();
      }
  }, [config.product]);

  const calculateFramePrice = () => {
    const totalQuantityWithFrames = calculateCartTotalFrameQuantity() ?? 1;
    dispatch(actions.editor.updateFramePrice(totalQuantityWithFrames));
  }

  useEffect(() => {
      calculateFramePrice();
  }, [editor.totalQuantity]);

  return (
      <EnterSizeWrapper
        justify={"center"}
        style={{ textAlign: "center", background: "#f3f3f7" }}
      >
        <InputQuantity
          disabled={canvas.loading && canvas.item.productId !== productId}
          type="text"
          inputMode="numeric"
          placeholder={isMobile ? "Qty" : "Enter Qty"}
          min={0}
          precision={0}
          max={MAX_ALLOWED_QUANTITY}
          maxLength={MAX_ALLOWED_QUANTITY.toString().length}
          parser={(value: any) => parseInt(value).toFixed(0)}
          value={value}
          onChange={(value: any) => onChange && onChange(value)}
          onKeyUp={(e: any) => {
            if (["Backspace", "Delete"].includes(e.key) && onChange) {
              if (e.target.value.length <= 0) {
                onChange(0);
              }
            }
          }}
          onKeyDown={(e: any) => {
            const allowedKeys = [
              "Backspace",
              "Delete",
              "ArrowLeft",
              "ArrowRight",
              "Tab",
              "Enter",
            ];
            if ((e.ctrlKey || e.metaKey) && e.key === 'a') {
              e.preventDefault();
              const inputElement = e.target;
              inputElement.select();
              return;
            }
            if (!allowedKeys.includes(e.key)) {
              const key = Number(e.key);
              if (isNaN(key) || e.key === null || e.key === " ") {
                e.preventDefault();
              }
            }
            const notAllowedKeys = [".", "e", "-"];
            if (notAllowedKeys.includes(e.key)) {
              e.preventDefault();
            }
          }}
          changeOnWheel={false}
        />
      </EnterSizeWrapper>
  );
};

export default SingleSizeVariant;
