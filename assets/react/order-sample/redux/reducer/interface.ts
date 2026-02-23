import ConfigState from "@orderSample/redux/reducer/config/interface";
import CartState from "@orderSample/redux/reducer/cart/interface";

export default interface AppState {
    config: ConfigState;
    cartStage: CartState;
}