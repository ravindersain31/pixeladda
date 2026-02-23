import React, { forwardRef, memo, type ReactNode, useEffect } from 'react';
import { autoScrollWindowForElements } from '@atlaskit/pragmatic-drag-and-drop-auto-scroll/element';
import { useBoardContext } from '@react/admin-order-queue/context/BoardContext';

type BoardProps = {
	children: ReactNode;
};

const boardStyle: React.CSSProperties = {
	display: 'flex',
	flexDirection: 'row',
	justifyContent: 'space-evenly',
	alignItems: 'flex-start',
	gap: 10,
	height: '100%',
	flexWrap: 'nowrap',
};

const Board = forwardRef<HTMLDivElement, BoardProps>(({ children }: BoardProps, ref) => {
	const { instanceId } = useBoardContext();

	useEffect(() => {
		return autoScrollWindowForElements({
			canScroll: ({ source }) => source.data.instanceId === instanceId,
		});
	}, [instanceId]);

	return (
		<div style={boardStyle} ref={ref}>
			{children}
		</div>
	);
});

export default memo(Board);