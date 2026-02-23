import React from 'react';
import Preview from './Preview';
import { Col, Row } from 'antd';

const CartItemPreview = (props: any) => {
    const widthAndHeight = props.name.split('x');
    const templateSize = {
        width: parseInt(widthAndHeight[0]) || 12,
        height: parseInt(widthAndHeight[1]) || 12,
    }
    return <>
        <Row className='flex-center justify-content-center' gutter={[8, 8]}>
            <Col>
                <Preview
                    itemId={props.itemId}
                    item={props.item}
                    side={'front'}
                    canvasData={props.canvasData.front}
                    templateSize={templateSize}
                />
            </Col>
            {props.item.addons.sides.key === 'DOUBLE' && <Col>
                <Preview
                    itemId={props.itemId}
                    item={props.item}
                    side={'back'}
                    canvasData={props.canvasData.back}
                    templateSize={templateSize}
                />
            </Col>}
        </Row>
    </>;
}

export default CartItemPreview;