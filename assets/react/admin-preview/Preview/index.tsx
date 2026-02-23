import fabric from "../fabric.ts";
import React, { useEffect, useState, useLayoutEffect } from "react";
import fitObjectsToCanvas from "@react/editor/canvas/fitObjectsToCanvas.ts";
import { calculateCanvasDimensions } from "../utils.ts";
import { PreviewContainer, StyledCol } from "./styled.tsx";
import Button from "@react/admin-preview/Button";
import { SideName } from "@react/admin-preview/styled.tsx";
import { Col, Row } from "antd";
import FontFaceObserver from "fontfaceobserver";
import DownloadArtwork from "./DownloadArtwork.tsx";
import { preloadFonts } from "@react/editor/canvas/utils.ts";

interface PreviewProps {
  itemId: string;
  item: any;
  side: string;
  canvasData: any
  templateSize: {
    width: number;
    height: number;
  };
  artworks: any[];
}

const Preview = ({ itemId, item, templateSize, canvasData, side, artworks }: PreviewProps) => {

  const [canvas, setCanvas] = useState<fabric.Canvas | null>(null);
  const [isCanvasReady, setIsCanvasReady] = useState<boolean>(false);
  const [isCustomProduct, setIsCustomProduct] = useState<boolean>(false);
  const [previewIndex, setPreviewIndex] = useState<number>(1);

  useLayoutEffect(() => {
    if (canvasData?.objects?.length > 0) {

      const rafId = requestAnimationFrame(() => {
        const tempCanvas = new fabric.Canvas(`canvas_preview_${itemId}_${side}`, {
          width: 500,
          height: 500,
          perPixelTargetFind: true,
        });

        const dimensions = calculateCanvasDimensions(tempCanvas.getElement(), templateSize);
        tempCanvas.setDimensions(dimensions);
        
        setCanvas(tempCanvas);
        setIsCanvasReady(true);
      });
      
      return () => cancelAnimationFrame(rafId);
    } else {
      setIsCustomProduct(true);
    }
  }, []);

  useEffect(() => {
    if (!canvas) return;
    const sideCanvasData = Array.isArray(canvasData)
      ? canvasData
      : canvasData?.objects
        ? canvasData
        : canvasData?.[side];
    if (!sideCanvasData || !sideCanvasData.objects) return;

    (async () => {
      if (sideCanvasData.objects) {
        await preloadFonts(sideCanvasData.objects);
      }

      canvas.loadFromJSON(
        sideCanvasData, () => {
        const objects = canvas.getObjects();
        objects.forEach((obj: any) => {
          obj.selection = false;
          obj.selectable = false;
        });

        canvas.renderAll();
        setTimeout(() => {
          fitObjectsToCanvas(canvas);
          canvas.requestRenderAll();
        }, 50);
      },
      (o: any) => {
        o.selection = false;
        o.selectable = false;
        if (o.type === "text") {
          o = { ...o, text: o.text.trim() };
        }
        return o;
      }
    );
  })();
  }, [isCanvasReady, side]);

  const autoResizeCanvas = (templateSize: any, fitContents: boolean = false) => {
    if (!canvas) return;
    const dimensions = calculateCanvasDimensions(canvas.getElement(), templateSize);
    canvas.setDimensions(dimensions);
    if (fitContents) {
      fitObjectsToCanvas(canvas);
    }
  };

  const onDownloadDesign = async () => {
    if (canvas) {
      const link = document.createElement("a");
      link.download = `${item.orderId}-${item.name}-${side}.png`;
      link.href = canvas.toDataURL({
        format: "png",
        quality: 1,
        enableRetinaScaling: true,
      });
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } else if (item?.image) {
      try {
        const response = await fetch(item.image, { mode: "cors" });
        const blob = await response.blob();
        const url = URL.createObjectURL(blob);

        const link = document.createElement("a");
        link.download = `${item.orderId}-${item.name}-${side}.png`;
        link.href = url;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        URL.revokeObjectURL(url);
      } catch (error) {
        console.error("Failed to download image:", error);
      }
    }
  };

  const previewFile = (file: fabric.Object) => {
    if (!canvas) return;
    const objects = canvas.getObjects() || [];
    const targetObject = objects.find((obj: any) => obj.custom?.id === file.custom?.id
    );
    if (!targetObject) return console.warn("Object not found!");

    let groupedObjects: fabric.Object[] = [targetObject];
    let foundTarget = false;

    for (const obj of objects) {
      if (obj === targetObject) {
        foundTarget = true;
        continue;
      }
      if (foundTarget) {
        if (obj.custom?.type === "custom-design") break;
        groupedObjects.push(obj);
      }
    }

    groupedObjects.forEach((obj) => canvas.bringToFront(obj));
    canvas.requestRenderAll();
  };

  const customOriginalArtwork = item?.customOriginalArtwork || {};
  const sideFiles = Array.isArray(customOriginalArtwork?.[side])
    ? customOriginalArtwork[side]
    : [];
  const csvExcelFiles = sideFiles.filter(
    (file: any) =>
      file?.url?.endsWith(".csv") ||
      file?.url?.endsWith(".xls") ||
      file?.url?.endsWith(".xlsx") ||
      file?.url?.endsWith(".zip")
  );

  const getFileTypeLabel = (url:any) => {
    if (url.endsWith(".csv")) return "CSV";
    if (url.endsWith(".zip")) return "ZIP";
    return "Excel";
  };

  return (
    <>
      <SideName>
        <div>{side} Side</div>
        <Row justify="center" gutter={[8, 8]}>
          {canvasData?.objects?.length > 0 &&
            canvasData.objects
              .filter(
                (obj: fabric.Object) => obj.custom?.type === "custom-design"
              )
              .length > 0 &&
            canvasData.objects
              .filter(
                (obj: fabric.Object) => obj.custom?.type === "custom-design"
              )
              .map((obj: fabric.Object, index: number) => (
                <StyledCol xs={24} sm={12} md={8} lg={6} key={index}>
                  <Button
                    onClick={() => {
                      previewFile(obj);
                      setPreviewIndex(index + 1);
                    }}
                    type="primary"
                    style={{ width: '100%' }}
                  >
                    Preview Design {index + 1}
                  </Button>
                </StyledCol>
              ))}
        </Row>

        {artworks?.length > 0 && canvasData?.objects?.length > 0 &&
          (() => {
            const previewedObject = canvasData.objects
              .filter((obj: fabric.Object) => obj.custom?.type === "custom-design")[previewIndex - 1];

            const matchedArtwork = artworks.find(
              (art) => art.id === previewedObject?.custom?.id
            );

            return matchedArtwork ? (
              <Row justify={"center"} className="gap-2" gutter={[8, 8]} key={`download-artwork-${previewIndex}`}>
                <Button
                  type="default"
                  href={matchedArtwork.originalFileUrl}
                  download
                  target="_blank"
                  rel="noopener noreferrer"
                  style={{
                    textTransform: 'capitalize',
                    whiteSpace: 'normal',
                    wordWrap: 'break-word',
                    overflowWrap: 'break-word', 
                    height: 'auto'
                  }}
                >
                  View Original Artwork {previewIndex}
                </Button>
                <DownloadArtwork
                  matchedArtwork={matchedArtwork}
                  previewIndex={previewIndex}
                  orderId={item.orderId}
                  side={side}
                />
              </Row>
            ) : null;
          })()}
        {csvExcelFiles.length > 0 && (
          <div style={{ marginTop: "10px" }}>
            {csvExcelFiles.map((file: any, i: number) => (
              <div key={`download-${side}-csv-${i}`}>
                <Button
                  type="default"
                  href={file.originalFileUrl || file.url}
                  download
                  target="_blank"
                  rel="noopener noreferrer"
                  style={{
                    marginTop: "10px",
                    textTransform: "capitalize",
                  }}
                >
                  {`Download ${getFileTypeLabel(file.url)} File ${i + 1} (${side})`}
                </Button>
              </div>
            ))}
          </div>
        )}
        <div key={`download-${previewIndex}`}>
          <Button
            onClick={onDownloadDesign}
            type="default"
            style={{ marginTop: "10px" }}
          >
            Download Design {previewIndex}
          </Button>
        </div>
      </SideName>
      <PreviewContainer>
        {!isCustomProduct ? (
          <canvas id={`canvas_preview_${itemId}_${side}`} />
        ) : (
          isCustomProduct && Array.isArray(canvasData) && canvasData.length > 0 ? (
            <div className="custom-product">
              {canvasData.map((file: string, index: number) => (
                <a href={file.replaceAll('/fit-in/1000x1000', '/fit-in/2000x2000')} key={file} target="_blank">
                  View Custom Design File #{index + 1}
                </a>
              ))}
            </div>
          ) :
            <img
              src={item.image}
              alt="Preview"
              style={{ width: "100%", height: "auto" }}
            />
        )}
      </PreviewContainer>
    </>
  );
};
export default Preview;