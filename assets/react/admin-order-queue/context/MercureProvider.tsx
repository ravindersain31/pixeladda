import React, { createContext, useEffect, useState } from "react";
import { EventSourcePolyfill } from "event-source-polyfill";
import { MERCURE_HUB_URL, MERCURE_TOPICS, MercureEvent } from "../constants/mercure.constant";
import { fetchJwtToken, getSessionId, getToken, isTokenExpired } from "../utils/mercure.utils";
import { Button, notification, message, Spin } from 'antd';
import { SmileOutlined, ReloadOutlined } from '@ant-design/icons';

interface MercureContextProps {
    events: MercureEvent[];
    subscribe: (topic: string, callback: (data: any) => void) => void;
    unsubscribe: (topic: string, callback: (data: any) => void) => void;
}

const defaultContextValue: MercureContextProps = {
    events: [],
    subscribe: () => { },
    unsubscribe: () => { },
};

export const MercureContext = createContext<MercureContextProps>(defaultContextValue);

const MercureProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [events, setEvents] = useState<MercureEvent[]>([]);
    const subscribers = new Map<string, Set<(data: any) => void>>();
    const [api, contextHolder] = notification.useNotification();
    const [isReconnecting, setIsReconnecting] = useState<boolean>(false);

    useEffect(() => {
        initializeMercureConnection();
        const heartbeatInterval = setInterval(sendHeartbeat, 3 * 60 * 1000); // 5 minutes

        return () => clearInterval(heartbeatInterval);
    }, []);

    const initializeMercureConnection = async () => {
        api.destroy();
        let jwt = getToken();
        if (!jwt || isTokenExpired()) {
            jwt = await fetchJwtToken();
            if (!jwt) {
                message.error("Failed to fetch JWT token. Please try again later.");
                return;
            }
        }

        const hubUrl = new URL(MERCURE_HUB_URL);
        Object.values(MERCURE_TOPICS).forEach((topic) => hubUrl.searchParams.append("topic", topic));

        const eventSource = new EventSourcePolyfill(hubUrl.toString(), {
            headers: { Authorization: `Bearer ${jwt}` },
        });

        eventSource.onopen = () => {
            setIsReconnecting(false);
            api.destroy();
        };

        eventSource.onmessage = (event) => {
            try {
                const eventData: MercureEvent = JSON.parse(event.data);

                setEvents([eventData]);

                const topicSubscribers = subscribers.get(eventData.topic);
                topicSubscribers?.forEach((callback) => callback(eventData));
            } catch (error) {
                console.error("Error parsing Mercure event:", error);
            }
        };

        eventSource.onerror = (error) => {
            console.error("Mercure connection error:", error);
            eventSource.close();
            handleDisconnect(eventSource);
        };

        return () => eventSource.close();
    };

    const sendHeartbeat = async () => {
        try {
            const response = await fetch("/warehouse/queue-api/sessions/heartbeat", { method: "GET" });

            if (response.redirected === true && response.ok && response.url.includes('login')) {
                api.destroy();
                showReloadNotification();
                setTimeout(() => window.location.reload(), 1000 * 60 * 5);
            }
        } catch (error) {
            console.error("Heartbeat request failed:", error);
        }
    };

    const handleDisconnect = (eventSource: EventSourcePolyfill) => {
        if (eventSource.readyState === EventSource.CLOSED) {
            if (!isReconnecting) {
                setIsReconnecting(true);
                api.destroy();
            }
            setTimeout(() => initializeMercureConnection(), 5000);
        }
    };

    const showReloadNotification = () => {
        api.open({
            placement: 'bottomLeft',
            duration: 0,
            message: 'Session Expired',
            description: 'Session has expired. Please login again. Redirecting to login page in 10 seconds.',
            btn: (
                <Button type="primary" icon={<ReloadOutlined />} onClick={() => window.location.reload()}>
                    Reload
                </Button>
            ),
            icon: <SmileOutlined style={{ color: 'red' }} />,
        });
    };

    const subscribe = (topic: string, callback: (data: any) => void) => {
        if (!subscribers.has(topic)) {
            subscribers.set(topic, new Set());
        }
        subscribers.get(topic)!.add(callback);
    };

    const unsubscribe = (topic: string, callback: (data: any) => void) => {
        if (subscribers.has(topic)) {
            subscribers.get(topic)!.delete(callback);
            if (subscribers.get(topic)!.size === 0) {
                subscribers.delete(topic);
            }
        }
    };

    return (
        <MercureContext.Provider value={{ events, subscribe, unsubscribe }}>
            {contextHolder}
            {children}
        </MercureContext.Provider>
    );
};

export default MercureProvider;