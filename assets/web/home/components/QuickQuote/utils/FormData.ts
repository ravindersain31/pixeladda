import { DeliverDateProp, DeliveryMethod } from '@react/editor/redux/reducer/editor/interface';
import { Frame, GrommetColor, Grommets, ImprintColor, Shape, Sides } from '@react/editor/redux/interface';
import { ShippingProps } from './calc_shipping';

export interface TemplateSizeProps {
  width: number;
  height: number;
}

interface FinalValuesParams {
  price: number;
  values: any;
  variant: any;
  data: any;
  templateSize: TemplateSizeProps;
  addonConfig: any;
  pricing: any;
  closestVariant: string;
  shipping: ShippingProps;
  delivery: DeliverDateProp;
  additionalData?: { orderQuoteEmail : string }
}

export const getFinalValues = ({
  price,
  values,
  variant,
  data,
  templateSize,
  addonConfig,
  pricing,
  closestVariant,
  shipping,
  delivery,
  additionalData,
}: FinalValuesParams) => {
  return {
    subTotalAmount: pricing.totalAmount,
    totalAmount: pricing.totalAmount,
    totalShippingDiscount: 0,
    totalQuantity: values.quantity,
    isHelpWithArtwork: false,
    isEmailArtworkLater: false,
    items: {
      [variant.id]: {
        productId: variant.id,
        name: `${values.width}x${values.height}`,
        sku: variant.sku,
        isCustom: true,
        isCustomSize: true,
        isSelling: true,
        quantity: values.quantity,
        templateSize: {
          width: values.width,
          height: values.height,
        },
        itemId: null,
        additionalNote: "",
        image: variant.imageUrl,
        template: variant.imageUrl,
        previewType: "canvas",
        addons: addonConfig,
        price: price,
        unitAmount: pricing.unitAmount,
        unitAddOnsAmount: pricing.unitAddOnsAmount,
        totalAmount: pricing.totalAmount,
        canvasData: {
          front: null,
          back: null,
        },
        customSize: {
          templateSize: {
            width: templateSize.width,
            height: templateSize.height
          },
          sku: data.product.sku,
          category: data.product.category.slug,
          isCustomSize: true,
          productId: variant.id,
          closestVariant: closestVariant,
          image: variant.imageUrl,
        }
      }
    },
    shipping: shipping,
    deliveryMethod: {
      key: DeliveryMethod.DELIVERY,
      label: "Delivery",
      type: "percentage",
      discount: 0
    },
    deliveryDate: delivery,
    sides: values.sides ?? Sides.SINGLE,
    imprintColor: values.imprintColor ?? ImprintColor.ONE,
    grommets: values.grommets ?? Grommets.NONE,
    grommetColor: values.grommetColor ?? GrommetColor.SILVER,
    frame: values.frame ?? Frame.NONE,
    shape: values.shape ?? Shape.SQUARE,
    isBlindShipping: false,
    isFreeFreight: false,
    readyForCart: true,
    isNewItem: true,
    uploadedArtworks: [],
    ...(additionalData && { additionalData }),
    productType: data.product.productType,
  };
};
