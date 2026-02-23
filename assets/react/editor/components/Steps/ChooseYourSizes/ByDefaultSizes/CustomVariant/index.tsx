import { useAppSelector } from "@react/editor/hook";
import useCanvas from "@react/editor/hooks/useCanvas";
import { Col } from "antd";
import { useContext, useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import SingleCustomVariant, { SizeInput } from "./SingleCustomVariant";
import actions from "@react/editor/redux/actions";
import { recalculateOnUpdateQuantity } from "@react/editor/helper/quantity.ts";
import { isProductEditable, updateEditorHeading } from "@react/editor/helper/template.ts";
import CanvasContext from "@react/editor/context/canvas";
import { CanvasProperties } from "@react/editor/canvas/utils";

interface CustomTemplateSize {
  width: number;
  height: number;
}

const CustomVariant = (
  { onClose }: { onClose: () => void }
) => {
  const product = useAppSelector((state) => state.config.product);
  const editor = useAppSelector((state) => state.editor);
  const canvas = useAppSelector((state) => state.canvas);
  const config = useAppSelector((state) => state.config);
  const customVariant = product.customVariant[0] ?? null;
  const [selectedQuantity, setSelectedQuantity] = useState<number>(editor.items[customVariant.id]?.quantity ?? null);
  const [selectedItem, setSelectedItem] = useState<any>(editor.items[canvas.item.id] ?? null);
  const [customTemplateSize, setCustomTemplateSize] = useState<CustomTemplateSize>(canvas.templateSize);
  const [sizeInputs, setSizeInputs] = useState<SizeInput[]>([
    { id: customVariant.id, width: customTemplateSize.width, height: customTemplateSize.height, quantity: selectedQuantity },
  ]);
  const dispatch = useDispatch();
  const canvasHook = useCanvas();
  const canvasContext = useContext(CanvasContext);

  useEffect(() => {
    if (isProductEditable(config)) {
      canvasHook.autoResizeCanvas(customTemplateSize, true);
    }
    if (canvas.item.isCustomSize) {
      dispatch(
        actions.canvas.updateCustomSize({
          templateSize: customTemplateSize,
        })
      );
    }
  }, [canvas.item.sku, config.product.sku, canvas.item.isCustomSize, canvas.view, canvas.updateCount]);

  const handleSizeChange = (width: number, height: number, sizeInputs?: any, id?: any): void => {
    setCustomTemplateSize({ width, height });
    const input = sizeInputs?.filter((input: any) => input.id === id) || [];
    const selectedInput = input[0] || { width: customTemplateSize.width, height: customTemplateSize.height };
    if (selectedInput.quantity > 0 && selectedItem) {
      dispatch(actions.canvas.updateCustomSize({ templateSize: { width, height } }));
      updateCustomVariant(selectedItem, selectedInput.quantity, width, height, id);
      if (typeof editor.items[id] !== "undefined") {
        updateEditorHeading({
          ...editor.items[id],
          templateSize: {
            width: width,
            height: height,
          },
        });
      }
    }
  };

  const handleQuantityChange = (quantity: number, item: any, sizeInputs?: any, id?: any): void => {
    if (!Number(quantity) && quantity !== 0) {
      return;
    }

    const input = sizeInputs?.filter((input: any) => input.id === id) || [];
    const selectedInput = input[0] || { width: customTemplateSize.width, height: customTemplateSize.height };
    updateCustomVariant(item, quantity, selectedInput.width, selectedInput.height, id);
  };

  const handleDelete = (id: number) => {
    handleQuantityChange(0, selectedItem, null, id);
  };

  const updateCustomVariant = (
    item: any,
    quantity: number,
    width: number,
    height: number,
    id: number
  ): void => {

    const templateSize = {
      width: width,
      height: height
    };

    const updatedItemId = item.id !== id ? null : item.itemId;

    const variant = {
      ...item,
      quantity,
      templateSize: templateSize,
      name: `${templateSize.width}x${templateSize.height}`,
      itemId: updatedItemId,
      id: id,
    };

    if (isProductEditable(config)) {
      canvasHook.autoResizeCanvas({ width, height }, true);
    }

    if (variant.id !== canvas.item.id) {
      if (isProductEditable(config)) {
          // save the canvas data before changing the variant name
          dispatch(actions.canvas.updateCanvasData(canvasContext.canvas.toJSON(CanvasProperties)));
      }
      dispatch(actions.canvas.updateVariant(variant));
      dispatch(actions.canvas.updateCustomVariant(variant));
    }

    const data = recalculateOnUpdateQuantity(variant, quantity);
    if (typeof data.items[id] !== "undefined") {
      updateEditorHeading(data.items[id]);
    }

    dispatch(actions.editor.updateQty(data));
    dispatch(actions.editor.refreshShipping());

    if (quantity <= 0) {
      const autoPickedItem = Object.values(data.items).find((it) => it.quantity > 0 && (it.id !== item.id));
      if (autoPickedItem) {
        dispatch(actions.canvas.updateVariant(autoPickedItem));
      }
    }
    setSelectedItem(item);
    setSelectedQuantity(quantity);
  };

  const ribbons: { [key: string]: string } = {
    "18x24": "Best Seller",
    "24x18": "Best Seller",
  };

  const sizeRibbonsColor: { [key: string]: string } = {
    "18x24": "#0a9c00",
    "24x18": "#237804",
  };

  const handleClose = (sizeInputs: SizeInput[]) => {
    sizeInputs.forEach((input) => {
        if (input.quantity != null && input.quantity > 0) {
          handleQuantityChange(0, editor.items[input.id], sizeInputs, input.id);
        }
      })
    onClose();
  };

  return (
    <>
      {product.customVariant.map((item: any, index: number) => {
        const itemData = editor.items[canvas.item.id ?? 0];
        const hasQuantity = sizeInputs.some(input => input.quantity != null && input.quantity > 0);

        return (
          <Col key={index} xs={24} sm={24} md={24} lg={24}>
            <SingleCustomVariant
              title={item.name}
              productId={item.productId}
              item={selectedItem}
              ribbonText={ribbons[item.name] || ""}
              ribbonColor={sizeRibbonsColor[item.name] || ""}
              image={item.image}
              isEdit={item.itemId !== null}
              active={hasQuantity}
              value={selectedItem && selectedItem.quantity > 0 ? selectedItem.quantity : null}
              onChange={(quantity: number, sizeInputs?: any, id?: number) =>
                handleQuantityChange(quantity, item, sizeInputs, id)
              }
              customTemplateSize={customTemplateSize}
              onSizeChange={(width: number, height: number, sizeInputs?: any, id?: number) =>
                handleSizeChange(width, height, sizeInputs, id)
              }
              onClose={(sizeInputs) => handleClose(sizeInputs)}
              onDelete={(id: number) => handleDelete(id)}
              setSizeInputs={setSizeInputs}
              sizeInputs={sizeInputs}
            />
          </Col>
        );
      })}
    </>
  );
};

export default CustomVariant;