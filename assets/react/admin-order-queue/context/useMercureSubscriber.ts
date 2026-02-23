import { useContext, useEffect, useCallback } from 'react';
import { MercureContext } from './MercureProvider';
import { MercureTopics, MercureEvent } from '../constants/mercure.constant';

const useMercureSubscriber = (topic: MercureTopics, callback: (data: any) => void) => {
    const context = useContext(MercureContext);

    if (!context) {
        console.warn('MercureContext is not available.');
        return;
    }

    const { subscribe, unsubscribe, events } = context;

    const memoizedCallback = useCallback(callback, []);

    useEffect(() => {
        // Process only new events relevant to this topic
        events
            .filter((event: MercureEvent) => event.topic === topic)
            .forEach((event: MercureEvent) => {
                memoizedCallback(event.data);
            });

    }, [events, topic]); // Removed `callback` to avoid unnecessary re-execution

    useEffect(() => {
        if (subscribe) {
            subscribe(topic, memoizedCallback);
        }

        return () => {
            if (unsubscribe) {
                unsubscribe(topic, memoizedCallback);
            }
        };
    }, [topic, subscribe, unsubscribe]); // Removed `events` to avoid resubscribing on every event
};

export default useMercureSubscriber;
