import {fabric} from "fabric";

fabric.Canvas.prototype.selection = false;
fabric.Object.prototype.selectable = false;
fabric.Object.prototype.hoverCursor = "default";
export default fabric;