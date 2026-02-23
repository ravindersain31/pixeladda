import React, { useEffect, useState } from "react";
import { Row, Col, InputNumber, Form, Popover, Tooltip } from "antd";
import { QuestionCircleOutlined } from '@ant-design/icons';
import { CustomSearchWrapper, OrderButton, StyledCard, QuestionButton, PopoverContent, Title, CustomOptionsWrapper, LowestPricesGuaranteed } from "./styled";
import { getPriceFromPriceChart, isValidVariantSize } from "@react/editor/helper/pricing";
import { isMobile } from "react-device-detect";
import { getClosestVariantFromPricing } from "@react/editor/helper/size-calc.ts";
import CustomOptions from "./CustomOptions";
import { Addons, templateSizeProps } from "@react/editor/redux/reducer/config/interface";
import { AddOnPrices, DeliverDateProp } from "@react/editor/redux/reducer/editor/interface";
import { buildConfigData, calculateAddonPrice, calculateFramePrice } from "../QuickQuote/utils/calc_addon_prices";
import { Flute, Frame, GrommetColor, Grommets, ImprintColor, Shape, Sides } from '@react/editor/redux/interface';
import { isDisallowedFrameSize } from "@react/editor/helper/template";
import { handleNumericKeyDown } from "@react/editor/helper/editor";

interface TemplateSizeProps {
  width: number;
  height: number;
}

