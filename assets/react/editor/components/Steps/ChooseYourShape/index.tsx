import { Radio, Col } from "antd";
import { StepProps } from "../interface.ts";
import StepCard from "@react/editor/components/Cards/StepCard";
import RadioButton from "@react/editor/components/Radio/RadioButton";
import AddonCard from "@react/editor/components/Cards/AddonCard";
import actions from "@react/editor/redux/actions";
import { AddOnPrices, Frame } from "@react/editor/redux/reducer/editor/interface.ts";
import { useEffect, useState } from "react";
import { useAppDispatch, useAppSelector } from "@react/editor/hook.ts";
import { Shape } from "@react/editor/redux/interface.ts";
import { isMobile } from "react-device-detect";
import { calculateCartTotalFrameQuantity } from "@react/editor/helper/quantity.ts";
import { isPromoStore } from "@react/editor/helper/editor.ts";

const ChooseYourShape = ({ stepNumber }: StepProps) => {

  const item = useAppSelector((state) => state.canvas.item);

  const items = useAppSelector((state) => state.editor.items);

  const editor = useAppSelector((state) => state.editor);

  const [shape, setShape] = useState<string>(editor.shape);

  const searchParams = new URLSearchParams(window.location.search);
  const urlShape = searchParams.get('shape');
  const dispatch = useAppDispatch();

  useEffect(() => {
    if (urlShape) {
      setShape(urlShape);
      dispatch(actions.editor.updateShape(urlShape));
    }
  }, []);

  const onShapeChange = (shapeName: string) => {
    setShape(shapeName);
    dispatch(actions.editor.updateShape(shapeName));
  };

  return (
    <StepCard title="Choose Your Shape" stepNumber={stepNumber}>
      <Radio.Group
        className="ant-row"
        value={shape}
        onChange={(e) => onShapeChange(e.target.value)}
      >
        <Col xs={12} sm={12} md={8} lg={5}>
          <RadioButton value={Shape.SQUARE}>
            <AddonCard
              title="Square / Rectangle"
              imageUrl={isPromoStore() ? "https://static.yardsignplus.com/storage/promo-store/Imprint-Color-Icon.svg" : "https://static.yardsignplus.com/assets/Square.png"}
              ribbonText={
                AddOnPrices.SHAPE[Shape.SQUARE] === 0
                  ? "FREE"
                  : AddOnPrices.SHAPE[Shape.SQUARE]
              }
              ribbonColor={"#1B8A1B"}
              helpText={
                <p className="text-start mb-0">
                  <b>Square / Rectangle Shape:</b><br />
                  Square or Rectangle Shape allows
                  printing and cutting along any
                  defined square or rectangular
                  border. This is the most common
                  and popular choice for standard
                  yard signs, including default sizes.
                </p>
              }
            />
          </RadioButton>
        </Col>
        <Col xs={12} sm={12} md={8} lg={5}>
          <RadioButton value={Shape.CIRCLE}>
            <AddonCard
              title="Circle"
              imageUrl={isPromoStore() ? "https://static.yardsignplus.com/storage/promo-store/Circle-Promo-Icon.svg" : "https://static.yardsignplus.com/assets/Circle.png"}
              ribbonText={"+" + AddOnPrices.SHAPE[Shape.CIRCLE] + "%"}
              ribbonColor={"#1d4e9b"}
              placement={isMobile ? "right" : undefined}
              helpText={
                <p className="text-start mb-0">
                  <b>Circle Shape:</b><br />
                  Circle Shape allows printing
                  and cutting along any circular
                  border. This includes any
                  round outlining.
                </p>
              }
            />
          </RadioButton>
        </Col>
        <Col xs={12} sm={12} md={8} lg={5}>
          <RadioButton value={Shape.OVAL}>
            <AddonCard
              title="Oval"
              imageUrl={isPromoStore() ? "https://static.yardsignplus.com/storage/promo-store/Oval-Promo-Icon.svg" : "https://static.yardsignplus.com/assets/Oval.png"}
              ribbonText={"+" + AddOnPrices.SHAPE[Shape.OVAL] + "%"}
              ribbonColor={"#1d4e9b"}
              placement={isMobile ? "bottom" : undefined}
              helpText={
                <p className="text-start mb-0">
                  <b>Oval Shape:</b><br />
                  Oval Shape allows printing
                  and cutting along any oval
                  border. This includes any
                  oval outlining.
                </p>
              }
            />
          </RadioButton>
        </Col>
        <Col xs={12} sm={12} md={8} lg={5}>
          <RadioButton value={Shape.CUSTOM}>
            <AddonCard
              title="Custom"
              imageUrl={isPromoStore() ? "https://static.yardsignplus.com/storage/promo-store/Custom-Promo-Icon.svg" : "https://static.yardsignplus.com/assets/Custom.png"}
              ribbonText={"+" + AddOnPrices.SHAPE[Shape.CUSTOM] + "%"}
              ribbonColor={"#1d4e9b"}
              placement="right"
              helpText={
                <p className="text-start mb-0">
                  <b>Custom Shape:</b><br />
                  Custom Shape allows printing and cutting along
                  any irregular border or die cut. This includes
                  any undefined outlining for fully custom signs.
                  We will cut along the outer edges of your custom
                  shape. Please leave a comment if necessary on
                  your final cut requirements.
                </p>
              }
            />
          </RadioButton>
        </Col>
        <Col xs={12} sm={12} md={8} lg={5}>
          <RadioButton value={Shape.CUSTOM_WITH_BORDER}>
            <AddonCard
              title="Custom with Border"
              imageUrl={isPromoStore() ? "https://static.yardsignplus.com/storage/YS-Steps/choose-your-shape/promo-custom-with-border-blue.webp" : "https://static.yardsignplus.com/storage/YS-Steps/choose-your-shape/custom-with-border.webp"}
              ribbonText={"+" + AddOnPrices.SHAPE[Shape.CUSTOM_WITH_BORDER] + "%"}
              ribbonColor={"#1d4e9b"}
              placement="bottom"
              helpText={
                <p className="text-start mb-0">
                  <b>Custom with Border Shape:</b><br />
                  Custom with Border Shape allows printing and cutting
                  along any irregular border or die cut. This includes any
                  undefined outlining for fully custom signs. We will print
                  and cut along the outer edges of your custom with
                  border shape. Please leave a comment if necessary on
                  your final print and cut requirements.
                </p>
              }
            />
          </RadioButton>
        </Col>
      </Radio.Group>
    </StepCard>
  );
};

export default ChooseYourShape;
