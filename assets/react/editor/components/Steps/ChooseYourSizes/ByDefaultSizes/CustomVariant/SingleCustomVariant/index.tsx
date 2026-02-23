import React, { useEffect, useState } from "react";
import {
  StyledCard,
  InputQuantity,
  StyledBadgeRibbon,
  StyledBadgeEdit,
  InputSizeWidth,
  InputSizeHeight,
  Checkmark,
} from "../../../styled";
import { CheckOutlined, DeleteOutlined, EditOutlined, QuestionCircleOutlined } from "@ant-design/icons";
import { Col, Row, Space, Tooltip, Button, Popover } from "antd";
import { useAppDispatch, useAppSelector } from "@react/editor/hook.ts";
import actions from "@react/editor/redux/actions";
import {
  EnterSizeWrapper,
  Label,
  CloseButton,
  QuestionButton,
  PopoverContent,
  Title,
  BiggerSizeMessage,
  AddAnotherSize,
  DeleteButton,
} from "./styled";
import { isMobile } from "react-device-detect";
import { isBiggerSize } from "@react/editor/helper/template.ts";
import { isItemsHasBiggerSize } from "@react/editor/helper/shipping";
import Freight from "@react/editor/components/common/Freight";
import { generateUniqueId } from "@react/editor/helper/template.ts";
import { ImprintColor, Shape } from "@react/editor/redux/interface";
import { handleNumericKeyDown, toggleFreeFreightBasedOnItems } from "@react/editor/helper/editor";

interface Props {
  title: string;
  image: string;
  item: any;
  value: number | null;
  onChange?: (value: number, sizeInputs: any, id: any) => void;
  onSizeChange?: (width: number, height: number, sizeInputs: any, id: any) => void;
  active?: boolean;
  isEdit?: boolean;
  ribbonText?: string;
  ribbonColor?: string;
  productId: number;
  customTemplateSize: {
    width: number;
    height: number;
  };
  onClose: (sizeInputs: SizeInput[]) => void;
  onDelete: (id: number) => void;
  setSizeInputs: (sizeInputs: SizeInput[]) => void;
  sizeInputs: SizeInput[]
}

export interface SizeInput {
  id: number;
  width: number;
  height: number;
  quantity: number | null;
};

const MAX_ALLOWED_QUANTITY = 100000;
const MIN_ALLOWED_SIZE = 48;
const MAX_ALLOWED_SIZE = 96;

