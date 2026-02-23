
export const calculateARatio = (width: number, height: number) => {
    const gcd: any = (a: number, b: number) => (b === 0 ? a : gcd(b, a % b));
    const aspectRatioGCD = gcd(width, height);
    return {
        width: width / aspectRatioGCD,
        height: height / aspectRatioGCD,
    };
}

export const calculateCanvasDimensions = (element: HTMLCanvasElement, templateSize: any) => {
    const apr = calculateARatio(templateSize.width, templateSize.height);

    const previewWrapper = element?.parentNode?.parentNode as HTMLDivElement;

    const preview = {
        width: previewWrapper.offsetWidth,
        height: previewWrapper.offsetHeight,
    }


    let canvasWidth, canvasHeight = 0;
    if (apr.width > apr.height) {
        canvasWidth = preview.width;
        canvasHeight = preview.width * (apr.height / apr.width);
    } else if (apr.height > apr.width) {
        canvasWidth = preview.height * (apr.width / apr.height);
        canvasHeight = preview.height;
    } else if (preview.height > preview.width) {
        canvasWidth = preview.width;
        canvasHeight = preview.width * (apr.width / apr.height);
    } else {
        canvasWidth = preview.height * (apr.width / apr.height);
        canvasHeight = preview.height;
    }

    return {
        width: canvasWidth,
        height: canvasHeight,
    };
}