const Search = (props: any) => {
  const MAX_ALLOWED_QUANTITY = 100000;
  const MIN_ALLOWED_SIZE = 48;
  const MAX_ALLOWED_SIZE = 96;
  const [totalPrice, setTotalPrice] = useState<number>(0);
  const [quantity, setQuantity] = useState<number>(100);
  const [maxWidth, setMaxWidth] = useState<number>(MAX_ALLOWED_SIZE);
  const [maxHeight, setMaxHeight] = useState<number>(MAX_ALLOWED_SIZE);
  const [templateSize, setTemplateSize] = useState<TemplateSizeProps>({
    width: 24,
    height: 18,
  });
  const [form] = Form.useForm();
  const [showWidthSuffix, setShowWidthSuffix] = useState<boolean>(false);
  const [showHeightSuffix, setShowHeightSuffix] = useState<boolean>(false);
  const [isCollapsed, setIsCollapsed] = useState(false);
  const [framePrices, setFramePrices] = useState<{ [key: string]: number }>(AddOnPrices.FRAME);
  const [disallowedFrame, setDisallowedFrame] = useState<boolean>(false);
  const [disallowedFrameForShape, setDisallowedFrameForShape] = useState<boolean>(false);
  const [showFrame, setShowFrame] = useState<boolean>(false);
  const [addonConfig, setAddonConfig] = useState<any>(Addons);
  const [calculatedData, setCalculatedData] = useState<{ [key: string]: number }>({})
  const [showGrommetColor, setShowGrommetColor] = useState<boolean>(false);
  const [product, setProduct] = useState<any>();

  const handleToggle = () => {
    setIsCollapsed(!isCollapsed);
  };

  useEffect(() => {
    if (props.isLoading) return;
    form.setFieldsValue({
      width: templateSize.width,
      height: templateSize.height,
      quantity: quantity,
      sides: Sides.SINGLE,
      imprintColor: ImprintColor.ONE,
      grommets: Grommets.NONE,
      grommetColor: GrommetColor.SILVER,
      flute: Flute.VERTICAL,
      frame: Frame.NONE,
      shape: Shape.SQUARE,
    });
    setProduct(props.searchConfig.product);
    setFramePrices(calculateFramePrice(quantity, props.searchConfig.framePricing));
    handleQuantityChange(quantity);
  }, [props]);

  const handleQuantityChange = (value: number | undefined): void => {
    if (value === undefined) return;
    setQuantity(value);
    form.setFieldsValue({ quantity: value });
    updateTotalPrice(templateSize, value);
  };

  const updateTotalPrice = (
    templateSize: any,
    quantity: number
  ) => {
    if (!Number.isInteger(quantity) && quantity !== 0) {
      setTotalPrice(0);
      return;
    }
    if (
      quantity > 0 &&
      templateSize.width > 0 &&
      templateSize.height > 0
    ) {
      const isValidVariant = isValidVariantSize(templateSize, props.searchConfig.variants);
      const closestVariant = getClosestVariantFromPricing(templateSize, props.searchConfig.priceChart);
      const variantPriceChart = props.searchConfig.priceChart.variants[`pricing_${closestVariant}`].pricing;
      const cartQuantity = (props.searchConfig.cart.quantityBySizes[isValidVariant ? templateSize.width + 'x' + templateSize.height : `CUSTOM_${closestVariant}`] || 0);
      const priceBasedOnQty = getPriceFromPriceChart(variantPriceChart, quantity + cartQuantity);
      const newTotalAmount = priceBasedOnQty * quantity;

      setTotalPrice(priceBasedOnQty.toFixed(2));
      setFramePrices(calculateFramePrice(quantity, props.searchConfig.framePricing));
    } else {
      setTotalPrice(0);
    }
  };

  const handleSizeChange = (width: number, height: number) => {
    if (!Number.isInteger(width) && width !== 0 && !Number.isInteger(height) && height !== 0) {
      return;
    }
    setTemplateSize && setTemplateSize({ width: width, height: height });
    setMaxWidth(
      height <= MIN_ALLOWED_SIZE ? MAX_ALLOWED_SIZE : MIN_ALLOWED_SIZE
    );
    setMaxHeight(
      width <= MIN_ALLOWED_SIZE ? MAX_ALLOWED_SIZE : MIN_ALLOWED_SIZE
    );
    updateTotalPrice({ width: width, height: height }, quantity);
  };

  const tooltipText =
    "The largest size we can produce is 48 x 96 (width x height) or 96 x 48 in inches.";

  const handleOrderSubmit = () => {
    if (!templateSize.width && !templateSize.height && !quantity) {
      const url = `/${product.category.slug}/shop/${product.productType.slug}/${product.sku}?variant=24x18&qty=1`;
      return (window.location.href = url);
    } else {
      form
        .validateFields()
        .then((values) => {
          const addonsValue = form.getFieldsValue(['sides', 'imprintColor', 'shape', 'grommets', 'grommetColor', 'flute', 'frame']);
          const { width, height, quantity } = values;
          const { sides, imprintColor, shape, grommets, grommetColor, flute, frame } = addonsValue;
          const queryParams = new URLSearchParams({
            variant: `${width}x${height}`,
            qty: quantity,
            sides: sides ?? Sides.SINGLE,
            shape: shape ?? Shape.SQUARE,
            imprintColor: imprintColor ?? ImprintColor.ONE,
            grommets: grommets ?? Grommets.NONE,
            grommetColor: grommetColor ?? GrommetColor.SILVER,
            flute: flute ?? Flute.VERTICAL,
            frame: frame ?? Frame.NONE,
          }).toString();

          window.location.href = `/${product.category.slug}/shop/${product.productType.slug}/${product.sku}?${queryParams}`;
        })
        .catch((error) => {
          // console.error("Validation failed:", error);
          // handleValidation(error);
        });
    }
  };

  const handleFormChange = () => {
    const qty: number = form.getFieldValue("quantity");
    const templateSize: templateSizeProps = form.getFieldsValue(['width', 'height']);
    const newAddonConfig = buildConfigData(form.getFieldsValue(["sides", "shape", "flute", "frame", "imprintColor", "grommets", "grommetColor"]), totalPrice, framePrices, quantity, product);
    const newCalculatedData = calculateAddonPrice(newAddonConfig, Number(totalPrice), qty, templateSize);
    setAddonConfig(newAddonConfig);
    setCalculatedData(newCalculatedData);
  };

  useEffect(() => {
    if (totalPrice > 0) {
      handleFormChange();
    }
  }, [totalPrice])


  useEffect(() => {
    const shape = form.getFieldValue('shape');
    const templateSize = form.getFieldsValue(['width', 'height']);
    const isDisallowed = isDisallowedFrameSize(templateSize, shape);
    if (isDisallowed) {
      form.setFieldValue("frame", Frame.NONE);
    }
    setDisallowedFrameForShape(isDisallowed && shape === Shape.CIRCLE);
  }, [templateSize, form.getFieldValue('shape')]);

  useEffect(() => {
    const grommets = form.getFieldValue("grommets")
    const flute = form.getFieldValue("flute")
    setShowGrommetColor(grommets !== Grommets.NONE);
    if (grommets === Grommets.NONE) {
      form.setFieldsValue({ grommetColor: GrommetColor.SILVER });
    }
    setShowFrame(flute !== Flute.HORIZONTAL);
  }, [form.getFieldValue("grommets"), form.getFieldValue("flute")]);  

  return (
    <CustomSearchWrapper>
      <StyledCard>
        <Form form={form} onSubmitCapture={handleOrderSubmit} onValuesChange={handleFormChange} layout="vertical">
          <LowestPricesGuaranteed
            justify="center"
            align="middle"
            className="pt-sm-2"
            gutter={isMobile ? [8, 8] : [16, 16]}
          >
            Lowest Prices Guaranteed
          </LowestPricesGuaranteed>
          <Row
            justify="center"
            align="middle"
            className="fw-bold pb-mb-2"
            gutter={isMobile ? [8, 8] : [16, 16]}
          >
            <Col xs={8} sm={8} md={6} lg={4}>
              <Tooltip
                title={showWidthSuffix ? tooltipText : ""}
                open={showWidthSuffix}
                onOpenChange={setShowWidthSuffix}
                color="var(--primary-color)"
                overlayStyle={{ fontSize: "12px", width: "200px" }}
              >
                <Form.Item
                  name="width"
                  rules={[{ required: true, message: "Please enter width" }]}
                  status={form.getFieldError("width") ? "error" : ""}
                  help={false}
                  className="input-field"
                  hasFeedback
                >
                  <InputNumber
                    placeholder={isMobile ? "Width (in.)" : "Enter Width (inches)"}
                    min={1}
                    type="text"
                    disabled={props.isLoading}
                    precision={0}
                    inputMode="numeric"
                    max={maxWidth}
                    // maxLength={maxWidth.toString().length}
                    value={templateSize.width}
                    onKeyUp={(e: any) => {
                      setShowWidthSuffix(
                        e.target.value > maxWidth
                      );
                    }}
                    onChange={(value: any) =>
                      handleSizeChange(Number(value), templateSize.height)
                    }
                    onKeyDown={handleNumericKeyDown((value) =>
                        setShowWidthSuffix(Number(value) > maxWidth)
                    )}
                    changeOnWheel={false}
                  />
                </Form.Item>
                <Title>{isMobile ? "Enter Width (in.)" : "Enter Width (inches)"}
                  <Popover
                    placement="bottom"
                    color="var(--primary-color)"
                    overlayStyle={{ fontSize: "12px", width: "200px" }}
                    content={<PopoverContent>{tooltipText}</PopoverContent>}
                  >
                    <QuestionButton
                      shape="circle"
                      icon={<QuestionCircleOutlined />}
                    />
                  </Popover>
                </Title>
              </Tooltip>
            </Col>
            <span className="multiply">x</span>
            <Col xs={8} sm={8} md={6} lg={4}>
              <Tooltip
                title={showHeightSuffix ? tooltipText : ""}
                open={showHeightSuffix}
                onOpenChange={setShowHeightSuffix}
                color="var(--primary-color)"
                overlayStyle={{ fontSize: "12px", width: "200px" }}
              >
                <Form.Item
                  name="height"
                  rules={[{ required: true, message: "Please enter height" }]}
                  status={form.getFieldError("height") ? "error" : ""}
                  help={false}
                  className="input-field"
                  hasFeedback
                >
                  <InputNumber
                    placeholder={isMobile ? "Height (in.)" : "Enter Height (inches)"}
                    disabled={props.isLoading}
                    min={1}
                    precision={0}
                    type="text"
                    inputMode="numeric"
                    max={maxHeight}
                    // maxLength={maxHeight.toString().length}
                    value={templateSize.height}
                    onKeyUp={(e: any) => {
                      setShowHeightSuffix(
                        e.target.value > maxHeight
                      );
                    }}
                    onChange={(value: any) =>
                      handleSizeChange(templateSize.width, Number(value))
                    }
                    onKeyDown={handleNumericKeyDown((value) =>
                      setShowHeightSuffix(Number(value) > maxHeight)
                    )}
                    changeOnWheel={false}
                  />
                </Form.Item>
                <Title>{isMobile ? "Enter Height (in.)" : "Enter Height (inches)"}
                  <Popover
                    placement="bottom"
                    color="var(--primary-color)"
                    overlayStyle={{ fontSize: "12px", width: "200px" }}
                    content={<PopoverContent>{tooltipText}</PopoverContent>}
                  >
                    <QuestionButton
                      shape="circle"
                      icon={<QuestionCircleOutlined />}
                    />
                  </Popover>
                </Title>
              </Tooltip>
            </Col>
            <Col xs={7} sm={7} md={6} lg={4}>
              <Form.Item
                name="quantity"
                rules={[{ required: true, message: "Please enter quantity" }]}
                status={form.getFieldError("quantity") ? "error" : ""}
                help={false}
                hasFeedback
                className="input-field"
              >
                <InputNumber
                  placeholder={isMobile ? "Enter Qty" : "Enter Quantity"}
                  className="quantity-input"
                  disabled={props.isLoading}
                  min={1}
                  precision={0}
                  type="text"
                  inputMode="numeric"
                  max={MAX_ALLOWED_QUANTITY}
                  onChange={(value: any) => handleQuantityChange(value)}
                  onKeyDown={handleNumericKeyDown()}
                  changeOnWheel={false}
                />
              </Form.Item>
              <Title>Enter Quantity</Title>
            </Col>
            <Col xs={24} sm={12} md={10} lg={3} className="price py-2">
              <span>
                <i className="fa-solid fa-tags price-tag"></i>
                Price Each:
              </span>
              <span className="pricing">${calculatedData.totalAmount >= 0 ? (calculatedData.totalAmount / (quantity ?? 1)).toFixed(2) : 0.00}</span>
            </Col>
            <Col xs={24} sm={24} md={8} lg={4}>
              <Form.Item>
                <OrderButton
                  type="primary"
                  disabled={props.isLoading}
                  htmlType="button"
                  className="btn btn-custom-search w-100"
                  onClick={handleOrderSubmit}
                >
                  Order Now
                </OrderButton>
              </Form.Item>
            </Col>
          </Row>
          <CustomOptionsWrapper>
            <CustomOptions
              showGrommetColor={showGrommetColor}
              framePrices={framePrices}
              disallowedFrameForShape={disallowedFrameForShape}
              showFrame={showFrame}
              form={form}
              product={product}
            />
          </CustomOptionsWrapper>
        </Form>
      </StyledCard>
    </CustomSearchWrapper>
  );
};

export default Search;
