import React, { useEffect, useState } from "react";
import { CheckOutlined, QuestionCircleOutlined } from "@ant-design/icons";
import { Button, Modal, Popover } from "antd";
import { useAppDispatch, useAppSelector } from "@react/editor/hook.ts";
import actions from "@react/editor/redux/actions";
import {
  StyledButton,
  StyledCheckmark,
  PopoverContent,
  ShippingMethod,
  StyledModal,
} from "./styled";
import FreightPopover from "./Popover";

const Freight = () => {
  const editor = useAppSelector((state) => state.editor);
  const canvas = useAppSelector((state) => state.canvas);
  const dispatch = useAppDispatch();
  const [isModalVisible, setIsModalVisible] = useState(false);

  const showModal = (e: React.MouseEvent, value: boolean) => {
    setIsModalVisible(value);
    if (!value) {
      e.stopPropagation();
    }
  };

  const toggleFreeFreight = () => {
    dispatch(actions.editor.updateFreeFreight(!editor.isFreeFreight));
  };

  return (
    <ShippingMethod className={`${editor.isFreeFreight ? `active` : ""}`}>
      <StyledButton
        $disabled={!editor.isFreeFreight}
        onClick={toggleFreeFreight}
        className={`${editor.isFreeFreight ? "active" : ""}`}
      >
        <StyledCheckmark className={editor.isFreeFreight ? "checkmark" : ""}>
          <CheckOutlined style={{ color: "#fff" }} />
        </StyledCheckmark>
        Free Freight
        <Button
          shape="circle"
          icon={<QuestionCircleOutlined />}
          onMouseOver={(e: React.MouseEvent) => showModal(e, true)}
        />
        <StyledModal
          open={isModalVisible}
          footer={null}
          onCancel={(e: React.MouseEvent) => showModal(e, false)}
          closable={true}
          mask={true}
          width={700}
        >
          <FreightPopover />
        </StyledModal>
      </StyledButton>
    </ShippingMethod>
  );
};

export default Freight;