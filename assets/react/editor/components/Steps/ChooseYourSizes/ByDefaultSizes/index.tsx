import React from 'react';
import {Button, Col, Row} from "antd";
import {UpOutlined, DownOutlined} from '@ant-design/icons';
import SingleVariant from "@react/editor/components/Steps/ChooseYourSizes/SingleVariant.tsx";
import {ContactSupport, StyledCollapse, StyledColCustomSize, BiggerSizeMessage, AdditionalNoteBox } from "@react/editor/components/Steps/ChooseYourSizes/styled.tsx";
import {useAppSelector} from "@react/editor/hook.ts";
import CustomVariant from "./CustomVariant";
import Freight from '@react/editor/components/common/Freight';
import { hasBiggerSizeItem } from '@react/editor/helper/editor';
import AdditionalNote from "@react/editor/components/AdditionalNote";
import { isMobile } from 'react-device-detect';

const ByDefaultSizes = (
    {
        product,
        ribbons,
        sizeRibbonsColor,
        handleQuantityChange,
        showCustomVariant,
        handleToggleCustomVariant,
        showAllSizes,
        handleToggleViewMore
    }: any
) => {

    const editor = useAppSelector(state => state.editor);
    const config = useAppSelector(state => state.config);
    const { isYardLetters, isDieCut, isBigHeadCutouts } = config.product;

    const LiveChat = (event: React.MouseEvent) => {
        //@ts-ignore
        Tawk_API.toggle();
      };

    const defaultVisibleSizes = 4;
    const visibleVariants = showAllSizes ? product.variants : product.variants.slice(0, showAllSizes ? product.variants.length : defaultVisibleSizes);
    
    return <Row justify="center">
        {visibleVariants.map((item: any, index: number) => {
            const itemData = editor.items[item.id];
            return (
                <Col key={index} xs={12} sm={12} md={8} lg={showAllSizes ? 5 : 6}>
                    <SingleVariant
                        title={item.name}
                        label={item.label}
                        productId={item.productId}
                        ribbonText={ribbons[item.name] || []}
                        ribbonColor={sizeRibbonsColor[item.name] || []}
                        image={item.image}
                        isEdit={item.itemId !== null}
                        active={itemData && itemData.quantity > 0}
                        value={
                            itemData &&
                            (itemData.quantity <= 0 ? null : itemData.quantity)
                        }
                        onChange={(quantity: number) =>
                            handleQuantityChange(quantity, item)
                        }
                    />
                </Col>
            );
        })}

        {product.variants.length > defaultVisibleSizes && (
            <div className="text-center">
                <Button
                    type="link"
                    onClick={handleToggleViewMore}
                    size="middle"
                    className="d-flex justify-content-center align-items-center w-100"
                >
                    { showAllSizes ? "View Less Sizes" : "View More Sizes"}
                    { showAllSizes ? <UpOutlined /> : <DownOutlined />}
                </Button>
            </div>
        )}

        <StyledColCustomSize span={24} $active={showCustomVariant}>
            <Button type={showCustomVariant ? "primary" : "default"} onClick={handleToggleCustomVariant} size='middle'>Order Custom Sizes</Button>
        </StyledColCustomSize>
        {config.product.customVariant.length > 0 && (
            <Col xs={24} sm={24} md={16} lg={17}>
                <StyledCollapse
                    bordered={false}
                    items={[{
                        key: "ChooseCustomVariant",
                        children: <CustomVariant onClose={handleToggleCustomVariant}/>,
                    }]}
                    activeKey={showCustomVariant ? ["ChooseCustomVariant"] : []}
                    onChange={handleToggleCustomVariant}
                />
            </Col>
        )}

        { (hasBiggerSizeItem(editor.items) || (editor.isFreeFreight && isYardLetters && isDieCut && isBigHeadCutouts)) && (
            <Col xs={20} md={18} lg={18}>
                <BiggerSizeMessage>
                    <Freight />
                    For sizes greater than 48x24 we will fold or score the sign with one to three creases to fit inside a standard box.
                    Please click Free Freight if you would like to proceed with this option.
                </BiggerSizeMessage>
            </Col>
        )}
        {config.product.productType.slug === 'yard-letters' &&
            <Col xs={24} md={24} lg={24}>  
                <AdditionalNoteBox>
                    <AdditionalNote showNeedAssistance={false} showNoteMessage={false}/>
                </AdditionalNoteBox>  
            </Col>
        }
        <Col xs={24} md={24} lg={24}>
            <ContactSupport>
                <p>
                    {isYardLetters && (
                        <>LIMITED TIME ONLY: All Yard Letters SKUs are 20% off! Each pack includes multiple signs and stakes. Please review order details.</>
                    )}
                    {" "}For assistance call{" "}
                    <a href="tel:+1-877-958-1499" className="text-primary">
                        &nbsp;+1-877-958-1499&nbsp;
                    </a>
                    or begin <Button size='small' type='link' onClick={LiveChat}>&nbsp;Live Chat</Button>.
                </p>
            </ContactSupport>
        </Col>
    </Row>
}

export default ByDefaultSizes;