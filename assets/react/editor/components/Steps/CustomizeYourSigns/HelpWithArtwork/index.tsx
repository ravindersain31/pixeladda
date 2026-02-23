import AdditionalNote from "@react/editor/components/AdditionalNote";
import AddQrCode from "../../CustomizeYourSigns/TextEditor/AddQrCode";
import { isMobile } from "react-device-detect";
import { StyledDiv } from "./styled";

const HelpWithArtwork = () => {
  return (
    <StyledDiv>
      <AddQrCode />
      <AdditionalNote showNeedAssistance={isMobile} />
    </StyledDiv>
  );
};

export default HelpWithArtwork;