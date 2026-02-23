import {useState} from "react";
import {
    MessageBox,
    SVGContainer,
    SVGLoaderForm,
    SVGLoaderInput,
} from "@react/admin/editor/SVGUploader/SVGLoader/styled.tsx";
import fabric from "@react/admin/editor/canvas/fabric.ts";
import FontFaceObserver from "fontfaceobserver";
import axios from "axios";
import { CanvasProperties, sanitizeFontFamily } from "../../canvas/utils";
import { autoResizeCanvas } from "../utils";

const SVGLoader = (props: any) => {

    const [file, setFile] = useState<any>(null);
    const [saving, setSaving] = useState<boolean>(false);    
    const [inputKey, setInputKey] = useState(0);
    const [message, setMessage] = useState<string>("");
    const [messageType, setMessageType] = useState<"info" | "error" | "success">("info");

    const canvasContext = props.canvasContext;

    const onFileChange = (e: any) => {
        const file = e.target.files[0];
        setFile(file);
        showMessage('');

        const ext = file.name.split('.').pop();
        if (ext !== 'svg') {
            showMessage("Please select SVG file.", "error");
            setInputKey(prev => prev + 1);
            setSaving(false);
            return;
        } else {
            const reader = new FileReader();
            reader.onload = (e: any) => {
                setSaving(true);
                const svg = e.target.result;
                clearCanvas(canvasContext.canvas);
                renderSVGOnCanvas(svg);
            }
            reader.readAsText(file);
        }
    }

    const onSave = async () => {
        if (canvasContext.canvas) {
            try {
                autoResizeCanvas(canvasContext.canvas, props.variant.templateSize, true);   
                const canvasData = canvasContext.canvas.toJSON(CanvasProperties);
                const {data} = await axios.post(props.saveUrl, {
                    canvasData,
                    imageDataURL: canvasContext.canvas.toDataURL(),
                })
                setSaving(false);
                if (data) {
                    showMessage(data.message, "success");
                }
            } catch (err) {
                setSaving(false);
                showMessage("There was an issue saving the template.", "error");
                // console.log('err', err);
            }
        } else {
            showMessage("Not a valid Canvas Object to Save", "error");
        }
    }

    const renderSVGOnCanvas = (svg: any) => {
        fabric.loadSVGFromString(svg, (_objects, options) => {
            prepareObjects(_objects).then((objects: any) => {
                const group = new fabric.Group(objects, options);
                canvasContext.canvas.add(group);
                canvasContext.canvas.setActiveObject(group);
                ungroupObjects();
                disableCanvasSelection(canvasContext.canvas);
                canvasContext.canvas.discardActiveObject();
                canvasContext.canvas.requestRenderAll();
                onSave();
                autoResizeCanvas(canvasContext.canvas, props.variant.templateSize, true);                              
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
                    autoResizeCanvas(canvasContext.canvas, props.variant.templateSize, true);
                }).catch((e) => {
                    setSaving(false);
                    reject(e);
                });
            }).catch((e) => {
                setSaving(false);
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

    const clearCanvas = (canvas: fabric.Canvas) => {
        canvas.clear();
        canvas.backgroundColor = '#fff'; 
    };

    const disableCanvasSelection = (canvas: fabric.Canvas) => {
        canvas.selection = false;
        canvas.forEachObject((obj) => {
            obj.selectable = false;
            obj.evented = false;
        });
        canvas.requestRenderAll();
    };

    const showMessage = (msg: string, type: "info" | "error" | "success" = "info", delay: number = 3000) => {
        setMessage(msg);
        setMessageType(type);

        setTimeout(() => {
            setMessage("");
        }, delay);
    };

    return (
        <SVGContainer>
            <label>Select Template SVG</label>
            <SVGLoaderForm>
                <SVGLoaderInput type="file" onChange={onFileChange} key={inputKey} className="form-control" />
            </SVGLoaderForm>
            {saving && <small>{"Saving Canvas Data..."}</small>} 
            {message && (
                <MessageBox type={messageType}>
                    {message}
                </MessageBox>
            )}
        </SVGContainer>
    )
}

export default SVGLoader;