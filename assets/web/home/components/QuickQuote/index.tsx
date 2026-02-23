import React, { useEffect, useState } from 'react';
import { CaretRightOutlined } from '@ant-design/icons';
import { Collapse, Form, Button, Typography, Select, Row, Col, message } from 'antd';
import { getSteps } from './Steps';
import { StyledButton, StyledModal, StyledModalButton, StyledSaveDesignModal } from './styled';
import { getPriceFromPriceChart, isValidVariantSize } from "@react/editor/helper/pricing";
import { getClosestVariantFromPricing } from "@react/editor/helper/size-calc.ts";
import { postDataToCart } from '@react/editor/components/Steps/ReviewOrderDetails/postDataToCart';
import { getFinalValues, TemplateSizeProps } from './utils/FormData';
import { AddOnPrices, DeliverDateProp } from '@react/editor/redux/reducer/editor/interface';
import { Flute, Frame, GrommetColor, Grommets, ImprintColor, Shape, Sides } from '@react/editor/redux/interface';
import { buildConfigData, calculateAddonPrice, calculateFramePrice } from './utils/calc_addon_prices';
import CustomInput from './CustomInput';
import { Addons } from '@react/editor/redux/reducer/config/interface';
import { isDisallowedFrameSize } from '@react/editor/helper/template';
import { getShippingAndDelivery, getShippingFromShippingChart, ShippingProps } from './utils/calc_shipping';
import { ContactUsButton, ContactUsIncludedEveryPurchaseRow, DeliveryNote, TotalAmount, EmailInput, NewQuoteButton, OrderDesignRow, OrderNowButton, SaveButton, SaveYourDesignButton, SaveYourDesignWrapper } from '@react/editor/components/Steps/ReviewOrderDetails/SaveYourDesign/styled';
import IncludedEveryPurchase from '@react/editor/components/Steps/ReviewOrderDetails/IncludedEveryPurchase';
import EmailQuote from './EmailQuote';
import { getStoreInfo } from '@react/editor/helper/editor';

const { Title, Text } = Typography;

