export const TOKEN_NAME = "MERCURE_JWT_TOKEN";
export const TOKEN_EXPIRY = `${TOKEN_NAME}_EXPIRY`;
export const TOKEN_USER_ID = `${TOKEN_NAME}_USER_ID`;

export const saveToken = (token: string, expiresAt: number): void => {
    localStorage.setItem(TOKEN_NAME, token);
    localStorage.setItem(TOKEN_EXPIRY, String(expiresAt));
};

export const getToken = (): string | null => localStorage.getItem(TOKEN_NAME);

export const getTokenExpiry = (): number | null => {
    const expiry = localStorage.getItem(TOKEN_EXPIRY);
    return expiry ? Number(expiry) : null;
};

export const isTokenExpired = (): boolean => {
    const expiry = getTokenExpiry();
    return !expiry || Date.now() >= expiry;
};

export const clearToken = (): void => {
    localStorage.removeItem(TOKEN_NAME);
    localStorage.removeItem(TOKEN_EXPIRY);
};

export const getCookie = (name: string): string | null => {
    const cookies = document.cookie.split("; ");
    for (const cookie of cookies) {
        const [key, value] = cookie.split("=");
        if (key === name) {
            return decodeURIComponent(value);
        }
    }
    return null;
}

export const fetchJwtToken = async (): Promise<string | null> => {
    try {
        const response = await fetch("/warehouse/queue-api/generate/jwt-token", {
            method: "GET",
            credentials: "include",
        });
        if (!response.ok) throw new Error("Failed to fetch JWT token");

        const data = await response.json();

        if (!data.token || !data.expiresIn) {
            throw new Error("Invalid token response from server");
        }

        saveToken(data.token, data.expiresIn); // Backend already sends correct timestamp
        return data.token;
    } catch (error) {
        console.error("Error fetching JWT token:", error);
        return null;
    }
};

export const getSessionId = (): string => {
    let tabId = sessionStorage.getItem("MERCURE_TAB_ID");
    if (!tabId) {
        tabId = generateSessionId();
        sessionStorage.setItem("MERCURE_TAB_ID", tabId);
    }
    return tabId;
};

const generateSessionId = (): string => {
    return `${Date.now().toString(36)}-${Math.random().toString(36).substring(2, 10)}`;
};
