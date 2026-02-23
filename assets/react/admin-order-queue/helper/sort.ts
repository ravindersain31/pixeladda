import { ASC, SortOrder } from "@react/admin-order-queue/constants/sort.constants";
import { ListProps, OrderDetails } from "../redux/reducer/config/interface";

export const sortOrders = (tasks: OrderDetails[], sortOrder: SortOrder): OrderDetails[] => {
    return [...tasks].sort((a, b) => {
        if (sortOrder === ASC) return a.sortIndex - b.sortIndex;
        return b.sortIndex - a.sortIndex;
    });
};

export const sortLists = (lists: ListProps[], sortOrder: SortOrder): ListProps[] => {
    return [...lists].sort((a, b) => {
        if (sortOrder === ASC) return new Date(a.shipBy).getTime() - new Date(b.shipBy).getTime();
        return new Date(b.shipBy).getTime() - new Date(a.shipBy).getTime();
    });
}