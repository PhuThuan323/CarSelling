<h1>Shopping Cart</h1>

{if $cart_items}
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
        <thead>
            <tr style="background: #ecf0f1;">
                <th style="padding: 10px; text-align: left; border-bottom: 2px solid #bdc3c7;">Product</th>
                <th style="padding: 10px; text-align: center; border-bottom: 2px solid #bdc3c7;">Quantity</th>
                <th style="padding: 10px; text-align: right; border-bottom: 2px solid #bdc3c7;">Action</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$cart_items key=product_id item=quantity}
            <tr style="border-bottom: 1px solid #ecf0f1;">
                <td style="padding: 10px;">Product #{$product_id}</td>
                <td style="padding: 10px; text-align: center;">{$quantity}</td>
                <td style="padding: 10px; text-align: right;">
                    <button style="background: #e74c3c; color: white; border: none; padding: 5px 10px; cursor: pointer;">Remove</button>
                </td>
            </tr>
            {/foreach}
        </tbody>
    </table>

    <div style="text-align: right; margin-top: 2rem;">
        <a href="/cart/checkout" style="background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block;">Proceed to Checkout</a>
    </div>
{else}
    <p>Your cart is empty. <a href="/products" style="color: #3498db;">Continue shopping</a></p>
{/if}
