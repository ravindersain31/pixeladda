import { RotationSlider } from "./styled";

interface RotationSliderProps {
  disabled: boolean;
  value: number;
  onChange: (value: number) => void;
}

const Rotation = ({ disabled, value, onChange }: RotationSliderProps) => {
  return <RotationSlider disabled={disabled} min={0} max={360} value={value} onChange={onChange} />;
};

export default Rotation;
