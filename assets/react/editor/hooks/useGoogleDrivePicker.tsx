import { useEffect, useState } from "react";
declare let google: any;
declare let window: any;

export interface PickerConfiguration {
    clientId: string;
    developerKey: string;
    appId: string;
    token?: string;
    customScopes?: string[];
    viewId?: string;
    showUploadView?: boolean;
    showUploadFolders?: boolean;
    multiselect?: boolean;
    supportDrives?: boolean;
    locale?: string;
    callbackFunction: (data: any) => void;
}

export function useGoogleDrivePicker(): [
    (config: PickerConfiguration) => void,
    string | undefined
] {
    const [pickerApiLoaded, setPickerApiLoaded] = useState(false);
    const [token, setToken] = useState<string>();
    const [config, setConfig] = useState<PickerConfiguration | null>(null);

    useEffect(() => {
        const script1 = document.createElement("script");
        script1.src = "https://apis.google.com/js/api.js";
        script1.onload = () => {
            window.gapi.load("picker", { callback: () => setPickerApiLoaded(true) });
        };
        document.body.appendChild(script1);

        const script2 = document.createElement("script");
        script2.src = "https://accounts.google.com/gsi/client";
        document.body.appendChild(script2);
    }, []);

    const openPicker = (config: PickerConfiguration) => {
        setConfig(config);
        if (!config.token) {
            const client = google.accounts.oauth2.initTokenClient({
                client_id: config.clientId,
                scope: config.customScopes?.join(" ") || "",
                callback: (tokenResponse: any) => {
                    setToken(tokenResponse.access_token);
                    createPicker({ ...config, token: tokenResponse.access_token });
                },
            });
            client.requestAccessToken();
        } else {
            createPicker(config);
        }
    };

    const createPicker = ({
        token,
        appId,
        developerKey,
        viewId = "DOCS",
        showUploadView,
        multiselect,
        supportDrives,
        locale = "en",
        callbackFunction,
    }: PickerConfiguration) => {
        const view = new google.picker.DocsView(google.picker.ViewId[viewId]);

        const pickerBuilder = new google.picker.PickerBuilder()
            .setOAuthToken(token)
            .setDeveloperKey(developerKey)
            .setAppId(appId)
            .setCallback(callbackFunction)
            .setLocale(locale)
            .addView(view);

        if (showUploadView) {
            pickerBuilder.addView(new google.picker.DocsUploadView());
        }
        if (multiselect) {
            pickerBuilder.enableFeature(google.picker.Feature.MULTISELECT_ENABLED);
        }
        if (supportDrives) {
            pickerBuilder.enableFeature(google.picker.Feature.SUPPORT_DRIVES);
        }

        const picker = pickerBuilder.build();
        picker.setVisible(true);
    };

    return [openPicker, token];
}
