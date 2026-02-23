import React, { useContext, useState, useRef, useEffect } from 'react';
import { Input, Row, Col, InputRef } from 'antd';
import CanvasContext from '@react/editor/context/canvas.ts';
import fabric from '@react/editor/canvas/fabric.ts';
import QRCodeReact from 'react-qr-code';
import { AddQrCodeContainer, StyledModal, StyledButton } from './styled';

const QrSize = 100;
const MaxInputLength = 100;

interface AddQrModalProps {
    visible: boolean;
    onClose: () => void;
}

const AddQrModal: React.FC<AddQrModalProps> = ({ visible, onClose }) => {
    const { canvas } = useContext(CanvasContext);
    const [qrText, setQrText] = useState('');
    const [error, setError] = useState<string | null>(null);
    const inputRef = useRef<InputRef | null>(null);
    const hiddenQrRef = useRef<SVGSVGElement | null>(null);
    const qrObjectRef = useRef<fabric.Image | null>(null);

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

    const isValidInput = (str: string): boolean => {
        if (!str || str.trim() === '') {
            setError('Please enter a valid URL, phone number, or email.');
            return false;
        }

        const trimmed = str.trim();

        // If it's a URL (contains . and no @)
        if (trimmed.includes('.') && !trimmed.includes('@')) {
            // Basic URL validation
            try {
                const normalized = /^[a-zA-Z][a-zA-Z\d+\-.]*:\/\//.test(trimmed) ? trimmed : `https://${trimmed}`;
                const urlObj = new URL(normalized);
                if (urlObj.hostname.includes('.')) {
                    setError(null);
                    return true;
                }
            } catch (e) {
                // fall through to other checks
            }
        }

        // If it's an email
        if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed) || trimmed.startsWith('mailto:')) {
            setError(null);
            return true;
        }

        // If it's a phone
        if (/^\+?[\d\s\-()]{7,20}$/.test(trimmed) || trimmed.startsWith('tel:')) {
            setError(null);
            return true;
        }

        // If none of the above, just allow it as plain text if it's not empty
        setError(null);
        return true;
    };

    const handleAddQr = () => {
        const value = qrText.trim();
        if (!isValidInput(value)) {
            return;
        }

        setError(null);

        if (!(canvas instanceof fabric.Canvas)) {
            console.error('Canvas not ready');
            return;
        }

        if (!hiddenQrRef.current) {
            console.error('Hidden QR SVG not rendered');
            return;
        }

        const svgNode = hiddenQrRef.current;
        const serializer = new XMLSerializer();
        const svgString = serializer.serializeToString(svgNode);

        const svgBlob = new Blob([svgString], { type: 'image/svg+xml;charset=utf-8' });
        const reader = new FileReader();
        reader.onload = (ev) => {
            const dataUrl = ev.target?.result as string;

            fabric.Image.fromURL(dataUrl, (img) => {
                const canvasWidth = canvas.getWidth();
                const canvasHeight = canvas.getHeight();
                const scaleX = QrSize / (img.width || QrSize);
                const scaleY = QrSize / (img.height || QrSize);

                img.set({
                    left: (canvasWidth - QrSize) / 2,
                    top: (canvasHeight - QrSize) / 2,
                    scaleX,
                    scaleY,
                    selectable: true,
                });

                canvas.add(img);
                canvas.setActiveObject(img);
                canvas.requestRenderAll();

                qrObjectRef.current = img;

                setQrText('');
                onClose();
            });
        };
        reader.readAsDataURL(svgBlob);
    };

    const handleCancel = () => {
        setError(null);
        setQrText('');
        onClose();
    };

    const formattedValue = getFormattedValue(qrText);

    function blurAllFocusable() {
        const focusableElements = document.querySelectorAll<HTMLElement>(
            'a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])'
        );

        focusableElements.forEach(el => {
            el.setAttribute('tabindex', '-1');
            el.blur();
        });
    }

    useEffect(() => {
        if (visible) {
            (document.activeElement as HTMLElement)?.blur();

            setTimeout(() => {
                blurAllFocusable();
                inputRef.current?.focus({ preventScroll: true });
            }, 300);
        }
    }, [visible]);


    return (
        <StyledModal
            open={visible}
            title="Add QR Code"
            onCancel={handleCancel}
            footer={null}
            width={450}
            zIndex={9999}
            destroyOnClose
            centered
        >
            <Row justify="center" align="middle" gutter={[16, 16]} style={{ width: '100%' }}>
                <Col span={24}>
                    <Input
                        ref={inputRef}
                        placeholder="URL, Phone (+1...), or Email"
                        value={qrText}
                        onChange={(e) => {
                            const v = e.target.value;
                            if (v.length > MaxInputLength) {
                                return;
                            }
                            setQrText(v);
                            if (error) setError(null);
                        }}
                        onPressEnter={handleAddQr}
                    />
                    {error && (
                        <div style={{ color: 'red', marginTop: 4, fontSize: 12 }}>
                            {error}
                        </div>
                    )}
                </Col>
                <Col span={24}>
                    <StyledButton
                        type="primary"
                        onClick={handleAddQr}
                    >
                        Generate QR Code
                    </StyledButton>
                </Col>
            </Row>

            <AddQrCodeContainer>
                <QRCodeReact
                    value={formattedValue || ' '}
                    size={QrSize}
                    level="M"
                    ref={(node: any) => {
                        if (node && node instanceof SVGSVGElement) {
                            hiddenQrRef.current = node;
                        }
                    }}
                />
            </AddQrCodeContainer>
        </StyledModal>
    );
};

export default AddQrModal;
