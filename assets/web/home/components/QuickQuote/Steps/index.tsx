import Shape from './Shape';
import Frames from './Frame';
import ImprintColor from './ImprintColor';
import Grommets from './Grommet';
import GrommetColor from './GrommetColor';
import Side from './Sides';
import { AddonDisplayText } from '@react/editor/redux/reducer/config/interface';
import { isPromoHost } from '@react/editor/helper/editor';
import Flutes from './Flute';

interface StepProps {
  showGrommetColor: boolean;
  framePrices: { [key: string]: number };
  disallowedFrameForShape: boolean;
  showFrame: boolean;
  label?: boolean;
  addons?: { [key: string]: string };
  product?: any;
}

export const stepColors: { [step: string]: string } = {
  'step_1': isPromoHost() ? '#25549b' : '#704D9F',
  'step_2': 'rgb(21, 0, 214, 0.8)',
  'step_3': 'rgb(7, 142, 137, 0.8)',
  'step_4': 'rgb(0, 119, 4, 0.9)',
  'step_5': 'rgb(229, 145, 0, 0.9)',
  'step_6': 'rgb(200, 31, 18, 0.9)',
  'step_7': 'rgb(138, 0, 119, 0.9)',
};

export const getSteps = ({ showGrommetColor, framePrices, disallowedFrameForShape, showFrame, label = true, addons, product }: StepProps) => {
  const steps = [
    {
      key: '1',
      label: <>
        {label &&
          <span style={{ backgroundColor: stepColors[`step_1`] }}>
            STEP 1
          </span>
        }
        {label ? <p>Choose Your Sides</p> : <p>Sides: {addons && (AddonDisplayText.sides as any)[addons.sides]}</p>}
      </>,
      children: <Side />,
    },
    {
      key: '2',
      label: <>
        {label &&
          <span style={{ backgroundColor: stepColors[`step_2`] }}>
            STEP 2
          </span>
        }
        {label ? <p>Choose Your Shape</p> : <p>Shape: {addons && (AddonDisplayText.shape as any)[addons.shape]}</p>}
      </>,
      children: <Shape />,
    },
    {
      key: '3',
      label: <>
        {label &&
          <span style={{ backgroundColor: stepColors[`step_3`] }}>
            STEP 3
          </span>
        }
        {label ? <p>Choose Imprint Color</p> : <p>Imprint Color: {addons && (AddonDisplayText.imprintColor as any)[addons.imprintColor]}</p>}
      </>,
      children: <ImprintColor addons={addons} />,
    },
    {
      key: '4',
      label: <>
        {label &&
          <span style={{ backgroundColor: stepColors[`step_4`] }}>
            STEP 4
          </span>
        }
        {label ? <p>Choose Your Grommets (3/8 Inch Hole)</p> : <p>Grommets: {addons && (AddonDisplayText.grommets as any)[addons.grommets]}</p>}
      </>,
      children: <Grommets addons={addons} />,
    }
  ];

  if (showGrommetColor) {
    steps.push({
      key: '5',
      label: <>
        {label &&
          <span style={{ backgroundColor: stepColors[`step_5`] }}>
            STEP 5
          </span>
        }
        {label ? <p>Choose Grommet Color</p> : <p>Grommet Color: {addons && (AddonDisplayText.grommetColor as any)[addons.grommetColor]}</p>}
      </>,
      children: <GrommetColor addons={addons} />,
    });
  }

  steps.push({
    key: showGrommetColor ? '6' : '5',
    label: <>
      {label &&
        <span style={{ backgroundColor: stepColors[showGrommetColor ? `step_6` : `step_5`] }}>
          {showGrommetColor ? 'STEP 6' : 'STEP 5'}
        </span>
      }
      {label ? <p>Choose Flutes Direction</p> : <p>Flutes: {addons && (AddonDisplayText.flute as any)[addons.flute]}</p>}
    </>,
    children: <Flutes product={product}  addons={addons} />,
  });

  if (showFrame) {
    steps.push({
      key: showGrommetColor ? '7' : '6',
      label: <>
        {label &&
          <span style={{ backgroundColor: stepColors[showGrommetColor ? `step_7` : `step_6`] }}>
            {showGrommetColor ? 'STEP 7' : 'STEP 6'}
          </span>
        }
        {label ? <p>Choose Your Frame</p> : <p>Frame: {addons && (AddonDisplayText.frame as any)[addons.frame]}</p>}
      </>,
      children: <Frames framePrices={framePrices} disallowedFrameForShape={disallowedFrameForShape} addons={addons} />,
    });
  }
  return steps;
};
