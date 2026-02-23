import { Row, message, Modal } from "antd";
import axios, { AxiosRequestConfig } from "axios";
import { useEffect, useState } from "react";
import { LightCartFooter, CartItemWrapper, DropDownMenu, LightCartWrapper } from "./styled";
// import useApiUrlDetector from "../../hooks/useApiUrlDetector";


const LightCart = (props: any) => {
  const [cartData, setCartData] = useState<any>(props.cartData);
  const [modalVisible, setModalVisible] = useState<boolean>(false);
  const [itemToDelete, setItemToDelete] = useState<string | null>(null);
  const [confirmLoading, setConfirmLoading] = useState<boolean>(false);


  const fetchCartData = async () => {
    try {
      const response = await axios.get("/api/light-cart");
      const cartData = response.data;
      setCartData(cartData);
    } catch (error) {
      console.error("Error fetching cart data:", error);
      message.error("Failed to fetch cart data. Please try again.");
    }
  };

  const handleDeleteItem = (itemId: string) => {
    setItemToDelete(itemId);
    setModalVisible(true);
  };

  const confirmDeleteItem = async () => {
    setConfirmLoading(true);
    try {
      const response = await axios.post("/api/remove-from-cart", {
        cartId: cartData.cartId,
        itemId: itemToDelete,
      });

      if (response.data.success === true) {
        fetchCartData();
        message.success(response.data.message);

        const pathsToReload = ["/cart/", "/editor", "/checkout"];
        const currentPath = window.location.pathname;

        if (pathsToReload.includes(currentPath)) {
          window.location.reload();
        }
      } else {
        message.error("Failed to remove item from cart. " + response.data.message);
      }
    } catch (error) {
      console.error("Error removing item from cart:", error);
      message.error("Failed to remove item from cart. Please try again.");
    } finally {
      setConfirmLoading(false);
      setModalVisible(false);
      setItemToDelete(null);
    }
  };

  const cartSvgIcon = (
    <svg
      xmlns="http://www.w3.org/2000/svg"
      width="24"
      height="24"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
      className="feather feather-shopping-cart"
    >
      <circle cx="9" cy="21" r="1" />
      <circle cx="20" cy="21" r="1" />
      <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
    </svg>
  );

  const removeSvgIcon = (
    <svg
      className="remove-cart-svg"
      xmlns="http://www.w3.org/2000/svg"
      width="24"
      height="24"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
    >
      <circle cx="12" cy="12" r="10" />
      <line x1="15" y1="9" x2="9" y2="15" />
      <line x1="9" y1="9" x2="15" y2="15" />
    </svg>
  );

  const hasItems = cartData && cartData.items.length > 0;

  useEffect(() => {
    const $button = $("#shoppingCart");
    const $cartPreview = $(".menu-cart-preview-shopping-cart");
    let hideTimeout: any;

    // Hover (DESKTOP only)
    $button.add($cartPreview).on("mouseenter", function () {
      if (window.innerWidth > 991) {
        clearTimeout(hideTimeout);
        $cartPreview.stop(true, true).fadeIn("fast");
      }
    });

    $button.add($cartPreview).on("mouseleave", function () {
      if (window.innerWidth > 991) {
        hideTimeout = setTimeout(() => {
          $cartPreview.stop(true, true).fadeOut("fast");
        }, 300);
      }
    });

    // CLICK toggle (desktop + mobile)
    $button.on("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      if ($cartPreview.is(":visible")) {
        $cartPreview.fadeOut("fast");
      } else {
        $cartPreview.fadeIn("fast");
      }
    });

    // CLICK OUTSIDE close
    $(document).on("click.cartPreview", function (e) {
      if (
        !$(e.target).closest(
          "#shoppingCart, .menu-cart-preview-shopping-cart"
        ).length
      ) {
        $cartPreview.fadeOut("fast");
      }
    });

    return () => {
      $button.add($cartPreview).off();
      $(document).off("click.cartPreview");
    };
  }, []);


  return (
    <>
      <LightCartWrapper>
        <button
          id="shoppingCart"
          className="nav-link  dropdown-toggle shopping-cart"
          type="button"
         >
          {cartSvgIcon}
          <span className="desktop-only">Shopping Cart</span>
          {cartData.items.length > 0 && (
            <span className="start-100 badge rounded-pill">
              {cartData.items.length}
            </span>
          )}
        </button>
        <DropDownMenu className="dropdown-menu dropdown-menu-end menu-cart-preview-shopping-cart">
          <div className="light-cart p-0">
            {cartData && cartData.items.length > 0 ? (
              <CartItemWrapper>
                <div className="row light-cart-totals p-3 pt-2">
                  <div className="col-3 text-start p-0">
                    <a href={cartData.cart}>
                      {cartSvgIcon}
                      <span>{cartData.items.length}</span>            
                    </a>
                  </div>
                  <div className="col-9 text-end p-0">
                    Total:{" "}
                    <strong>${cartData.cartTotalAmount.toFixed(2)}</strong>
                  </div>
                </div>
                <div className="light-cart-items px-2">
                  {cartData.items &&
                    Object.values(cartData.items).map((item: any, index: number) => (
                      <div
                      key={item.id}
                      className={`light-cart-item d-flex justify-center justify-content-start align-items-center text-align-start ${index === Object.values(cartData.items).length - 1 ? '' : 'border-1 border-bottom'}`}
                      >
                        <div className="text-center">
                          <a href={item.editUrl} type="button">
                            <img
                              className="img-responsive light-item-img"
                              src={item.data.image}
                              alt="Image"
                            />
                          </a>
                        </div>
                        <div>
                          <div className="light-cart-item-header">
                            <a href={item.editUrl} type="button">
                              {item.parentSku} {item.data.customSize && item.data.customSize.isSample ? `(${item.data.customSize.parentSku})` : ''}
                            </a>
                          </div>
                          <div className="light-cart-item-info d-flex justify-content-between align-items-center">
                            <div className="price">
                              ${item.data.totalAmount.toFixed(2)}
                            </div>
                            <div className="variant-name">
                              {item.categorySlug === "wire-stake"
                                ? (item.data.label.match(/^(Standard|Premium|Single)/) ? item.data.label.match(/^(Standard|Premium|Single)/)[0] : null)
                                : item.data.label || item.data.name}
                            </div>
                            <div className="quantity">Qty: {item.quantity}</div>
                          </div>
                        </div>
                        <div className="remove">
                          <button
                            type="button"
                            onClick={() => handleDeleteItem(item.id)}
                            className="p-0"
                          >
                            {removeSvgIcon}
                          </button>
                        </div>
                      </div>
                    ))}
                </div>
              </CartItemWrapper>
            ) : (
              <CartItemWrapper>
                <div className="row no-items p-3 pt-2">
                  <div className="col-4 text-start p-0">
                    <a href={cartData.cart}>
                      {cartSvgIcon}
                      <span>{0}</span>
                    </a>
                  </div>
                  <div className="col-8 text-end p-0">
                    Total: <strong>$0</strong>
                  </div>
                </div>
                <div className="card-body text-center">
                  <span className="p-2">Your shopping cart is empty!</span>
                </div>
              </CartItemWrapper>
            )}
            
            <LightCartFooter>
              <a
                href={cartData.cart}
                className={`btn btn-view-cart ${hasItems ? "btn-cart-two" : "btn-cart-single"}`}
              >
                View Cart
              </a>

              {hasItems && (
                <a href={cartData.checkout} className="btn btn-view-cart btn-cart-two">
                  Checkout
                </a>
              )}
            </LightCartFooter>
          </div>
          <Modal
            title="Confirm"
            open={modalVisible}
            onOk={confirmDeleteItem}
            onCancel={() => setModalVisible(false)}
            confirmLoading={confirmLoading}
            okText="Delete"
            cancelText="Cancel"
            centered
          >
            <p>Are you sure you want to remove this item from the cart?</p>
          </Modal>
        </DropDownMenu>
      </LightCartWrapper>
    </>
  );
};

export default LightCart;
