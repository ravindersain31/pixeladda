import React, { useEffect, useState } from "react";
import {
    StyledColorPicker,
    ColorPickerTrigger
} from './styled';
import {ColorPickerProps} from "antd/lib";

interface CustomColorPicker extends ColorPickerProps {
    icon?: React.ReactNode;
    onChange: (color: any) => void;
}

const ColorPicker = ({icon, value, onChange, disabled, ...rest}: CustomColorPicker) => {
    const [open, setOpen] = useState(false);

    let currentColor: string = value as string ?? '#EEE';
    if (value && typeof value !== 'string') {
        if (value.toHexString) {
            currentColor = value.toHexString();
        } else {
            currentColor = '#EEE';
        }
    }

    useEffect(() => {
        const handleClick = (event: MouseEvent) => {
            const target = event.target as HTMLElement;             
            if (!target.closest('.custom-lightgallery, .swiper, .lightgallery-item')) return;
            setOpen(false);
        };
    
        document.addEventListener('click', handleClick);
        return () => {
            document.removeEventListener('click', handleClick);
        };
    }, []); 

    return <StyledColorPicker
        open={open}
        onOpenChange={setOpen}
        children={
            <ColorPickerTrigger color={currentColor} disabled={disabled}>
                <div><span style={{background: currentColor}}>{icon}</span></div>
            </ColorPickerTrigger>
        }
        disabledAlpha={true}
        disabled={disabled}
        {...rest}
        onChangeComplete={onChange}
        presets={[
            {
                label: 'Recommended',
                colors: [
                    '#000000',
                    '#000000E0',
                    '#000000A6',
                    '#00000073',
                    '#00000040',
                    '#00000026',
                    '#0000001A',
                    '#00000012',
                    '#0000000A',
                    '#00000005',
                    '#F5222D',
                    '#FA8C16',
                    '#FADB14',
                    '#8BBB11',
                    '#52C41A',
                    '#13A8A8',
                    '#1677FF',
                    '#2F54EB',
                    '#722ED1',
                    '#EB2F96',
                    '#F5222D4D',
                    '#FA8C164D',
                    '#FADB144D',
                    '#8BBB114D',
                    '#52C41A4D',
                    '#13A8A84D',
                    '#1677FF4D',
                    '#2F54EB4D',
                    '#722ED14D',
                    '#EB2F964D',
                ],
            }
        ]}
    />
}

export default ColorPicker;