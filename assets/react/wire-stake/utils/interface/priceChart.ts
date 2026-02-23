export interface TableColumn {
    title: string;
    dataIndex: string;
    key: string;
    className?: string;
    fixed?: 'left' | 'right';
    width?: number;
}