const QuickQuote = ({ data, isLoading }: any) => {
  const email = getStoreInfo().storeEmail;
  const MIN_ALLOWED_SIZE = 48;
  const MAX_ALLOWED_SIZE = 96;
  const [maxWidth, setMaxWidth] = useState<number>(MAX_ALLOWED_SIZE);
  const [maxHeight, setMaxHeight] = useState<number>(MAX_ALLOWED_SIZE);
  const [isQuickQuoteModalVisible, setIsQuickQuoteModalVisible] = useState(false);
  const [isSaveDesignModalVisible, setIsSaveDesignModalVisible] = useState(false);
  const [form] = Form.useForm();
  const [emailQuoteForm] = Form.useForm();
  const [variant, setVariant] = useState<any>(null);
  const [product, setProduct] = useState<any>();
  const [price, setPrice] = useState<number>(0);
  const [totalAmount, setTotalAmount] = useState<number>(0);
  const [quantity, setQuantity] = useState<number>(1);
  const [templateSize, setTemplateSize] = useState<TemplateSizeProps>({
    width: 24,
    height: 18,
  });
  const [closestVariant, setClosestVariant] = useState<string>('24x18');
  const [framePrices, setFramePrices] = useState<{ [key: string]: number }>(AddOnPrices.FRAME);
  const [disallowedFrameForShape, setDisallowedFrameForShape] = useState<boolean>(false);
  const [showFrame, setShowFrame] = useState<boolean>(false);
  const [addonConfig, setAddonConfig] = useState<any>(Addons);
  const [calculatedData, setCalculatedData] = useState<{ [key: string]: number }>({})
  const [showWidthSuffix, setShowWidthSuffix] = useState<boolean>(false);
  const [showHeightSuffix, setShowHeightSuffix] = useState<boolean>(false);
  const [showGrommetColor, setShowGrommetColor] = useState<boolean>(false);
  const [isAddingToCart, setIsAddingToCart] = useState(false);
  const [isOrderQuote, setIsOrderQuote] = useState(false);
  const [shipping, setShipping] = useState<ShippingProps>({
    day: 0,
    date: "",
    amount: 0,
  });
  const [delivery, setDelivery] = useState<DeliverDateProp>({
    day: 0,
    isSaturday: false,
    free: true,
    date: "",
    discount: 0,
    timestamp: 0,
    pricing: {}
  });

  const items = [
    'Expedited Rush Deliveries',
    'FREE Design Previews',
    'No Tax',
    'Expert Help Always Available',
    'FREE Shipping on Qualifying Orders',
    'No Hidden Fees',
    'Bulk Discounts on Large Quantities',
    'Unlimited Proof Revisions',
    'No Minimum Order Quantities',
  ];

  const showQuickQuoteModal = (value: boolean) => {
    setIsQuickQuoteModalVisible(value);
  };

  const showEmailQuoteModal = (value: boolean) => {
    setIsSaveDesignModalVisible(value);
  };

  const showNewQuote = () => {
    setTimeout(() => {
      form.resetFields();
      emailQuoteForm.resetFields();

      const initialValues = {
        width: 24,
        height: 18,
        product: data.product.name,
        quantity: 1,
        sides: Sides.SINGLE,
        imprintColor: ImprintColor.ONE,
        grommets: Grommets.NONE,
        grommetColor: GrommetColor.SILVER,
        flute: Flute.VERTICAL,
        frame: Frame.NONE,
        shape: Shape.SQUARE,
      };

      form.setFieldsValue(initialValues);

      const templateSize = { width: 24, height: 18 };
      const qty = 1;

      const newAddonConfig = buildConfigData(
        form.getFieldsValue(["sides", "shape", "flute", "frame", "imprintColor", "grommets", "grommetColor"]),
        price,
        framePrices,
        qty,
        product
      );
      const newCalculatedData = calculateAddonPrice(newAddonConfig, price, qty, templateSize);
      setTemplateSize(templateSize);
      setQuantity(1);
      setPrice(0);
      setTotalAmount(0);
      setAddonConfig(Addons);
      setCalculatedData(newCalculatedData);
      setClosestVariant('24x18');
      updateTotalPrice(templateSize, 1);
    }, 200);
  };


  useEffect(() => {
    if (isLoading) return;

    if (data) {
      const { width, height } = templateSize;
      const initialValues = {
        width: width,
        height: height,
        product: data.product.name,
        quantity: quantity,
        sides: Sides.SINGLE,
        imprintColor: ImprintColor.ONE,
        grommets: Grommets.NONE,
        grommetColor: GrommetColor.SILVER,
        flute: Flute.VERTICAL,
        frame: Frame.NONE,
        shape: Shape.SQUARE,
      };

      setProduct(data.product);
      setTemplateSize({ width: width, height: height });
      setVariant(data.variants[0]);
      setFramePrices(calculateFramePrice(quantity, data.framePricing));
      form.setFieldsValue(initialValues);
      handleQuantityChange(quantity);
      updateTotalPrice({ width: width, height: height }, quantity);
    }
  }, [data]);

  const handleSizeChange = (width: number, height: number) => {
    if (!Number.isInteger(width) || width === 0 || !Number.isInteger(height) || height === 0) {
      return;
    }

    const updateMaxDimensions = (width: number, height: number) => {
      setMaxWidth(height <= MIN_ALLOWED_SIZE ? MAX_ALLOWED_SIZE : MIN_ALLOWED_SIZE);
      setMaxHeight(width <= MIN_ALLOWED_SIZE ? MAX_ALLOWED_SIZE : MIN_ALLOWED_SIZE);
    };

    setTemplateSize({ width, height });
    updateMaxDimensions(width, height);
    updateTotalPrice({ width, height }, quantity);
  };

  const handleQuantityChange = (value: number) => {
    setQuantity(value);
    updateTotalPrice(templateSize, value);
  };

  const updateTotalPrice = (templateSize: any, quantity: number) => {
    if (!Number.isInteger(quantity) && quantity !== 0) {
      setPrice(0);
      setTotalAmount(0);
      return;
    }
    if (quantity > 0 && templateSize.width > 0 && templateSize.height > 0) {
      const isValidVariant = isValidVariantSize(templateSize, data.variants);
      const closestVariant = getClosestVariantFromPricing(templateSize, data.priceChart);
      const variantPriceChart = data.priceChart.variants[`pricing_${closestVariant}`].pricing;
      const cartQuantity = (data.cart.quantityBySizes[isValidVariant ? templateSize.width + 'x' + templateSize.height : `CUSTOM_${closestVariant}`] || 0);
      const priceBasedOnQty = getPriceFromPriceChart(variantPriceChart, quantity + cartQuantity);
      const newTotalAmount = priceBasedOnQty * quantity;
      const shippingBasedOnQty = getShippingFromShippingChart(data.shipping, quantity + cartQuantity);
      const { deliveryDate, shipping } = getShippingAndDelivery(quantity, shippingBasedOnQty, newTotalAmount, templateSize);

      setShipping(shipping);
      setDelivery(deliveryDate);
      setFramePrices(calculateFramePrice(quantity, data.framePricing));
      setClosestVariant(closestVariant);
      setPrice(priceBasedOnQty);
      setTotalAmount(newTotalAmount);
      setTemplateSize(templateSize);
    } else {
      setPrice(0);
      setTotalAmount(0);
    }
  };

  useEffect(() => {
    if (price > 0) {
      handleFormChange();
    }
  }, [price])

  const handleFormChange = () => {
    const qty = form.getFieldValue("quantity");
    const newAddonConfig = buildConfigData(form.getFieldsValue(["sides", "shape", "flute", "frame", "imprintColor", "grommets", "grommetColor"]), price, framePrices, quantity, product);
    const templateSize = form.getFieldsValue(['width', 'height']);
    const newCalculatedData = calculateAddonPrice(newAddonConfig, price, qty, templateSize);

    setAddonConfig(newAddonConfig);
    setCalculatedData(newCalculatedData);
    setTotalAmount(newCalculatedData.totalAmount);
  };

  const getValuesForSubmit = (values: any, additionalData?: { orderQuoteEmail: string } | undefined) => ({
    price,
    values,
    variant,
    data,
    templateSize,
    addonConfig,
    pricing: calculatedData,
    closestVariant,
    shipping,
    delivery,
    additionalData,
  });

  const handleAddToCart = (values: any) => {
    const finalValues = getFinalValues(getValuesForSubmit(values));
    addToCart(finalValues);
  };

  const handleEmailQuoteSubmit = (values: any) => {
    const orderQuoteEmail: string = emailQuoteForm.getFieldValue('orderQuoteEmail') ?? "";
    const additionalData = { orderQuoteEmail };

    values = {
      templateSize,
      quantity,
      width: templateSize.width,
      height: templateSize.height,
      sides: form.getFieldValue('sides'),
      imprintColor: form.getFieldValue('imprintColor'),
      shape: form.getFieldValue('shape'),
      grommets: form.getFieldValue('grommets'),
      grommetColor: form.getFieldValue('grommetColor'),
      flute: form.getFieldValue('flute'),
      frame: form.getFieldValue('frame'),
    };

    const finalValues = getFinalValues(getValuesForSubmit(values, additionalData));

    addToCart(finalValues, orderQuoteEmail);

    if (orderQuoteEmail) {
      emailQuoteForm.resetFields();
    }
  };

  const addToCart = async (values: any, orderQuoteEmail?: string) => {
    setIsAddingToCart(orderQuoteEmail ? false : true);
    setIsOrderQuote(orderQuoteEmail ? true : false);
    try {
      await postDataToCart(values, data.add_to_cart);
      setIsAddingToCart(false);
      setIsOrderQuote(false);
      setIsSaveDesignModalVisible(false);
    } catch (error) {
      message.error('Failed to add item to cart');
      setIsAddingToCart(false);
      setIsOrderQuote(false);
    } finally {
      setIsAddingToCart(false);
      setIsOrderQuote(false);
      setIsSaveDesignModalVisible(false);
    }
  };


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

  const flute = Form.useWatch('flute', form);
  useEffect(() => {
    if (flute !== Flute.VERTICAL) {
      form.setFieldsValue({
        frame: Frame.NONE,
      });
    }
  }, [flute]);


  const handleOrderSubmit = () => {
      if (!templateSize.width && !templateSize.height && !quantity) {
        const url = `/${product.category.slug}/shop/${product.productType.slug}/${"CUSTOM"}?variant=24x18&qty=1`;
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
  
            window.location.href = `/${product.category.slug}/shop/${product.productType.slug}/${"CUSTOM"}?${queryParams}`;
          })
          .catch((error) => {
            // console.error("Validation failed:", error);
            // handleValidation(error);
          });
      }
  };
  
  const orderUrl = () => {
    const url = `/${product.category.slug}/shop/${product.productType.slug}/${"CUSTOM"}?variant=24x18&qty=1`;
    return (window.location.href = url);
  };
  
  const LiveChat = (event: React.MouseEvent) => {
    //@ts-ignore
    Tawk_API.toggle();
  };

  const addons = form.getFieldsValue(["sides", "shape", "flute", "frame", "imprintColor", "grommets", "grommetColor"]);

  return (
    <>
      <StyledModalButton onClick={() => showQuickQuoteModal(true)}>
        Request Quick Quote
      </StyledModalButton>
      <StyledModal
        title={(
          <>
            <Title level={4} className='modal-title'>Quick Quote</Title>
            <Title level={4}>
              <span style={{ fontFamily: 'Montserrat' }}>Total Amount:</span> <span className="ysp-purple me-3">${calculatedData.totalAmount >= 0 && calculatedData.totalAmount.toFixed(2)}</span>
            </Title>
          </>
        )}
        open={isQuickQuoteModalVisible}
        centered
        onCancel={() => showQuickQuoteModal(false)}
        footer={null}
        width={900}
        afterClose={() => showNewQuote()}
      >
        <Form form={form} onFinish={handleAddToCart} onValuesChange={handleFormChange} layout="vertical" requiredMark={false}>
          <Row gutter={16}>
            <Col span={24}>
              <CustomInput
                maxWidth={maxWidth}
                maxHeight={maxHeight}
                templateSize={templateSize}
                showWidthSuffix={showWidthSuffix}
                setShowWidthSuffix={setShowWidthSuffix}
                showHeightSuffix={showHeightSuffix}
                setShowHeightSuffix={setShowHeightSuffix}
                handleSizeChange={handleSizeChange}
                handleQuantityChange={handleQuantityChange}
                form={form}
                quantity={quantity}
                calculatedData={calculatedData}
              />
            </Col>
            <Col span={24}>
              <Collapse
                expandIconPosition="end"
                bordered={false}
                defaultActiveKey={['1']}
                expandIcon={({ isActive }) => <CaretRightOutlined rotate={isActive ? 90 : 0} />}
                items={getSteps({ showGrommetColor, framePrices, disallowedFrameForShape, showFrame, addons, product })}
              />
            </Col>
          </Row>
          {/* <StyledButton type="primary" htmlType="submit" loading={isAddingToCart}>
            {isAddingToCart ? 'Adding to Cart...' : 'Add to Cart'}
          </StyledButton> */}
        </Form>
        <SaveYourDesignWrapper className="save-wrapper">
          <DeliveryNote className='note-ribbon'>
            Ready to order? Visit our <a className='order-page' onClick={orderUrl} role="button">Order Page </a>
            or contact us by calling <a href="tel:+1-877-958-1499"> +1-877-958-1499</a>, emailing <a href={`mailto:${email}`}>{email}</a>,
            or messaging us on our <a className='live-chat' onClick={LiveChat} role="button">live chat</a>.
            We will email you a digital proof in 1 hour. Once approved, we will begin processing your order.
          </DeliveryNote>
          <TotalAmount>
            <Title level={4}>
              <span style={{ fontFamily: 'Montserrat' }}>Total Amount:</span> <span className="ysp-purple me-3">${calculatedData.totalAmount >= 0 && calculatedData.totalAmount.toFixed(2)}</span>
            </Title>
          </TotalAmount>
          <OrderDesignRow>
            <OrderNowButton onClick={handleOrderSubmit}>
              Order Now
            </OrderNowButton>
            <NewQuoteButton onClick={() => showNewQuote()}>
              New Quote
            </NewQuoteButton>
            <SaveYourDesignButton onClick={() => showEmailQuoteModal(true)}>
              Email Quote
            </SaveYourDesignButton>
          </OrderDesignRow>
          <ContactUsIncludedEveryPurchaseRow>
            <ContactUsButton onClick={() => window.location.href = `${window.location.origin}/contact-us`}>
              Contact Us
            </ContactUsButton>
            <IncludedEveryPurchase items={items}  />
          </ContactUsIncludedEveryPurchaseRow>
        </SaveYourDesignWrapper>
      </StyledModal >
      <EmailQuote
        isSaveDesignModalVisible={isSaveDesignModalVisible}
        showEmailQuoteModal={showEmailQuoteModal}
        emailQuoteForm={emailQuoteForm}
        handleEmailQuoteSubmit={handleEmailQuoteSubmit}
        isOrderQuote={isOrderQuote}
      />
    </>
  );
};

export default QuickQuote;
