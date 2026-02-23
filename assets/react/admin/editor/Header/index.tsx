import React from 'react';
import {Row, Col} from "antd";
import {
    HeaderWrapper
} from "./styled.tsx";
import Button from "@react/admin/editor/components/Button";

const Header = ({onSave, variant}: any) => {

    return <HeaderWrapper id="page-header">
        <Row>
            <Col span={12}>
                <div style={{display: 'flex', justifyContent: 'space-between', alignItems: 'center'}}>
                    <h4>Design Studio</h4>
                    <h5 style={{margin: 0, color: '#FFF'}}>SKU: {variant.sku} ({variant.name})</h5>
                    <a style={{margin: 0, color: '#FFF'}} href={variant.editorUrl} target="_blank">Open In Editor</a>
                </div>
            </Col>
            <Col span={12} style={{textAlign: 'right'}}>
                <Button type="default" onClick={() => onSave()}>Save Design</Button>
            </Col>
        </Row>

    </HeaderWrapper>

}

export default Header;