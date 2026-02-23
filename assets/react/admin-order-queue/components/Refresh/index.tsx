import React, { useEffect } from 'react';
import axios from 'axios';
import { isNull } from 'lodash';
import { useAppDispatch, useAppSelector } from '@react/admin-order-queue/hook';
import { refresh } from '@react/admin-order-queue/helper';

const Refresh = () => {
    const config = useAppSelector((state) => state.config);
    const dispatch = useAppDispatch();

    const fetchData = async () => {
        if (isNull(config.printer)) return;

        try {
            const data = refresh(config.printer);
        } catch (error) {
            console.error('Error fetching refreshed data:', error);
        }
    };

    useEffect(() => {
        const interval = setInterval(() => {
            fetchData();
        }, 5000);

        return () => clearInterval(interval);
    }, [config.printer]);

    return null;
};

export default Refresh;
