import React, { useState, useRef } from 'react';
import { Input, Button, Typography, Row, Col, Space, message, Tag, Empty } from 'antd';
import QRCodeReact from 'react-qr-code';
import {
    DownloadOutlined,
    LinkOutlined,
    PhoneOutlined,
    MailOutlined,
    CheckCircleOutlined,
    QrcodeOutlined
} from '@ant-design/icons';
import { FadeInWrapper, GlassCard, ProtocolButton, QRPreviewContainer } from './styled';

const { Title, Paragraph } = Typography;

const QrGenerator: React.FC = () => {
    const [qrValue, setQrValue] = useState('');
    const qrRef = useRef<SVGSVGElement | null>(null);

    const downloadQr = () => {
        if (!qrRef.current) return;

        const svg = qrRef.current;
        const svgData = new XMLSerializer().serializeToString(svg);
        const canvas = document.createElement("canvas");
        const ctx = canvas.getContext("2d");
        const img = new Image();

        img.onload = () => {
            canvas.width = img.width * 2;
            canvas.height = img.height * 2;
            if (ctx) {
                ctx.fillStyle = 'white';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                const pngFile = canvas.toDataURL("image/png");

                const downloadLink = document.createElement("a");
                downloadLink.download = `qrcode-${Date.now()}.png`;
                downloadLink.href = `${pngFile}`;
                downloadLink.click();
                message.success('QR Code downloaded successfully!');
            }
            URL.revokeObjectURL(img.src);
        };

        const blob = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' });
        img.src = URL.createObjectURL(blob);
    };

    const getFormattedValue = (value: string) => {
        const trimmed = value.trim();
        if (!trimmed) return '';

        // If it already has a protocol, leave it alone
        if (/^[a-z]+:/i.test(trimmed)) return trimmed;

        // Email detection
        if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed)) {
            return `mailto:${trimmed}`;
        }

        // Phone detection
        if (/^\+?[\d\s\-()]{7,20}$/.test(trimmed)) {
            const digitsOnly = trimmed.replace(/[^\d+]/g, '');

            // If original contains +, return number only (no tel:)
            if (trimmed.includes('+')) {
                return digitsOnly;
            }

            return `tel:${digitsOnly}`;
        }

        // Default to http if it looks like a domain but has no protocol
        if (trimmed.includes('.') && !trimmed.startsWith('http')) {
            return `https://${trimmed}`;
        }

        return trimmed;
    };

    const formattedValue = getFormattedValue(qrValue);

    return (
        <GlassCard
            title={
                <Space size="middle">
                    <QrcodeOutlined style={{ color: '#1677ff', fontSize: '20px', verticalAlign: 'middle' }} />
                    <Title level={4} style={{ margin: 0, fontWeight: 700 }}>QR Code Generator</Title>
                </Space>
            }
        >
            <Row gutter={[32, 32]}>
                {/* Left Column: Input */}
                <Col xs={24} lg={14}>
                    <Space direction="vertical" size="large" style={{ width: '100%' }}>
                        <div>
                            <Paragraph strong style={{ marginBottom: 8 }}>
                                Content to Encode
                            </Paragraph>
                            <Input.TextArea
                                placeholder="Enter URL, phone (+1...), email, or any text here..."
                                autoSize={{ minRows: 4, maxRows: 8 }}
                                value={qrValue}
                                onChange={(e) => setQrValue(e.target.value)}
                                style={{ borderRadius: '12px', padding: '12px' }}
                            />
                        </div>
                    </Space>
                </Col>

                {/* Right Column: Preview & Controls */}
                <Col xs={24} lg={10}>
                    <div style={{ height: '100%', display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
                        <Paragraph strong style={{ width: '100%', textAlign: 'center', marginBottom: 16 }}>
                            Live Preview
                        </Paragraph>

                        {!qrValue ? (
                            <Empty
                                description="Start typing to generate..."
                                image={Empty.PRESENTED_IMAGE_SIMPLE}
                                style={{ margin: '40px 0' }}
                            />
                        ) : (
                            <FadeInWrapper>
                                <QRPreviewContainer>
                                    <QRCodeReact
                                        value={formattedValue}
                                        size={220}
                                        level="H"
                                        ref={(node: any) => {
                                            if (node && node instanceof SVGSVGElement) {
                                                qrRef.current = node;
                                            }
                                        }}
                                    />
                                    <div style={{ marginTop: '16px', textAlign: 'center' }}>
                                        <Tag color="processing" icon={<CheckCircleOutlined />}>
                                            {formattedValue}
                                        </Tag>
                                    </div>
                                </QRPreviewContainer>

                                <Space direction="vertical" style={{ width: '100%', marginTop: '24px' }} size="middle">
                                    <Button
                                        type="primary"
                                        icon={<DownloadOutlined />}
                                        onClick={downloadQr}
                                        block
                                        size="large"
                                        style={{ height: '50px', borderRadius: '12px', fontWeight: 600 }}
                                    >
                                        Download PNG
                                    </Button>
                                </Space>
                            </FadeInWrapper>
                        )}
                    </div>
                </Col>
            </Row>
        </GlassCard>
    );
};

export default QrGenerator;
