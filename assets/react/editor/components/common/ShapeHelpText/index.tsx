import { Shape } from "@react/editor/redux/interface.ts";
import React from 'react';

const descriptions: Record<Shape, React.ReactNode> = {
  [Shape.SQUARE]: (
    <>
      <b>Square / Rectangle Shape:</b> <br />
      Square or Rectangle Shape allows <br />
      printing and cutting along any <br />
      defined square or rectangular <br />
      border. This is the most common <br />
      and popular choice for standard <br />
      yard signs, including default sizes.
    </>
  ),
  [Shape.CIRCLE]: (
    <>
      <b>Circle Shape:</b> <br />
      Circle Shape allows printing <br />
      and cutting along any circular <br />
      border. This includes any <br />
      round outlining.
    </>
  ),
  [Shape.OVAL]: (
    <>
      <b>Oval Shape:</b> <br />
      Oval Shape allows printing <br />
      and cutting along any oval <br />
      border. This includes any <br />
      oval outlining.
    </>
  ),
  [Shape.CUSTOM]: (
    <>
      <b>Custom Shape:</b> <br />
      Custom Shape allows printing and cutting along <br />
      any irregular border or die cut. This includes <br />
      any undefined outlining for fully custom signs. <br />
      We will cut along the outer edges of your custom <br />
      shape. Please leave a comment if necessary on <br />
      your final cut requirements.
    </>
  ),
  [Shape.CUSTOM_WITH_BORDER]: (
    <>
      <b>Custom with Border Shape:</b> <br />
      Custom with Border Shape allows printing and cutting <br />
      along any irregular border or die cut. This includes any <br />
      undefined outlining for fully custom signs. We will print <br />
      and cut along the outer edges of your custom with <br />
      border shape. Please leave a comment if necessary on <br />
      your final print and cut requirements.
    </>
  ),
};

const ShapeHelpText = ({ addon }: { addon: { key: Shape } }) => {
  const description = descriptions[addon.key];

  return description ? (
    <p className="text-start mb-0">{description}</p>
  ) : null;
};

export default ShapeHelpText;
