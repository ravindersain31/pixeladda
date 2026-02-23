import React, { memo, useEffect, useState } from "react";
import { useAppSelector } from "@react/editor/hook.ts";
import { StyledTable, BulkCol } from "./styled.tsx";
import Value from "./Value.tsx";
import { getVariantLabel } from "@react/editor/helper/template.ts";
import { Button } from "antd";

interface Props {
    setIsBulkOrderModalOpen: React.Dispatch<React.SetStateAction<boolean>>;
}

const PriceChart = ({ setIsBulkOrderModalOpen}: Props) => {
    const [columns, setColumns] = useState<any[]>([]);
    const [data, setData] = useState<any[]>([]);
    const [customData, setCustomData] = useState<any[]>([]);
    const product = useAppSelector(state => state.config.product);
    const editor = useAppSelector(state => state.editor);
    const canvas = useAppSelector(state => state.canvas);
    const config = useAppSelector(state => state.config);
    const initialData = useAppSelector(state => state.config.initialData);
    const [activePrices, setActivePrices] = useState<{
        [key: string]: number
    }>({});

    const [activeCustomPrices, setActiveCustomPrices] = useState<{
        [key: string]: number;
    }>({});

    const [variants, setVariants] = useState<any>(product.pricing.variants);

    useEffect(() => {
        setVariants(product.pricing.variants);
        handleColumns();
    }, [product]);

    useEffect(() => {
        handleData();
        handleCustomData();
    }, [columns, activePrices]);

    useEffect(() => {
        const editorActivePrices: {
            [key: string]: number
        } = {};
        const editorActiveCustomPrices: { [key: string]: number; } = {};

        for (const [productId, item] of Object.entries(editor.items)) {
            if (item.quantity > 0 && !item.isCustomSize) {
                editorActivePrices[`pricing_${item.name}`] = item.price;
            } else if (item.quantity > 0 && item.isCustomSize) {
                editorActiveCustomPrices[`pricing_${item.customSize.closestVariant}`] = item.price;
            }
        }
        if (!Object.values(editor.items).find((item: any) => item.quantity > 0)) {
            if (Object.keys(editorActivePrices).length === 0) {
                editorActivePrices[`pricing_${initialData.variant}`] = 0.01;
            }
        }
        setActivePrices(editorActivePrices);
        setActiveCustomPrices(editorActiveCustomPrices);
    }, [editor.items, initialData]);

    const handleColumns = () => {
        const { quantities } = product.pricing;
        const columns: any = [{
            title: 'Qty',
            dataIndex: 'size',
            key: 'price_chart_size_col',
            className: 'text-nowrap first-col',
            fixed: 'left',
        }];
        columns.push(...quantities.map((qty: number) => ({
            title: qty + '+',
            dataIndex: qty,
            className: 'text-nowrap',
            key: 'price_chart_qty_col_' + qty,
        })));

        const lastQty = 2500;
        columns.push({
            title: `${lastQty}+`,
            dataIndex: 'bulk',
            key: `price_chart_bulk_col`,
            className: 'text-nowrap',
            width: 280,
            render: () => (
                <BulkCol>
                    <Button size="small" type="link" className="p-0 pe-1" onClick={() => setIsBulkOrderModalOpen(true)}>
                        Contact us
                    </Button>
                    for bulk pricing!
                </BulkCol>
            ),
        });

        setColumns(columns);
        return columns;
    }

    const handleData = () => {
        let rows: any = [];
        for (const variant in variants) {
            if (activePrices[variant]) {
                const pricing = variants[variant].pricing;
                if (pricing) {
                    const dataItem: any = {
                        key: `${variant}_data`,
                        size: activePrices[variant] === 0.01 ?
                            <span className="first-col fw-medium">Price</span> :
                            <span className="first-col">{getVariantLabel(variant, config.product.variants)}</span>,
                    };
                    for (const column of columns) {
                        if (column.dataIndex === 'size' || column.dataIndex === 'bulk') continue;
                        const tier = `qty_${column.dataIndex}`;
                        const amount = pricing[tier] ? pricing[tier]['usd'] : 0;
                        dataItem[column.dataIndex] = <Value
                            amount={amount}
                            active={activePrices[variant] === amount}
                        />;
                    }
                    rows.push(dataItem);
                }
            }
        }
        setData(rows);
    }

    const handleCustomData = () => {
        let rows: any = [];
        const variants = product.pricing.variants;
        const customSizes = Object.values(editor.items).filter((it: any) => it.isCustomSize === true);

        customSizes.forEach((size) => {
            const closestVariant = size.customSize.closestVariant;

            for (const variantKey in variants) {
                const variant = variants[variantKey];
                if (closestVariant !== variant.label || size.quantity == 0) continue;

                if (activeCustomPrices[variantKey]) {
                    const pricing = variant.pricing;

                    if (pricing) {
                        let label = closestVariant === variant.label ? `CUSTOM SIZE(${size.name})` : variant.label;

                        const dataItem: any = {
                            key: `${variantKey}_custom_${size.id}`,
                            size: <span className="first-col">{label}</span>,
                        };

                        columns.forEach((column) => {
                            if (column.dataIndex === 'size' || column.dataIndex === 'bulk') return;
                            const tier = `qty_${column.dataIndex}`;
                            const amount = pricing[tier] ? pricing[tier]['usd'] : 0;
                            dataItem[column.dataIndex] = (
                                <Value
                                    amount={amount}
                                    active={activeCustomPrices[variantKey] === amount}
                                />
                            );
                        });
                        rows.push(dataItem);
                    }
                }
            }
        });

        setCustomData(rows);
    };

    return (
        <>
            <StyledTable
                size="small"
                pagination={false}
                dataSource={[...data, ...customData]}
                columns={columns}
                locale={{
                    emptyText: "Please add 1 or more to your quantity to see the prices.",
                }}
                scroll={{ y: 100, x: true }}
            />
        </>
    );
}

export default memo(PriceChart);