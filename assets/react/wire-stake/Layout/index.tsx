import { shallowEqual } from 'react-redux';
import React, { useEffect, Suspense, memo, lazy } from 'react';
import { Spin } from 'antd';

// internal imports
import { useAppDispatch, useAppSelector } from "@wireStake/hook.ts";
import actions from "@wireStake/redux/actions";
import { WireStakeWrapper } from "@wireStake/Layout/styled.tsx";
import { spinnerImage } from '@react/editor/helper/editor';
const Steps = lazy(() => import("@wireStake/components/Steps"));

const Layout = (props: any) => {
    const initialized = useAppSelector((state) => state.config.initialized, shallowEqual);

    const dispatch = useAppDispatch();

    useEffect(() => {
        if (!initialized) {
            dispatch(actions.config.initialize(props));
            dispatch(actions.cartStage.initialize(props));
        }
    }, [initialized]);

    if (!initialized) {
        return null;
    }

    return (
        <>
            <WireStakeWrapper>
                <Suspense
                    fallback={
                        <div
                            style={{
                                position: 'fixed',
                                top: 0,
                                left: 0,
                                right: 0,
                                bottom: 0,
                                display: 'flex',
                                justifyContent: 'center',
                                alignItems: 'center',
                                backgroundColor: 'rgba(255, 255, 255, 0.6)',
                                zIndex: 9999,
                            }}
                        >
                            <div className="loading-product-editor">
                                <div className="d-flex justify-content-center align-items-center ">
                                    <img src={spinnerImage()} alt="Loading..." />
                                </div>
                            </div>
                        </div>
                    }
                >
                    <Steps />
                </Suspense>
            </WireStakeWrapper>
        </>
    );
};

export default memo(Layout);