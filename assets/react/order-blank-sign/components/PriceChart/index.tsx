import React, { memo, useEffect, useMemo, useState } from "react";
import { useAppSelector } from "@orderBlankSign/hook";
import { TableColumn } from "@orderBlankSign/utils/interface";
import Value from "@orderBlankSign/components/PriceChart/Value";
import { StyledTable, BulkCol } from "./styled";
import { shallowEqual } from "react-redux";
import { Button } from "antd";
import { getEffectiveQuantity } from "@react/order-blank-sign/utils/helper";

interface Props {
    setIsBulkOrderModalOpen: React.Dispatch<React.SetStateAction<boolean>>;
}

const PriceChart = ({ setIsBulkOrderModalOpen }: Props) => {
    const product = useAppSelector(state => state.config.product, shallowEqual);
    const cart = useAppSelector(state => state.config.cart, shallowEqual);
    const cartStage = useAppSelector(state => state.cartStage, shallowEqual);
    const variants = product.variants;

    const [variantsPricing, setVariantsPricing] = useState(product.pricing.variants);
    const [tableColumns, setTableColumns] = useState<TableColumn[]>([]);
    const [tableData, setTableData] = useState<any[]>([]);
    const [activeTiers, setActiveTiers] = useState<Record<string, number>>({});
    const currentItemQuantity = cart.currentItemQuantity || 0;
    const currentFrameQuantity = cart.currentFrameQuantity || {};

    useEffect(() => {
        setVariantsPricing(product.pricing.variants);
        generateColumns();
    }, [product]);

    useEffect(() => {
        const active: Record<string, number> = {};

        for (const item of Object.values(cartStage.items)) {
            if (item.quantity > 0) {
                const variantKey = `pricing_${item.name}`;
                const quantities = product.pricing.quantities;

                const itemQty = getEffectiveQuantity(item, cart, cartStage);

                const applicableTier = quantities.filter(qty => qty <= itemQty).pop();

                if (applicableTier !== undefined) {
                    active[variantKey] = applicableTier as number;
                }
            }
        }

        setActiveTiers(active);
    }, [cartStage.items, product.pricing.quantities, cart]);

    useEffect(() => {
        generateTableData();
    }, [variantsPricing, tableColumns, activeTiers]);

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

        for (const v of variants) {
            // normalize name (lowercase, remove spaces just in case)
            const normalizedName = v.name.toLowerCase().replace(/\s+/g, "");
            const variantKey = `pricing_${normalizedName}`;

            const variantPricing = variantsPricing[variantKey];

            if (!variantPricing || !variantPricing.pricing || Object.keys(variantPricing.pricing).length === 0) {
                continue; // skip if pricing not found
            }

            const row: any = {
                key: `${variantKey}_row`,
                size: <span className="first-col">{v.label}</span>,
            };

            let hasPrice = false;

            for (const column of tableColumns) {
                if (column.dataIndex === "size" || column.dataIndex === "bulk") continue;

                const tierKey = `qty_${column.dataIndex}`;
                const amount = variantPricing.pricing[tierKey]?.usd ?? 0;

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
            scroll={{ x: true }}
        />
    );
};

export default memo(PriceChart);
