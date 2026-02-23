import AdditionalNote from "@react/editor/components/AdditionalNote";
import { StyledDiv, AddQrWrapper } from "./styled";
import AddQrCode from "../../CustomizeYourSigns/TextEditor/AddQrCode";
import useShowCanvas from "@react/editor/hooks/useShowCanvas";

const HelpWithArtwork = () => {
  const showCanvas = useShowCanvas();

  return (
    <>
      <StyledDiv>
        {showCanvas && <AddQrWrapper><AddQrCode /></AddQrWrapper>}
        <AdditionalNote />
      </StyledDiv>
    </>
  );
};

export default HelpWithArtwork;