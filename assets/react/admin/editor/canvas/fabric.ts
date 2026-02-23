import {fabric} from "fabric";
import buildControls from './controls.ts';

buildControls(fabric);

fabric.Object.prototype.objectCaching = false;
fabric.Object.prototype.borderColor = 'rgb(242, 242, 242)';
fabric.Object.prototype.borderScaleFactor = 3;

fabric.util.loadImage = function (url, callback, context, crossOrigin) {
    if (!url) {
        // @ts-ignore
        callback && callback.call(context, url);
        return;
    }

    let img = fabric.util.createImage();
    img.crossOrigin = "";

    const onLoadCallback = function () {
        callback && callback.call(context, img);
        // @ts-ignore
        img = img.onload = img.onerror = null;
    };

    img.onload = onLoadCallback;
    /** @ignore */
    img.onerror = function () {
        fabric.log('Error loading ' + img.src);
        // @ts-ignore
        callback && callback.call(context, null, true);
        // @ts-ignore
        img = img.onload = img.onerror = null;
    };

    // data-urls appear to be buggy with crossOrigin
    // https://github.com/kangax/fabric.js/commit/d0abb90f1cd5c5ef9d2a94d3fb21a22330da3e0a#commitcomment-4513767
    // see https://code.google.com/p/chromium/issues/detail?id=315152
    //     https://bugzilla.mozilla.org/show_bug.cgi?id=935069
    if (url.indexOf('data') !== 0 && crossOrigin) {
        img.crossOrigin = crossOrigin;
    }

    // IE10 / IE11-Fix: SVG contents from data: URI
    // will only be available if the IMG is present
    // in the DOM (and visible)
    if (url.substring(0, 14) === 'data:image/svg') {
        img.onload = null;
        // @ts-ignore
        fabric.util.loadImageInDom(img, onLoadCallback);
    }
    img.src = url;
};

export default fabric;