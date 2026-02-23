import ConfigState from "@wireStake/redux/reducer/config/interface";
import CartState from "@wireStake/redux/reducer/cart/interface";

export default interface AppState {
    config: ConfigState;
    cartStage: CartState;
}