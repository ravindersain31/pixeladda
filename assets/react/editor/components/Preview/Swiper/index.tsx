import React, {useContext, useEffect, useState} from "react";
import Slider from "@react/editor/components/Preview/Swiper/Slider";
import {useAppSelector} from "@react/editor/hook.ts";
import {buildImageList, isProductEditable} from "@react/editor/helper/template.ts";
import {getClosestProportionalVariantFromDefaultSizes} from "@react/editor/helper/size-calc.ts";
import ImagesSlider from "./ImagesSlider.tsx";
import CanvasContext from "@react/editor/context/canvas.ts";
import useShowCanvas from "@react/editor/hooks/useShowCanvas.tsx";

const Swiper = () => {
    const editor = useAppSelector((state) => state.editor);
    const config = useAppSelector((state) => state.config);
    const canvas = useAppSelector((state) => state.canvas);
    const [displayVariant, setDisplayVariant] = useState<string>('6x24');
    const [images, setImages] = useState<string[]>([]);
    const canvasContext = useContext(CanvasContext);
    const showCanvas = useShowCanvas();

    useEffect(() => {
        if (canvas.item.productId !== 0) {
            if(config.product.productImages && config.product.productImages.length > 0 && !isProductEditable(config)) {
                setImages(config.product.productImages);
            } else {
                const size = `${canvas.customSize.templateSize.width}x${canvas.customSize.templateSize.height}`
                const variantName = getClosestProportionalVariantFromDefaultSizes(size);

                if (displayVariant !== variantName || config.product.productImages.length <= 1 || config.product.isDieCut || config.product.isBigHeadCutouts || config.product.isHandFans) {
                    const defaultImages = buildImageList(variantName);

                    const imageList: string[] = [
                        ...(config.product.isDieCut ? defaultImages.DieCutImages : []),
                        ...(config.product.isBigHeadCutouts ? defaultImages.BigHeadCutouts : []),
                        ...(config.product.isHandFans ? defaultImages.HandFansImages : []),
                        ...defaultImages.primaryImages.withWireStake[variantName] ?? [],
                        ...defaultImages.primaryImages.withoutWireStake[variantName] ?? [],
                        ...defaultImages.secondaryImages.withWireStake[variantName] ?? [],
                        ...defaultImages.secondaryImages.withoutWireStake[variantName] ?? [],
                    ];
                    setImages(imageList);
                    setDisplayVariant(variantName);
                }
            }
        }
    }, [canvas.customSize.templateSize, config.product.productImages, config.product.isDieCut, config.product.isBigHeadCutouts]);

    return (
        <>
            {!showCanvas ? (
                <Slider images={images} />
            ) : (
                <ImagesSlider images={images} />
            )}
        </>
    );
};

export default Swiper;
