import React from 'react';
import Preview from './Preview';
import { Col, Row } from "antd";
import { SideName } from "./styled";

const CartItemSinglePreview = (props: any) => {
  const widthAndHeight = props.item.name.split('x');
  const templateSize = {
    width: parseInt(widthAndHeight[0]) || 12,
    height: parseInt(widthAndHeight[1]) || 12,
  }

  const sides = props.item?.addons?.sides || { key: "SINGLE" };

  return (
    <Row className="flex-center">
      <Col
        sm={sides.key !== "DOUBLE" ? 24 : 12}
        md={sides.key === "SINGLE" ? 12 : 12}
      >
        <SideName>
          <div>Front Side</div>
        </SideName>
        <Preview
          itemId={props.itemId}
          item={props.item}
          side="front"
          canvasData={props.canvasData?.front || {}}
          templateSize={templateSize}
        />
      </Col>

      {sides.key === "DOUBLE" && (
        <Col sm={12} md={12}>
          <SideName>
            <div>Back Side</div>
          </SideName>
          <Preview
            itemId={props.itemId}
            item={props.item}
            side="back"
            canvasData={props.canvasData?.back || {}}
            templateSize={templateSize}
          />
        </Col>
      )}
    </Row>
  );
}

export default CartItemSinglePreview;