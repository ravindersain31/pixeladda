import { CaseReducer } from "@reduxjs/toolkit";
import AppState from "@react/admin-order-queue/redux/reducer/interface.ts";
import { updateTabsPrintersCount } from "@react/admin-order-queue/helper";

const updatePrintersCount: CaseReducer = (state: AppState, action) => {
    const { printers } = action.payload;

    if (printers && Object.keys(printers).length > 0) {
        const printersArray = Object.values(printers);
        
        const printer: any = printersArray.find((p: any) => p.label === state.config.printer);

        if (printer) {
            state.config.orderCount = printer.orderCount;
            state.config.printers = printers;
        }

        if (printersArray.length > 0) {
            updateTabsPrintersCount(printersArray);
        }
    }
};

export default updatePrintersCount;