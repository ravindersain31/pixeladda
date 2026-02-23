import React, {useContext, useState} from 'react';
import {Row, Col} from 'antd';
import Header from './Header';
import Preview from './Preview';
import Customizer from "./Customizer";
import {SavingDesign} from "./styled.tsx";
import axios from "axios";
import CanvasContext from "@react/admin/editor/context/canvas.ts";
import { CanvasProperties } from "./canvas/utils.ts";

const AdminProductEditor = (props: any) => {

    const [saving, setSaving] = useState<boolean>(false);

    const canvasContext = useContext(CanvasContext);

    const onSave = async () => {
        if (canvasContext.canvas) {
            setSaving(true);
            try {
                const canvasData = canvasContext.canvas.toJSON(CanvasProperties);
                const {data} = await axios.post(props.saveUrl, {
                    canvasData,
                    imageDataURL: canvasContext.canvas.toDataURL(),
                })
                setSaving(false);
                if (data) {
                    alert(data.message);
                }
            } catch (err) {
                setSaving(false);
                alert('There was some issues in saving the template');
                console.log('err', err);
            }
        } else {
            alert('Not a valid Canvas Object to Save');
        }
    }
    return <>
        {saving && <SavingDesign>
            <div className="backdrop"/>
            <div className="text">
                Saving Canvas Data...<br/>
                <small>Don't Close this Window</small>
            </div>
        </SavingDesign>
        }
        <Row>
            <Col span={24}>
                <Header onSave={onSave} variant={props.variant}/>
            </Col>
            <Col sm={16}>
                <Preview
                    variant={props.variant}
                    templateJsonUrl={props.templateJsonUrl}
                />
            </Col>
            <Col sm={8} style={{padding: 20, background: '#fcfcfc'}}>
                <Customizer {...props}/>
            </Col>
        </Row>
    </>;
}

export default AdminProductEditor;