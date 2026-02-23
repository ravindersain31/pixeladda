import React from 'react'
import {isMobile} from "react-device-detect";
import {Col, Row} from "antd";
import {ContactSupport} from "@react/editor/components/Steps/ChooseYourSizes/styled.tsx";
import {useAppSelector} from "@react/editor/hook.ts";
import AdditionalNote from "@react/editor/components/AdditionalNote";
import SingleSizeVariant from "./SingleSizeVariant";

const EnterQuantity = (
    {
        product,
        ribbons,
        sizeRibbonsColor,
        handleQuantityChange
    }: any) => {

    const config = useAppSelector(state => state.config);
    const editor = useAppSelector(state => state.editor);

    return <Row justify={isMobile ? 'center' : 'start'} style={{width: '100%'}}>
        {product.variants.map((item: any, index: number) => {
            const itemData = editor.items[item.productId];
            return (
                <React.Fragment key={item.productId}>
                    <Col key={index} xs={6} sm={10} md={8} lg={4}>
                        <SingleSizeVariant
                            title={item.name}
                            productId={item.productId}
                            ribbonText={ribbons[item.name] || ""}
                            ribbonColor={sizeRibbonsColor[item.name] || ""}
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
                    <Col xs={18} sm={14} md={16} lg={20}>
                        <ContactSupport style={{padding: 0, marginLeft: '5px'}}>
                            {isMobile && <p className="packed normal-text mobile">
                                <span style={{lineHeight: 1.5}}>
                                    Standard size is {item.label || item.name}. &nbsp;{config.product.productType.slug === 'yard-letters' && <span className="packed">Qty is total packs of signs and stakes.</span>}
                                    &nbsp;<span className="text"><a href="tel: +1-877-958-1499" className="text-primary">Call</a> or comment for custom sizes.</span>
                            </span>
                            </p>}
                            {!isMobile && <p className="packed normal-text">
                                <span>
                                    Standard size is {item.label || item.name}.&nbsp;{config.product.productType.slug === 'yard-letters' && <span className="packed">Quantity is total packs of signs and stakes.</span>}
                                    <br/>Call <a href="tel: +1-877-958-1499" className="text-primary">+1-877-958-1499</a>
                                    <span className="text"> or leave a comment for custom sizes.</span>
                                </span>
                            </p>}
                        </ContactSupport>
                    </Col>
                    <Col xs={24} md={24} lg={24}>
                        <AdditionalNote
                            showNeedAssistance={false}
                            showNoteMessage={false}
                        />
                    </Col>
                </React.Fragment>
            );
        })}
    </Row>
}

export default EnterQuantity