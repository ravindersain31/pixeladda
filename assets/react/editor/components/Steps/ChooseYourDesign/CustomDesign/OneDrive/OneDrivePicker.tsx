import React, { useState } from "react";
import { PublicClientApplication } from "@azure/msal-browser";
import axios from "axios";
import { FileItem, LoginButton, LogoutButton, OneDrive } from "./styled";
declare global {
  interface Window {
    oneDriveClientId: string;
  }
}
const CLIENT_ID = window.oneDriveClientId;
const REDIRECT_URI = window.location.origin;

const msalConfig = {
  auth: {
    clientId: CLIENT_ID,
    authority: "https://login.microsoftonline.com/common",
    redirectUri: REDIRECT_URI,
  },
};
const msalInstance = new PublicClientApplication(msalConfig);
let msalInitialized = false;

export type OneDriveFile = {
  id: string;
  name: string;
  size: number;
  webUrl: string;
  "@microsoft.graph.downloadUrl"?: string;
};

const OneDrivePicker = ({
  onFileSelected,
}: {
  onFileSelected: (file: OneDriveFile, content: Blob) => void;
}) => {
  const [token, setToken] = useState<string | null>(null);
  const [files, setFiles] = useState<OneDriveFile[]>([]);
  const [loading, setLoading] = useState(false);
  const [selectedFile, setSelectedFile] = useState<OneDriveFile | null>(null);

  const signInAndGetToken = async () => {
    if (!msalInitialized) {
      await msalInstance.initialize();
      msalInitialized = true;
    }

    const loginResponse = await msalInstance.loginPopup({
      scopes: ["Files.ReadWrite.All", "User.Read"],
    });

    const account = loginResponse.account;
    if (!account) throw new Error("Login failed");

    try {
      return (
        await msalInstance.acquireTokenSilent({
          scopes: ["Files.ReadWrite.All", "User.Read"],
          account,
        })
      ).accessToken;
    } catch {
      return (
        await msalInstance.acquireTokenPopup({
          scopes: ["Files.ReadWrite.All", "User.Read"],
        })
      ).accessToken;
    }
  };

  const fetchFiles = async () => {
    setLoading(true);
    try {
      const accessToken = await signInAndGetToken();
      setToken(accessToken);

      const res = await axios.get(
        "https://graph.microsoft.com/v1.0/me/drive/root/children",
        {
          headers: {
            Authorization: `Bearer ${accessToken}`,
          },
        }
      );

      setFiles(res.data.value.filter((item: any) => item.file));

    } catch (err) {
      console.error("OneDrive error:", err);
    } finally {
      setLoading(false);
    }
  };

  const downloadFile = async (file: OneDriveFile) => {
    if (!file["@microsoft.graph.downloadUrl"]) {
      alert("No download URL found.");
      return;
    }

    try {
      const response = await axios.get(file["@microsoft.graph.downloadUrl"], {
        responseType: "blob",
      });
      onFileSelected(file, response.data);
    } catch (err) {
      console.error("Download error:", err);
    }
  };

  const handleLogout = async () => {
    await msalInstance.logoutPopup();
    setToken(null);
    setFiles([]);
    setSelectedFile(null);
  };

  return (
    <OneDrive className="ysp-one-drive">
      <LoginButton onClick={fetchFiles}>Choose from OneDrive</LoginButton>
      {token && <LogoutButton onClick={handleLogout}>Logout</LogoutButton>}
      <div className="onedrive-files-container">
        {files.map((file) => (
          <FileItem
            key={file.id}
            selected={file.id === selectedFile?.id}
            onClick={() => {
              setSelectedFile(file);
              downloadFile(file);
            }}
          >
            {file.name} ({Math.round(file.size / 1024)} KB)
          </FileItem>
        ))}
      </div>
    </OneDrive>
  );
};

export default OneDrivePicker;
