<h1>Products</h1>

<div style="margin-bottom: 2rem;">
    <form action="/products/search" method="get" style="display: flex; gap: 10px;">
        <input type="text" name="q" placeholder="Search products..." style="padding: 8px; flex: 1;">
        <button type="submit" style="padding: 8px 20px; background: #3498db; color: white; border: none; cursor: pointer;">Search</button>
    </form>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px;">
    {if $products}
        {foreach from=$products item=product}
        <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
            <h3>{$product.name}</h3>
            <p style="color: #666; font-size: 0.9rem;">{$product.description|truncate:80}</p>
            <p style="font-size: 1.2rem; color: #2c3e50; margin: 10px 0;">
                <strong>${$product.price}</strong>
            </p>
            <p style="color: #666; font-size: 0.9rem;">Stock: {$product.stock}</p>
            <button style="width: 100%; padding: 8px; background: #27ae60; color: white; border: none; cursor: pointer; border-radius: 3px;">Add to Cart</button>
        </div>
        {/foreach}
    {else}
        <p>No products found.</p>
    {/if}
</div>
