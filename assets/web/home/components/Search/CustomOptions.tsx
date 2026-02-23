import React, { useState } from 'react';
import { Button, Row, Col, Collapse, CollapseProps } from 'antd';
import { DownOutlined, UpOutlined } from '@ant-design/icons';
import { AccordionButton, StyledCollapse, StyledInnerCollapse, StyledAddonsCollapse } from './styled';
import { getSteps } from '../QuickQuote/Steps';
import { FormInstance } from 'antd/lib';
import { isMobile } from 'react-device-detect';

interface CustomOptionsProps {
    showGrommetColor: boolean;
    framePrices: { [key: string]: number };
    disallowedFrameForShape: boolean;
    showFrame: boolean;
    form: FormInstance;
    product: any;
}

const CustomOptions = ({ showGrommetColor, framePrices, disallowedFrameForShape, showFrame, form, product }: CustomOptionsProps) => {
    const [activeKey, setActiveKey] = useState<string | null>(null);
    const addons = form.getFieldsValue(["sides", "shape", "flute", "frame", "imprintColor", "grommets", "grommetColor"]);
    const steps = getSteps({ showGrommetColor, framePrices, disallowedFrameForShape, showFrame, label: false, addons, product })
    const [isCollapsed, setIsCollapsed] = useState(false);
    const [isInnerCollapsed, setIsInnerCollapsed] = useState(false);

    const handleHeaderClick = (key: string) => {
        setActiveKey(key === activeKey ? null : key);
    };

    const handleCollapseToggle = () => {
        setIsCollapsed(!isCollapsed);
    };

    const handleInnerCollapseToggle = () => {
        setIsInnerCollapsed(!isInnerCollapsed);
    };

    return (
        <>
            <StyledCollapse
                bordered={false}
                ghost
                $isCollapsed={isCollapsed}
                items={[{
                    key: "CustomOptions",
                    label: <>{isCollapsed ? 'Hide Options' : 'Show Options'}</>,
                    children: <>
                        {isMobile
                            ?
                            <StyledInnerCollapse
                                defaultActiveKey="1"
                                items={steps}
                                expandIconPosition='end'
                                bordered={false}
                                onChange={handleInnerCollapseToggle}
                                accordion
                            />
                            :
                            <>
                                <Row gutter={8} justify="center">
                                    {steps.map((step) => (
                                        <Col key={step.key} xs={24} sm={12} lg={12} xl={8}>
                                            <AccordionButton
                                                block
                                                $isActive={activeKey === step.key}
                                                onClick={() => handleHeaderClick(step.key)}
                                            >
                                                {step.label}
                                                {activeKey === step.key ? <UpOutlined /> : <DownOutlined />}
                                            </AccordionButton>
                                        </Col>
                                    ))}
                                </Row>
                                <StyledAddonsCollapse
                                    activeKey={activeKey ? [activeKey] : []}
                                    ghost
                                    bordered={false}
                                    size="small"
                                    accordion
                                >
                                    {steps.map((step) => (
                                        <Collapse.Panel key={step.key} header={null} showArrow={false}>
                                            {step.children}
                                        </Collapse.Panel>
                                    ))}
                                </StyledAddonsCollapse>
                            </>
                        }
                    </>,
                }]}
                onChange={handleCollapseToggle}
                expandIconPosition='end'
                expandIcon={() => isCollapsed ? <UpOutlined /> : <DownOutlined />}
            />
        </>
    );
};

export default CustomOptions;