const SingleVariant = ({
  title,
  image,
  item,
  value,
  onChange,
  onSizeChange,
  active = false,
  isEdit = false,
  ribbonText,
  ribbonColor,
  productId,
  customTemplateSize,
  onClose,
  onDelete,
  setSizeInputs,
  sizeInputs,
}: Props) => {
  const [maxWidth, setMaxWidth] = useState<number>(MAX_ALLOWED_SIZE);
  const [maxHeight, setMaxHeight] = useState<number>(MAX_ALLOWED_SIZE);
  const [isWidthZeroMap, setIsWidthZeroMap] = useState<{ [key: number]: boolean }>({});
  const [isHeightZeroMap, setIsHeightZeroMap] = useState<{ [key: number]: boolean }>({});
  const [showWidthSuffixMap, setShowWidthSuffixMap] = useState<{ [key: number]: boolean }>({});
  const [showHeightSuffixMap, setShowHeightSuffixMap] = useState<{ [key: number]: boolean }>({});
  const canvas = useAppSelector((state) => state.canvas);
  const config = useAppSelector((state) => state.config);
  const editor = useAppSelector((state) => state.editor);
  const dispatch = useAppDispatch();
  const tooltipText = 'The largest size we can produce is 48 x 96 (width x height) or 96 x 48 in inches.';

  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const quantityParam = Number(urlParams.get("qty"));
    const variantParam = urlParams.get("variant");
    const itemId = Number(urlParams.get("itemId"));
    const isDefaultVariant = config.product.variants.some(variant => variant.name === variantParam);

    if (quantityParam && !isDefaultVariant) {
      handleQuantityChange(quantityParam, sizeInputs[0].id);
    }
  }, []);

  useEffect(() => {
    const isValidVariant = Object.values(config.product.variants).find(
      (variant: any) =>
        variant.name ===
        `${customTemplateSize.width}x${customTemplateSize.height}`
    );
  }, [customTemplateSize]);

  useEffect(() => {
    const hasBiggerSize = isItemsHasBiggerSize(editor.items);
    const hasHeightGreaterThan48 = sizeInputs.some(input => input.height > 48);
    toggleFreeFreightBasedOnItems(editor.items, editor.isFreeFreight, dispatch);
  }, [customTemplateSize, value, sizeInputs]);

  const handleSizeChange = (width: number, height: number, id: number) => {
    if (width <= 0) {
      width = 1;
    }
    if (height <= 0) {
      height = 1;
    }
    const updatedInputs = sizeInputs.map(input =>
      input.id === id ? { ...input, width, height } : input
    );
    setSizeInputs(updatedInputs);
    onSizeChange && onSizeChange(width, height, updatedInputs, id);
    setMaxWidth(height <= MIN_ALLOWED_SIZE ? MAX_ALLOWED_SIZE : MIN_ALLOWED_SIZE);
    setMaxHeight(width <= MIN_ALLOWED_SIZE ? MAX_ALLOWED_SIZE : MIN_ALLOWED_SIZE);
  };

  const handleQuantityChange = (value: number, id: number) => {
    const updatedInputs = sizeInputs.map(input =>
      input.id === id ? { ...input, quantity: value > 0 ? value : null } : input
    );
    setSizeInputs(updatedInputs);
    onChange && onChange(value, updatedInputs, id);

    if (value > 0) {
      const { isYardLetters, isDieCut, isBigHeadCutouts, isHandFans } = config.product;
      if (isYardLetters || isDieCut || isBigHeadCutouts || isHandFans) {
        dispatch(actions.editor.updateShape(Shape.CUSTOM));
      }

      if (isYardLetters) {
        dispatch(actions.editor.updateImprintColor(ImprintColor.UNLIMITED));
      }
    }
  };

  const addNewSizeInput = () => {
    const id = generateUniqueId();
    setSizeInputs([...sizeInputs, { id: id, width: 24, height: 18, quantity: null }]);
  };

  const handleDeleteSizeInput = (id: number) => {
    onDelete(id);
    const updatedInputs = sizeInputs.filter(input => input.id !== id);
    setSizeInputs(updatedInputs);
  };

  const handleClose = () => {
    const updatedSizeInputs = sizeInputs.map((input) => ({
      ...input,
      quantity: null,
    }));

    onClose(updatedSizeInputs);
    setSizeInputs(updatedSizeInputs);

    if (onChange) {
      updatedSizeInputs.forEach((input) => {
        onChange(0, updatedSizeInputs, input.id);
      });
    }
  };

  const handleWidthSuffixChange = (id: number, value: boolean) => {
    setShowWidthSuffixMap(prev => ({ ...prev, [id]: value }));
  };

  const handleHeightSuffixChange = (id: number, value: boolean) => {
    setShowHeightSuffixMap(prev => ({ ...prev, [id]: value }));
  };

  return (
    <StyledBadgeRibbon text={ribbonText} color={ribbonColor}>
      <StyledCard className={`${active ? `active` : ""}`}>
        <Checkmark className="checkmark">
          <CheckOutlined style={{ color: "#FFF" }} />
        </Checkmark>
        {isEdit && (
          <StyledBadgeEdit
            title={ribbonText}
            text={<EditOutlined style={{ color: "#FFF" }} />}
          />
        )}
        <CloseButton onClick={handleClose}>x</CloseButton>
        <h6 style={{ padding: 0, margin: 0 }}>Enter Custom Size</h6>

        {sizeInputs.map((sizeInput, index) => (
          <EnterSizeWrapper key={index} gutter={isMobile ? [8, 8] : [20, 20]} justify={"center"} $hasMultipleSizes={sizeInputs.length > 1}>
            <Col xs={sizeInputs.length > 1 ? 6 : 7} sm={7} md={7} lg={7}>
              <Tooltip
                title={showWidthSuffixMap[sizeInput.id] ? tooltipText : ""}
                open={showWidthSuffixMap[sizeInput.id]}
                onOpenChange={(value) => handleWidthSuffixChange(sizeInput.id, value)}
                color="var(--primary-color)"
                overlayStyle={{ fontSize: "12px", width: "200px" }}
              >
                <InputSizeWidth
                  placeholder={isMobile ? "W (in.)" : "Enter Width (in.)"}
                  type="text"
                  inputMode="numeric"
                  min={1}
                  precision={0}
                  max={maxWidth}
                  value={isWidthZeroMap[sizeInput.id] ? undefined : sizeInput.width}
                  onKeyUp={(e: any) => {
                    handleWidthSuffixChange(sizeInput.id, e.target.value > maxWidth);
                  }}
                  onChange={(value: any) => handleSizeChange(Number(value), sizeInput.height, sizeInput.id)}
                  onKeyDown={(e: any) => {
                    const isNumericInput = /^[0-9\b]+$/;
                    if (!isNumericInput.test(e.key) && !["Backspace", "Delete", "ArrowLeft", "ArrowRight", "Tab", "Enter"].includes(e.key)) {
                      e.preventDefault();
                    }
                    if ((e.ctrlKey || e.metaKey) && e.key === 'a') {
                      e.preventDefault();
                      const inputElement = e.target;
                      inputElement.select();
                      return;
                    }
                    const notAllowedKeys = [".", "e", "-"];
                    if (notAllowedKeys.includes(e.key)) {
                      e.preventDefault();
                    }
                  }}
                />
                <Title $hasMultipleSizes={sizeInputs.length > 1}>{isMobile ? "Width (in.)" : "Enter Width (in.)"}
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
            <span className="cross">x</span>
            <Col xs={sizeInputs.length > 1 ? 6 : 7} sm={7} md={7} lg={7}>
              <Tooltip
                title={showHeightSuffixMap[sizeInput.id] ? tooltipText : ""}
                open={showHeightSuffixMap[sizeInput.id]}
                onOpenChange={(value) => handleHeightSuffixChange(sizeInput.id, value)}
                color="var(--primary-color)"
                overlayStyle={{ fontSize: "12px", width: "200px" }}
              >
                <InputSizeHeight
                  placeholder={isMobile ? "H (in.)" : "Enter Height (in.)"}
                  type="text"
                  inputMode="numeric"
                  min={1}
                  precision={0}
                  max={maxHeight}
                  value={isHeightZeroMap[sizeInput.id] ? undefined : sizeInput.height}
                  onKeyUp={(e: any) => {
                    handleHeightSuffixChange(sizeInput.id, e.target.value > maxHeight);
                  }}
                  onChange={(value: any) => handleSizeChange(sizeInput.width, Number(value), sizeInput.id)}
                  onKeyDown={(e: any) => {
                    const isNumericInput = /^[0-9\b]+$/;
                    if (!isNumericInput.test(e.key) && !["Backspace", "Delete", "ArrowLeft", "ArrowRight", "Tab", "Enter"].includes(e.key)) {
                      e.preventDefault();
                    }
                    if ((e.ctrlKey || e.metaKey) && e.key === 'a') {
                      e.preventDefault();
                      const inputElement = e.target;
                      inputElement.select();
                      return;
                    }
                    const notAllowedKeys = [".", "e", "-"];
                    if (notAllowedKeys.includes(e.key)) {
                      e.preventDefault();
                    }
                  }}
                />
                <Title $hasMultipleSizes={sizeInputs.length > 1}>{isMobile ? "Height (in.)" : "Enter Height (in.)"}
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
            <Col xs={sizeInputs.length > 1 ? 6 : 7} sm={7} md={7} lg={7}>
              <InputQuantity
                id={`custom-variant-qty-${index}`}
                className={'custom-variant-qty'}
                disabled={canvas.loading && canvas.item.productId !== productId}
                type="text"
                inputMode="numeric"
                placeholder={isMobile ? "Qty" : "Enter Qty"}
                min={0}
                precision={0}
                max={MAX_ALLOWED_QUANTITY}
                maxLength={MAX_ALLOWED_QUANTITY.toString().length}
                parser={(value: any) => parseInt(value).toFixed(0)}
                value={sizeInput.quantity}
                onChange={(value: any) => handleQuantityChange(Number(value), sizeInput.id)}
                onKeyUp={(e: any) => {
                  if (["Backspace", "Delete"].includes(e.key)) {
                    if (e.target.value.length <= 0) {
                      handleQuantityChange(0, sizeInput.id)
                    }
                  }
                }}
                onKeyDown={handleNumericKeyDown()}
                changeOnWheel={false}
              />
              <span className="bulk-discounts">Bulk Discounts!</span>
            </Col>
            {sizeInputs.length > 1 &&
              <Col xs={2} sm={1} md={1} lg={1}>
                <DeleteButton
                  type="primary"
                  shape="circle"
                  size="small"
                  icon={<DeleteOutlined />}
                  onClick={() => handleDeleteSizeInput(sizeInput.id)}
                />
              </Col>
            }
          </EnterSizeWrapper>
        ))}
        <AddAnotherSize size="small" type="default" onClick={addNewSizeInput} >
          + Add Size
        </AddAnotherSize>
        {sizeInputs.some(input => isBiggerSize(`${input.width}x${input.height}`)) &&
          <>
            <BiggerSizeMessage>
              <Freight />
                For sizes greater than 48x24 we will fold or score the sign with one to three creases to fit inside a standard box.
                Please click Free Freight if you would like to proceed with this option.
            </BiggerSizeMessage>
          </>
        }
      </StyledCard>
    </StyledBadgeRibbon>
  );
};

export default SingleVariant;