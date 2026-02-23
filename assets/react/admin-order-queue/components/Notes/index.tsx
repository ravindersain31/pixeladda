import React, { useState, useEffect, memo } from 'react';
import { Input, message, Spin } from 'antd';
import axios from 'axios';
import { LoadingOutlined } from '@ant-design/icons';
import { NotesWrapper } from './styled';
import { useAppDispatch } from '@react/admin-order-queue/hook.ts';
import actions from '@react/admin-order-queue/redux/actions';

const { TextArea } = Input;

interface NotesProps {
    warehouseOrderId: string;
    comments?: string | null;
    notesRows?: number;
}

const Notes = memo(({ warehouseOrderId, comments, notesRows }: NotesProps) => {
    const dispatch = useAppDispatch();

    const [notes, setNotes] = useState<string|null>(comments || '');
    const [lastSavedComment, setLastSavedComment] = useState<string|null>(comments || '');
    const [timeoutId, setTimeoutId] = useState<NodeJS.Timeout | null>(null);
    const [loading, setLoading] = useState<boolean>(false);

    // Prevent resetting if comment hasn't actually changed or if saving
    useEffect(() => {
        if (!loading && comments !== undefined && comments !== lastSavedComment) {
            setNotes(comments);
            setLastSavedComment(comments);
        }
    }, [comments, loading, lastSavedComment]);

    const handleNotesChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
        const updatedNotes = e.target.value;
        setNotes(updatedNotes);

        if (timeoutId) {
            clearTimeout(timeoutId);
        }

        const id = setTimeout(() => {
            dispatch(actions.config.updateNotes({ id: warehouseOrderId, comments: updatedNotes }));
            updateNotes(warehouseOrderId, updatedNotes);
        }, 1000);

        setTimeoutId(id);
    };

    const updateNotes = async (warehouseOrderId: string, updatedNotes: string) => {
        setLoading(true);
        try {
            await axios.post('/warehouse/queue-api/warehouse-orders/update-note', {
                id: warehouseOrderId,
                comments: updatedNotes,
            });
            setLastSavedComment(updatedNotes);
        } catch (error) {
            message.error('Error updating comments: ' + error, 5);
        } finally {
            setLoading(false);
        }
    };

    const handleClick = (e: React.MouseEvent) => {
        e.stopPropagation();
    };

    return (
        <NotesWrapper onClick={handleClick}>
            <TextArea
                rows={notesRows || 2}
                placeholder="Add your notes here..."
                value={notes || ''}
                onChange={handleNotesChange}
            />
            {loading && (
                <Spin
                    indicator={<LoadingOutlined spin />}
                    size="small"
                    style={{
                        position: 'absolute',
                        top: '8px',
                        right: '8px',
                        transform: 'translateY(0)',
                    }}
                />
            )}
        </NotesWrapper>
    );
});

export default Notes;
