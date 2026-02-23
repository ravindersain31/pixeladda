import { spinnerImage } from "@react/editor/helper/editor";

const Initializing = () => {
    return (
        <div className="loading-product-editor">
            <div className="d-flex justify-content-center align-items-center">
                <img src={spinnerImage()} />
            </div>
        </div>
    );
}

export default Initializing;