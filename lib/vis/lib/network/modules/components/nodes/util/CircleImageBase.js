import NodeBase from '../util/NodeBase'

class CircleImageBase extends NodeBase {
  constructor(options, body, labelModule) {
    super(options, body, labelModule);
    this.labelOffset = 0;
    this.imageLoaded = false;
  }

  setOptions(options, imageObj) {
    this.options = options;
    if (imageObj) {
      this.imageObj = imageObj;
    }
  }

  /**
   * This function resizes the image by the options size when the image has not yet loaded. If the image has loaded, we
   * force the update of the size again.
   *
   * @private
   */
  _resizeImage() {
    let force = false;
    if (!this.imageObj.width || !this.imageObj.height) { // undefined or 0
      this.imageLoaded = false;
    }
    else if (this.imageLoaded === false) {
      this.imageLoaded = true;
      force = true;
    }

    if (!this.width || !this.height || force === true) {  // undefined or 0
      var width, height, ratio;
      if (this.imageObj.width && this.imageObj.height) { // not undefined or 0
        width = 0;
        height = 0;
      }
      if (this.options.shapeProperties.useImageSize === false) {
        if (this.imageObj.width > this.imageObj.height) {
          ratio = this.imageObj.width / this.imageObj.height;
          width = this.options.size * 2 * ratio || this.imageObj.width;
          height = this.options.size * 2 || this.imageObj.height;
        }
        else {
          if (this.imageObj.width && this.imageObj.height) { // not undefined or 0
            ratio = this.imageObj.height / this.imageObj.width;
          }
          else {
            ratio = 1;
          }
          width = this.options.size * 2;
          height = this.options.size * 2 * ratio;
        }
      }
      else {
        // when not using the size property, we use the image size
        width = this.imageObj.width;
        height = this.imageObj.height;
      }
      this.width = width;
      this.height = height;
      this.radius = 0.5 * this.width;
    }

  }

  _drawRawCircle(ctx, x, y, selected, hover, size) {
    var neutralborderWidth = this.options.borderWidth;
    var selectionLineWidth = this.options.borderWidthSelected || 2 * this.options.borderWidth;
    var borderWidth = (selected ? selectionLineWidth : neutralborderWidth) / this.body.view.scale;
    ctx.lineWidth = Math.min(this.width, borderWidth);

    ctx.strokeStyle = selected ? this.options.color.highlight.border : hover ? this.options.color.hover.border : this.options.color.border;
    ctx.fillStyle = selected ? this.options.color.highlight.background : hover ? this.options.color.hover.background : this.options.color.background;
    ctx.circle(x, y, size);

    // draw shadow if enabled
    this.enableShadow(ctx);
    // draw the background
    ctx.fill();
    // disable shadows for other elements.
    this.disableShadow(ctx);

    //draw dashed border if enabled, save and restore is required for firefox not to crash on unix.
    ctx.save();
    // if borders are zero width, they will be drawn with width 1 by default. This prevents that
    if (borderWidth > 0) {
      this.enableBorderDashes(ctx);
      //draw the border
      ctx.stroke();
      //disable dashed border for other elements
      this.disableBorderDashes(ctx);
    }
    ctx.restore();
  }

  _drawImageAtPosition(ctx) {
    if (this.imageObj.width != 0) {
      // draw the image
      ctx.globalAlpha = 1.0;

      // draw shadow if enabled
      this.enableShadow(ctx);

      // draw image
      ctx.drawImage(this.imageObj, this.left, this.top, this.width, this.height);

      // disable shadows for other elements.
      this.disableShadow(ctx);
    }
  }

  _drawImageLabel(ctx, x, y, selected) {
    var yLabel;
    var offset = 0;

    if (this.height !== undefined) {
      offset = this.height * 0.5;
      var labelDimensions = this.labelModule.getTextSize(ctx);
      if (labelDimensions.lineCount >= 1) {
        offset += labelDimensions.height / 2;
      }
    }

    yLabel = y + offset;

    if (this.options.label) {
      this.labelOffset = offset;
    }
    this.labelModule.draw(ctx, x, yLabel, selected, 'hanging');
  }
}

export default CircleImageBase;