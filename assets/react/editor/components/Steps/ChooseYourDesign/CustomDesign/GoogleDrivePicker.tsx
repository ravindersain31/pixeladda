import React, { useEffect, useState } from "react";
import useDrivePicker from "react-google-drive-picker";
import { StyledButton } from "./styled.tsx";
import { useGoogleDrivePicker } from "@react/editor/hooks/useGoogleDrivePicker.tsx";

interface GoogleDrivePickerProps {
  onFileSelected: (file: any) => void;
}
declare global {
  interface Window {
    googleDriveClientId: string;
    googleDriveApiKey: string;
    googleDriveAppId: string;
  }
}
const CLIENT_ID = window.googleDriveClientId;
const DEVELOPER_KEY = window.googleDriveApiKey;
const APP_ID = window.googleDriveAppId;

const GoogleDrivePicker: React.FC<GoogleDrivePickerProps> = ({
  onFileSelected,
}) => {
  const [openPicker, authResponse] = useGoogleDrivePicker();
  const [token, setToken] = useState<string>("");

  useEffect(() => {
    if (authResponse) {
      setToken(authResponse);
    }
  }, [authResponse]);
  const handleOpenPicker = () => {
    openPicker({
      clientId: CLIENT_ID,
      developerKey: DEVELOPER_KEY,
      appId: APP_ID,
      viewId: "DOCS",
      showUploadView: true,
      supportDrives: true,
      showUploadFolders: true,
      multiselect: false,
      customScopes: [
        "https://www.googleapis.com/auth/drive.file",
      ],
      callbackFunction: async (data) => {
        if (data.action === "picked") {
          const file = data.docs[0];
          try {
            onFileSelected(file);
          } catch (err) {
            console.error("Error downloading file from Google Drive:", err);
          }
        }
      },
    });
  };

  return (
    <form style={{ display: "flex", justifyContent: "center" }}>
      <input type="hidden" name="googleToken" value={token} />
      <StyledButton className="btn-ysp-google-upload" onClick={handleOpenPicker}>
        Choose from Google Drive
      </StyledButton>
    </form>
  );
};

export default GoogleDrivePicker;
