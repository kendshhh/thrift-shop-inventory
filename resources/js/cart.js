import Echo from 'laravel-echo';

const cartChannel = Echo.channel('cart-updates');

cartChannel.listen('CartUpdated', (event) => {
    // Update the cart UI with the new data
    console.log('Cart updated:', event);
    // Example: Refresh the cart items
    window.location.reload();
});