import LightCart from "assets/web/home/components/Cart/LightCart";

const Cart = (props: any) => {
  return (
    <>
      <LightCart cartData={props.cartData} />
    </>
  );
};

export default Cart;
