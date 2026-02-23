import React from "react";
import { Button, Typography } from "antd";
import { ContentWrapper, Image, HeaderWrapper, BodyWrapper, FooterWrapper, StyledCheckmark, StyledButton, ShippingMethod } from "./styled";
import { isMobile } from "react-device-detect";
import { useAppDispatch, useAppSelector } from "@react/editor/hook.ts";
import actions from "@react/editor/redux/actions";
import { CheckOutlined } from "@ant-design/icons";
import { getStoreInfo } from "@react/editor/helper/editor";

const { Text, Paragraph, Title } = Typography;

const FreightPopover = () => {
  const editor = useAppSelector((state) => state.editor);
  const dispatch = useAppDispatch();

  const toggleFreeFreight = (e: React.MouseEvent) => {
    dispatch(actions.editor.updateFreeFreight(!editor.isFreeFreight));
  };

  const LiveChat = (event: React.MouseEvent) => {
    //@ts-ignore
    Tawk_API.toggle();
  };
  const storeEmail = getStoreInfo().storeEmail;
  const storeYardLogo = getStoreInfo().storeYardLogo;

  return (
    <ContentWrapper>
      <HeaderWrapper onClick={(e: React.MouseEvent) => e.stopPropagation()}>
        <img loading="lazy" width={85} height={53} src={storeYardLogo} alt="Logo" />
        <Title className="text-white" level={4}>FREE FREIGHT: {isMobile && <br />} SCORING & FOLDING METHOD</Title>
      </HeaderWrapper>
      <BodyWrapper onClick={(e: React.MouseEvent) => e.stopPropagation()}>
        <Title level={4}>SCORING & FOLDING</Title>
        <Paragraph>
          <Text className="freight" strong>Free Freight:</Text>
          <span className="description">We will fold or score the sign with one to three creases to fit inside a standard box.
          Please click</span>
          <ShippingMethod className={`${editor.isFreeFreight ? `active` : ""}`}>
            <StyledButton
              size="small"
              type="link"
              onClick={toggleFreeFreight}
              $disabled={!editor.isFreeFreight}
              className={`${editor.isFreeFreight ? "active" : ""}`}
            >
              <StyledCheckmark className={editor.isFreeFreight ? "checkmark" : ""}>
                <CheckOutlined style={{ color: "#fff" }} />
              </StyledCheckmark>
              Free Freight
            </StyledButton>
          </ShippingMethod> if you would like to proceed with this option.
        </Paragraph>
        <Image onClick={(e: React.MouseEvent) => e.stopPropagation()} width={670} height={336} src="https://yardsignplus-static.s3.amazonaws.com/storage/images/freight.webp" alt="Scoring and Folding Example" />
      </BodyWrapper>
      <FooterWrapper onClick={(e: React.MouseEvent) => e.stopPropagation()}>
        <Title level={5} onClick={(e) => e.stopPropagation()}>
          For questions call <a href="tel: +1-877-958-1499">+1-877-958-1499</a>, email <a href={`mailto:${storeEmail}`}>{storeEmail}</a>,
          or message us on our <Button size="small" type="link" onClick={LiveChat} aria-label="live chat">live chat</Button>.
        </Title>
      </FooterWrapper>
    </ContentWrapper>
  );
};

export default FreightPopover;