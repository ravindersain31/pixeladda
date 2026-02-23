export enum WarehouseOrderStatusEnum {
    READY = "READY",
    NESTING = "NESTING",
    NESTED = "NESTED",
    PRINTING = "PRINTING",
    PAUSED = "PAUSED",
    FIXING = "FIXING",
    PRINTED = "PRINTED",
    CUTTING = "CUTTING",
    PACKING = "PACKING",
    DONE = "DONE",
}

export const WarehouseOrderStatus: Record<
    WarehouseOrderStatusEnum,
    { label: string; color: string }
> = {
    [WarehouseOrderStatusEnum.READY]: {
        label: "Pending",
        color: "#95b502",
    },
    [WarehouseOrderStatusEnum.NESTING]: {
        label: "Nesting",
        color: "#0689ff",
    },
    [WarehouseOrderStatusEnum.NESTED]: {
        label: "Nested",
        color: "#0e00c7",
    },
    [WarehouseOrderStatusEnum.PRINTING]: {
        label: "Printing",
        color: "#970bcc",
    },
    [WarehouseOrderStatusEnum.PAUSED]: {
        label: "Paused",
        color: "#ff7400",
    },
    [WarehouseOrderStatusEnum.FIXING]: {
        label: "Fixing",
        color: "#f90000",
    },
    [WarehouseOrderStatusEnum.PRINTED]: {
        label: "Printed",
        color: "#3ae728",
    },
    [WarehouseOrderStatusEnum.CUTTING]: {
        label: "Cutting",
        color: "#c0b800",
    },
    [WarehouseOrderStatusEnum.PACKING]: {
        label: "Packing",
        color: "#494949",
    },
    [WarehouseOrderStatusEnum.DONE]: {
        label: "Done",
        color: "#085600",
    },
};
