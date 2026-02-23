import React from 'react';

import TextEditor from "./TextEditor";
import SVGLoader from "./SVGLoader";
import Layer from "./Layer";
import ArtworkUploader from "./ArtworkUploader";

const CustomizeYourSigns = (props: any) => {
    return <>
        <SVGLoader {...props}/>
        <TextEditor/>
        <ArtworkUploader {...props}/>
        <Layer/>
    </>;
}

export default CustomizeYourSigns;