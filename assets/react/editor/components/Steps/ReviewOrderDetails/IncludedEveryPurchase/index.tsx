import React, { useState } from "react";
import {
  IncludedEveryPurchaseButton,
  IncludedItem,
  IncludedList,
  StyledModal,
  EveryPurchaseModal,
} from "./styled";
import AnchorLink from "antd/es/anchor/AnchorLink";
import { isPromoStore } from "@react/editor/helper/editor";

interface IncludedEveryPurchaseProps {
  items: any[];
}

const IncludedEveryPurchase = ({ items }: IncludedEveryPurchaseProps) => {
  
  const [isIncludedModalOpen, setIsIncludedModalOpen] = useState(false);

  return (
    <EveryPurchaseModal>
        <IncludedEveryPurchaseButton
          type="primary"
          onClick={() => setIsIncludedModalOpen(true)}
          className="included-every-purchase"
        >
          Included With Every Purchase
        </IncludedEveryPurchaseButton>

        <StyledModal
          title="Included With Every Yard Sign Product"
          open={isIncludedModalOpen}
          onCancel={() => setIsIncludedModalOpen(false)}
          footer={null}
          className="text-center"
        >
          <IncludedList>
            {items.map((item, index) => (
              <IncludedItem key={index}>
                <img
                  src={isPromoStore() ? "https://static.yardsignplus.com/storage/promo-store/Promo-Icon-Verification-Mark.svg" : "https://static.yardsignplus.com/assets/check-circle.png"}
                  alt="circle"
                  className={isPromoStore() ? 'promo-img' : 'normal-img' }
                />
                {item}
              </IncludedItem>
            ))}
            <AnchorLink href="/ysp-rewards" className="ysp-rewards" target="_blank" title="Earn 5% YSP Rewards" />
          </IncludedList>
        </StyledModal>
    </EveryPurchaseModal>
  );
};

export default IncludedEveryPurchase;
