import ConfigState from "@orderBlankSign/redux/reducer/config/interface";
import CartState from "@orderBlankSign/redux/reducer/cart/interface";

export default interface AppState {
    config: ConfigState;
    cartStage: CartState;
}