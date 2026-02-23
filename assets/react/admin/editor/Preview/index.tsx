import {useEffect} from "react";
import useCanvas from "@react/admin/editor/hooks/useCanvas.tsx";
import {PreviewWrapper} from "./styled";
import Canvas from "./Canvas";

interface PreviewProps {
    variant: object | any;
    templateJsonUrl: string;
}

const Preview = (
    {
        variant,
        templateJsonUrl
    }: PreviewProps
) => {
    const canvas = useCanvas();

    useEffect(() => {
        (async () => {
            await loadTemplate();
        })()
    }, []);

    const loadTemplate = async () => {
        let template: any = {};
        const ext = templateJsonUrl.split('.').pop();
        if (ext === 'json') {
            const response = await fetch(templateJsonUrl)
            template = await response.json();
            if (template.overlayImage) {
                delete template.overlayImage;
            }
        }

        canvas.loadFromJSON(template, variant.templateSize, (data) => {

        })
    }

    return (
        <PreviewWrapper>
            <Canvas/>
        </PreviewWrapper>
    );
}

export default Preview;