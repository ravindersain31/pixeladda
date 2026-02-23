import {Radio, Col} from "antd";
import {useAppDispatch, useAppSelector} from "@react/editor/hook.ts";
import StepCard from '@react/editor/components/Cards/StepCard';
import RadioButton from "@react/editor/components/Radio/RadioButton";
import AddonCard from "@react/editor/components/Cards/AddonCard";
import actions from "@react/editor/redux/actions";
import {GrommetColor} from "@react/editor/redux/interface.ts";
import {StepProps} from "../interface.ts";
import {useEffect, useMemo, useState} from "react";
import { AddOnPrices, AddOnProps } from "@react/editor/redux/reducer/editor/interface.ts";
import { grommetColorImages } from "@react/editor/helper/addonGrommetColorImages.ts";

const ChooseGrommetColor = ({stepNumber}: StepProps) => {
    const config = useAppSelector(state => state.config);
    const canvas = useAppSelector(state => state.canvas);
    const item = useAppSelector(state => state.canvas.item);

    const items = useAppSelector(state => state.editor.items);

    const [grommetColor, setGrommetColor] = useState<string | AddOnProps>(GrommetColor.SILVER);
    const searchParams = new URLSearchParams(window.location.search);
    const urlGrommetColor = searchParams.get('grommetColor');
    const dispatch = useAppDispatch();
    const currentItem = items[canvas.item.id];
    const addonImages = useMemo(() => grommetColorImages(currentItem), [currentItem]);

    useEffect(() => {
      if (urlGrommetColor) {
        dispatch(actions.editor.updateGrommetColor(urlGrommetColor));
      }
    }, []);

    useEffect(() => {
        if (items[item.id] && items[item.id].addons.frame) {
            setGrommetColor(items[item.id].addons.grommetColor.key);
        }
    }, [items])

    const onGrommetColorChange = (colorName: string) => {
        setGrommetColor(colorName);
        dispatch(actions.editor.updateGrommetColor(colorName));
        dispatch(actions.editor.updatePrePackedDiscount());
    }

    return <StepCard title="Choose Grommets Color" stepNumber={stepNumber}>
        <Radio.Group
            className="ant-row"
            value={grommetColor}
            onChange={(e) => onGrommetColorChange(e.target.value)}
        >
            <Col xs={12} sm={12} md={8} lg={6}>
                <RadioButton value={GrommetColor.SILVER}>
                    <AddonCard
                        title="Silver"
                        imageUrl={addonImages[GrommetColor.SILVER](config.product)}
                        ribbonText={AddOnPrices.GROMMET_COLOR[GrommetColor.SILVER] === 0 ? 'FREE' : AddOnPrices.GROMMET_COLOR[GrommetColor.SILVER]}
                        ribbonColor={'#1B8A1B'}
                    />
                </RadioButton>
            </Col>
            {/* <Col xs={12} sm={12} md={8} lg={6}>
                <RadioButton value={GrommetColor.BLACK}>
                    <AddonCard
                        title="Black"
                        imageUrl={addonImages[GrommetColor.BLACK](config.product)}
                        ribbonText={"+" + AddOnPrices.GROMMET_COLOR[GrommetColor.BLACK] + "%"}
                        ribbonColor={'#1d4e9b'}
                    />
                </RadioButton>
            </Col> */}
            <Col xs={12} sm={12} md={8} lg={6}>
                <RadioButton value={GrommetColor.GOLD}>
                    <AddonCard
                        title="Gold"
                        imageUrl={addonImages[GrommetColor.GOLD](config.product)}
                        ribbonText={"+" + AddOnPrices.GROMMET_COLOR[GrommetColor.GOLD] + "%"}
                        ribbonColor={'#1d4e9b'}
                    />
                </RadioButton>
            </Col>
        </Radio.Group>
    </StepCard>
}

export default ChooseGrommetColor;