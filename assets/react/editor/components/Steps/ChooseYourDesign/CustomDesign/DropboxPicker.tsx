import { useEffect } from "react";
import { StyledButton } from "./styled.tsx";

  interface DropboxPickerProps {
    onFileSelected: (file: any) => void;
  }
  declare global {
    interface Window {
      dropBoxAppId: string;
    }
  }
  const dropBoxAppId = window.dropBoxAppId;
  const DropboxPicker = ({ onFileSelected }: DropboxPickerProps) => {
    useEffect(() => {
        const existing = document.getElementById("dropboxjs");      
        if (!existing) {
            const script = document.createElement("script");
            script.src = "https://www.dropbox.com/static/api/2/dropins.js";
            script.id = "dropboxjs";
            script.dataset.appKey = dropBoxAppId;
            document.body.appendChild(script);
        }
  }, []);

  const handleDropboxUpload = () => {
    const options = {
      success: (files: any[]) => {
        files.forEach((file) => onFileSelected(file));
      },
      cancel: () => {
        console.log("Dropbox chooser canceled");
      },
      linkType: "direct",
      multiselect: true,
      extensions: [".png", ".jpg", ".jpeg", ".pdf"],
    };

    (window as any).Dropbox.choose(options);
  };

  return (
    <div style={{ display: "flex", justifyContent: "center" }}>
      <StyledButton className="btn-ysp-dropbox-upload" onClick={handleDropboxUpload}>
        Choose from Dropbox
      </StyledButton>
    </div>
  );
};

export default DropboxPicker;