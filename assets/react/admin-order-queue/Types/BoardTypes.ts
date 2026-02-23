import { OrderDetails } from "@react/admin-order-queue/redux/reducer/config/interface";

export type ColumnType = {
    title: string;
    columnId: string;
    listId: string;
    shipBy: string;
    items: OrderDetails[];
};

export type ColumnMap = { [columnId: string]: ColumnType };

export type Outcome =
    | {
        type: "column-reorder";
        columnId: string;
        startIndex: number;
        finishIndex: number;
    }
    | {
        type: "card-reorder";
        columnId: string;
        startIndex: number;
        finishIndex: number;
    }
    | {
        type: "card-move";
        startColumnId: string;
        finishColumnId: string;
        itemIndexInStartColumn: number;
        itemIndexInFinishColumn: number;
    } 
    | {
        type: "card-remove";
        columnId: string;
        cardId: string;
    };

export type Trigger = "pointer" | "keyboard";

export type Operation = {
    trigger: Trigger;
    outcome: Outcome;
};

export type BoardState = {
    columnMap: ColumnMap;
    orderedColumnIds: string[];
    lastOperation: Operation | null;
};