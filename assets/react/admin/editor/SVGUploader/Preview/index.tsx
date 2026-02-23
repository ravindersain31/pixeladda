import {PreviewWrapper} from "./styled";
import Canvas from "./Canvas";

interface PreviewProps {
    variant: object | any;
    templateJsonUrl: string;
    canvasContext: any
}

const Preview = (
    {
        variant,
        templateJsonUrl,
        canvasContext
    }: PreviewProps
) => {
    return (
        <PreviewWrapper>
            <Canvas variant={variant} canvasContext={canvasContext}  templateJsonUrl={templateJsonUrl} />
        </PreviewWrapper>
    );
}

export default Preview;