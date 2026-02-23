import React, { memo, useEffect, useState } from "react";
import { useAppSelector } from "@wireStake/hook";
import { getVariantLabel } from "@wireStake/utils/helper";
import { TableColumn } from "@wireStake/utils/interface";
import Value from "@wireStake/components/PriceChart/Value";
import { StyledTable, BulkCol } from "./styled";
import { shallowEqual } from "react-redux";
import { Button } from "antd";

interface Props {
    setIsBulkOrderModalOpen: React.Dispatch<React.SetStateAction<boolean>>;
}

const PriceChart = ({ setIsBulkOrderModalOpen }: Props) => {
    const product = useAppSelector(state => state.config.product, shallowEqual);
    const cart = useAppSelector(state => state.config.cart, shallowEqual);
    const cartStage = useAppSelector(state => state.cartStage, shallowEqual);
    const variants = product.variants;

    const [frameVariants, setFrameVariants] = useState(product.pricing.frames);
    const [tableColumns, setTableColumns] = useState<TableColumn[]>([]);
    const [tableData, setTableData] = useState<any[]>([]);
    const [activeTiers, setActiveTiers] = useState<Record<string, number>>({});
    const currentItemQuantity = cart.currentItemQuantity || 0;
    const currentFrameQuantity = cart.currentFrameQuantity || {};

    // Update frame variants and columns on product change
    useEffect(() => {
        setFrameVariants(product.pricing.frames);
        generateColumns();
    }, [product]);

    // Generate active tiers based on cart quantities
    useEffect(() => {
        const active: Record<string, number> = {};

        for (const item of Object.values(cartStage.items)) {
            if (item.quantity > 0) {
                const variantKey = `pricing_${item.name}`;
                const quantities = product.pricing.quantities;

                let itemQty = Number(item.quantity + Number(cart.totalFrameQuantity[item.name] || 0));

                if (cart.totalFrameQuantity[item.name] !== undefined && currentFrameQuantity[item.name] !== undefined) {
                    itemQty = item.quantity + ((cart.totalFrameQuantity[item.name] || 0) - (currentFrameQuantity[item.name] || 0));
                }

                const applicableTier = quantities.filter(qty => qty <= itemQty).pop();

                if (applicableTier !== undefined) {
                    active[variantKey] = applicableTier as number;
                }
            }
        }

        setActiveTiers(active);
    }, [cartStage.items, product.pricing.quantities]);

    // Generate table data
    useEffect(() => {
        generateTableData();
    }, [frameVariants, tableColumns, activeTiers]);

    const generateColumns = () => {
        const { quantities } = product.pricing;

        const columns: any = [
            {
                title: 'Qty',
                dataIndex: 'size',
                key: 'price_chart_size_col',
                className: 'text-nowrap first-col',
                fixed: 'left',
            },
            ...quantities.map((quantity) => ({
                title: `${quantity}+`,
                dataIndex: quantity.toString(),
                key: `qty_${quantity}`,
                className: 'text-nowrap',
            })),
        ];

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

        setTableColumns(columns);
    };

    const generateTableData = () => {
        const rows: any[] = [];

        for (const variantKey in frameVariants) {
            const variant = frameVariants[variantKey];
            const pricing = variant.pricing;

            if (variantKey === 'pricing_WIRE_STAKE_10X30' || variantKey === 'pricing_WIRE_STAKE_10X30_PREMIUM') continue;

            if (!pricing || Object.keys(pricing).length === 0) continue;

            const row: any = {
                key: `${variantKey}_row`,
                size: <span className="first-col"> {getVariantLabel(variantKey, variants)?.replace('Wire Stake', '')}</span>,
            };

            let hasPrice = false;

            for (const column of tableColumns) {
                if (column.dataIndex === 'size' || column.dataIndex === 'bulk') continue;

                const tierKey = `qty_${column.dataIndex}`;
                const amount = pricing[tierKey]?.usd ?? 0;

                if (amount > 0) hasPrice = true;

                row[column.dataIndex] = (
                    <Value
                        amount={amount}
                        active={activeTiers[variantKey]?.toString() === column.dataIndex}
                    />
                );
            }

            if (hasPrice) {
                rows.push(row);
            }
        }

        setTableData(rows);
    };

    return (
        <StyledTable
            size="small"
            pagination={false}
            dataSource={tableData}
            columns={tableColumns}
            locale={{
                emptyText: "Please add 1 or more to your quantity to see the prices.",
            }}
            scroll={{ y: 100, x: true }}
        />
    );
};

export default memo(PriceChart);
