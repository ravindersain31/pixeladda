import {useContext, useState} from "react";
import {
    SVGContainer,
    SVGLoadButton,
    SVGLoaderForm,
    SVGLoaderInput
} from "@react/admin/editor/Customizer/SVGLoader/styled.tsx";
import fabric from "@react/admin/editor/canvas/fabric.ts";
import useCanvas from "@react/admin/editor/hooks/useCanvas.tsx";
import CanvasContext from "@react/admin/editor/context/canvas.ts";
import FontFaceObserver from "fontfaceobserver";
import {fitObjectsToCanvas} from "@react/admin/editor/canvas";
import { sanitizeFontFamily } from "../../canvas/utils";

const SVGLoader = (props: any) => {

    const [file, setFile] = useState<any>(null);

    const [loading, setLoading] = useState<boolean>(false);

    const canvasContext = useContext(CanvasContext);

    const canvasHook = useCanvas();

    const onFileChange = (e: any) => {
        const file = e.target.files[0];
        setFile(file);
    }

    const onLoad = () => {
        const ext = file.name.split('.').pop();
        if (ext !== 'svg') {
            alert('Please select SVG file');
        } else {
            setLoading(true);
            const reader = new FileReader();
            reader.onload = (e: any) => {
                const svg = e.target.result;
                renderSVGOnCanvas(svg);
            }
            reader.readAsText(file);
        }
    }

    const renderSVGOnCanvas = (svg: any) => {
        fabric.loadSVGFromString(svg, (_objects, options) => {
            prepareObjects(_objects).then((objects: any) => {
                const group = new fabric.Group(objects, options);
                canvasContext.canvas.add(group);
                canvasContext.canvas.setActiveObject(group);
                ungroupObjects();
                canvasContext.canvas.discardActiveObject();
                canvasContext.canvas.requestRenderAll();
                canvasHook.autoResizeCanvas(props.variant.templateSize, true);
                setLoading(false);
            });
        });
    }

    const prepareObjects = (objects: any) => {
        return new Promise((resolve, reject) => {
            let promises = [];
            for (const object of objects) {
                promises.push(new Promise((resolve, reject) => {
                    if (object.type === 'image') {
                        const imageData = getImageDataFromObject(object);
                        if (imageData) {
                            uploadImageFromDataURL(imageData).then((imageUrl) => {
                                object['xlink:href'] = '';
                                object.src = imageUrl;
                                fabric.util.loadImage(imageUrl as string, (img) => {
                                    object.setElement(img);
                                    object.setCoords();
                                    resolve(object);
                                });
                            }).catch((e) => {
                                resolve(object);
                            });
                        } else {
                            resolve(object);
                        }
                    } else if (object.type === 'text' || object.type === 'i-text') {
                        object.text = object.text.trim();
                        object.fontFamily = sanitizeFontFamily(object.fontFamily); 
                        new FontFaceObserver(object.fontFamily).load().then(() => {
                            resolve(object);
                        }).catch((e: any) => {
                            resolve(object);
                        });
                    } else {
                        resolve(object);
                    }
                }));
            }
            Promise.all(promises).then((objects) => {
                resolve(objects);
            });
        });
    }

    const uploadImageFromDataURL = (imageData: any) => {
        return new Promise((resolve, reject) => {
            const data = new FormData();
            data.append('file', imageData);
            fetch(props.uploadDataImage, {
                method: 'POST',
                body: data
            }).then((res) => {
                res.json().then((data) => {
                    resolve(data.imageUrl);
                }).catch((e) => {
                    reject(e);
                });
            }).catch((e) => {
                reject(e);
            });
        });
    }

    const getImageDataFromObject = (object: any) => {
        let imageData = '';
        if (object['src']) {
            imageData = object['src'];
        } else if (object['xlink:href']) {
            imageData = object['xlink:href'];
        }

        const pattern = new RegExp("^data:image\/(?:gif|png|jpeg|bmp|webp)(?:;charset=utf-8)?;base64,(?:[A-Za-z0-9 ]|[+/])+={0,2}$");
        if (!pattern.test(imageData)) {
            return null;
        }
        return imageData;
    }

    const ungroupObjects = () => {
        const activeObject = canvasContext.canvas.getActiveObject();
        if (activeObject && activeObject instanceof fabric.Group && activeObject.type === 'group') {
            const items = activeObject.getObjects();
            activeObject._restoreObjectsState();
            canvasContext.canvas.remove(activeObject);
            for (let i = 0; i < items.length; i++) {
                canvasContext.canvas.add(items[i]);
            }
            canvasContext.canvas.renderAll();
        }
    }

    return <SVGContainer>
        <label>Select Template SVG</label>
        <SVGLoaderForm>
            <SVGLoaderInput type="file" onChange={onFileChange}/>
            <SVGLoadButton type="default" onClick={() => onLoad()} disabled={loading}>
                {loading ? 'Loading...' : 'Load'}
            </SVGLoadButton>
        </SVGLoaderForm>
    </SVGContainer>
}

export default SVGLoader;