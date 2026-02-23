import React from "react";
import { StyledButton } from "./styled";

interface BSDrawerProps {
    id?: string;
    heading?: string;
    children: React.ReactNode;
}

const BSDrawer = (
    {
        id = 'offcanvasEditor',
        heading = 'Offcanvas',
        children
    }: BSDrawerProps
) => {
    return <div className="offcanvas offcanvas-end w-100" id={id} aria-labelledby={`${id}_Label`}>
        <div className="offcanvas-header pb-0 pt-2">
            <h5 className="offcanvas-title" id={`${id}_Label`}>{heading}</h5>
            <div className="d-flex">
                <StyledButton tabIndex={-1} data-bs-dismiss="offcanvas" aria-label="Save">
                    Save
                </StyledButton>
                <StyledButton tabIndex={-1} data-bs-dismiss="offcanvas" aria-label="Back">
                    Back
                </StyledButton>
                <button tabIndex={-1} type="button" className="btn-close text-reset m-0" data-bs-dismiss="offcanvas" aria-label="Close"
                ></button>
            </div>
        </div>
        <div className="offcanvas-body pt-1">
            {children}
        </div>
    </div>
}

export default BSDrawer;