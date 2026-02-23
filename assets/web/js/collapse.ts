const cartItemCollection = document.getElementsByClassName('cart-item-collapse');
Array.from(cartItemCollection).map((item) => {
    item.addEventListener('hidden.bs.collapse', event => changeText(event, '<i class="far fa-eye"></i>'))
    item.addEventListener('shown.bs.collapse', event => changeText(event, '<i class="far fa-eye-slash"></i>'));
});

function changeText(event: Event, text: string) {
    // @ts-ignore
    const id = event.target?.id;
    const button = document.querySelector(`[data-bs-target="#${id}"]`);
    if(button) {
        button.innerHTML = text;
    }
}