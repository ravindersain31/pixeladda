import ChooseYourSizes from './ChooseYourSizes';
import CustomizeYourSigns from './CustomizeYourSigns';
import ChooseYourSides from './ChooseYourSides';
import ChooseImprintColor from './ChooseImprintColor';
import ChooseYourGrommets from './ChooseYourGrommets';
import ChooseGrommetColor from './ChooseGrommetColor';
import ChooseYourFrame from './ChooseYourFrame';
import ChooseDeliveryDate from './ChooseDeliveryDate';
import ReviewOrderDetails from './ReviewOrderDetails';
import {useContext, useEffect, useState} from "react";
import {useAppDispatch, useAppSelector} from "@react/editor/hook.ts";
import {
    Flute,
    Frame,
    Grommets,
    ProductTypeSlug,
    productTypeStepsConfig,
    Shape,
    StepConfigProps
} from "@react/editor/redux/reducer/editor/interface.ts";
import ChooseDesignOption from './ChooseDesignOption';
import ChooseYourShape from './ChooseYourShape';
import AddYourArtwork from './AddYourArtwork';
import CanvasContext from '@react/editor/context/canvas';
import useShowCanvas from '@react/editor/hooks/useShowCanvas';
import ChooseYourFlute from './ChooseYourFlute';
import actions from "@react/editor/redux/actions";

const Steps = () => {
    const config = useAppSelector(state => state.config);
    const editor = useAppSelector(state => state.editor);
    const canvas = useAppSelector(state => state.canvas);
    const canvasContext = useContext(CanvasContext);
    const showCanvas = useShowCanvas(); 
    const dispatch = useAppDispatch();

    const [stepConfig, setStepConfig] = useState<StepConfigProps>(productTypeStepsConfig.default);

    useEffect(() => {
        const productTypeSlug: ProductTypeSlug = config.product.productType.slug as ProductTypeSlug;
        const newStepConfig = productTypeStepsConfig[productTypeSlug] || productTypeStepsConfig.default;
        setStepConfig(newStepConfig);
    }, [config.product.productType.slug]);

    const handleFrameVisibility = (
        flute: Flute,
        frame: Frame | Frame[],
        newStepConfig: StepConfigProps,
        dispatch: any
    ) => {
        if (flute !== Flute.VERTICAL) {
            newStepConfig.ChooseYourFrame.show = false;
            if (frame !== Frame.NONE) {
                dispatch(actions.editor.updateFrame(Frame.NONE));
            }
        } else {
            newStepConfig.ChooseYourFrame.show = true;
        }
    };

    const useProductTypeEffects = (productTypeSlug: ProductTypeSlug) => {
        useEffect(() => {
            const newStepConfig = { ...stepConfig };
            const isYardLetters = productTypeSlug === 'yard-letters';
            const isDieCut = productTypeSlug === 'die-cut';
            const isBigHeadCutouts = productTypeSlug === 'big-head-cutouts';
            const isHandFans = productTypeSlug === 'hand-fans';
            const showCustomizeYourSigns = isDieCut || isBigHeadCutouts || isHandFans;

            if (isYardLetters || isDieCut || isBigHeadCutouts || isHandFans) {
                newStepConfig.ChooseYourShape.show = false;
                newStepConfig.CustomizeYourSigns.show = showCustomizeYourSigns;
                newStepConfig.ChooseYourGrommets.show = true;
                newStepConfig.ChooseGrommetColor.show = editor.grommets !== Grommets.NONE;
                newStepConfig.ChooseYourFrame.show = false;
                newStepConfig.ChooseYourFlute.show = false;

                if (isDieCut || isBigHeadCutouts || isHandFans) {
                    handleFrameVisibility(editor.flute, editor.frame, newStepConfig, dispatch);
                    newStepConfig.ChooseYourFlute.show = true;
                }
                
                if(isYardLetters){
                    newStepConfig.ChooseImprintColor.show = false;
                }
                
                if(isHandFans){
                    newStepConfig.ChooseYourFrame.show = false;
                    newStepConfig.ChooseYourFlute.show = false;
                }
            } else {
                newStepConfig.ChooseYourShape.show = true;
                newStepConfig.ChooseYourGrommets.show = true;
                newStepConfig.CustomizeYourSigns.show = showCanvas;
                newStepConfig.ChooseGrommetColor.show = editor.grommets !== Grommets.NONE;
                handleFrameVisibility(editor.flute, editor.frame, newStepConfig, dispatch);
                newStepConfig.ChooseYourFlute.show = true;
            }

            reIndexStepConfig(newStepConfig);
        }, [config.product.isCustom, editor.grommets, editor.items, productTypeSlug, showCanvas]);
    };

    useProductTypeEffects(config.product.productType.slug as ProductTypeSlug);


    const reIndexStepConfig = (config: StepConfigProps) => {
        let index = 1;
        for (const [key, value] of Object.entries(config)) {
            if (value.show) {
                value.stepNumber = index;
                index++;
            }
        }
        setStepConfig(config);
    }

    return (
        <>
            {stepConfig.ChooseYourSizes.show && <ChooseYourSizes stepNumber={stepConfig.ChooseYourSizes.stepNumber} />}
            {stepConfig.ChooseYourSides.show && <ChooseYourSides stepNumber={stepConfig.ChooseYourSides.stepNumber} />}
            {stepConfig.ChooseDesignOption.show && <ChooseDesignOption stepNumber={stepConfig.ChooseDesignOption.stepNumber} />}
            {stepConfig.CustomizeYourSigns.show && showCanvas && (
                <CustomizeYourSigns stepNumber={stepConfig.CustomizeYourSigns.stepNumber} />
            )}
            {stepConfig.ChooseYourShape.show && <ChooseYourShape stepNumber={stepConfig.ChooseYourShape.stepNumber} />}
            {stepConfig.ChooseImprintColor.show && <ChooseImprintColor stepNumber={stepConfig.ChooseImprintColor.stepNumber} />}
            {stepConfig.ChooseYourGrommets.show && <ChooseYourGrommets stepNumber={stepConfig.ChooseYourGrommets.stepNumber} />}
            {stepConfig.ChooseGrommetColor.show && (
                <ChooseGrommetColor stepNumber={stepConfig.ChooseGrommetColor.stepNumber} />
            )}
            {stepConfig.ChooseYourFlute.show && (
                <ChooseYourFlute stepNumber={stepConfig.ChooseYourFlute.stepNumber} />
            )}
            {stepConfig.ChooseYourFrame.show && (
                <ChooseYourFrame stepNumber={stepConfig.ChooseYourFrame.stepNumber} />
            )}
            {stepConfig.ChooseDeliveryDate.show && <ChooseDeliveryDate stepNumber={stepConfig.ChooseDeliveryDate.stepNumber} />}
            {stepConfig.ReviewOrderDetails.show && <ReviewOrderDetails stepNumber={stepConfig.ReviewOrderDetails.stepNumber} />}
        </>
    );
}

export default Steps;