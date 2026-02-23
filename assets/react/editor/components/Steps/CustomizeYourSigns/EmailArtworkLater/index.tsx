import AdditionalNote from "@react/editor/components/AdditionalNote";
import { StyledDiv } from "./styled";
import AddQrCode from "../../CustomizeYourSigns/TextEditor/AddQrCode";
import { isMobile } from "react-device-detect";

const EmailArtworkLater = () => {

  return (
    <StyledDiv>
      <AddQrCode />
      <AdditionalNote showNeedAssistance={isMobile} />
    </StyledDiv>
  );
};

export default EmailArtworkLater;