// Sorting function (unchanged)
function sortProductsByPrice(sectionId, order) {
    const section = document.getElementById(sectionId);
    if (!section) return false;
    const grid = section.querySelector('.row.g-4');
    if (!grid) return false;
    const items = Array.from(grid.querySelectorAll('.col-lg-4'));
    if (items.length === 0) return false;

    items.sort((a, b) => {
        const priceElementA = a.querySelector('.text-primary.fw-bold');
        const priceElementB = b.querySelector('.text-primary.fw-bold');
        const priceA = priceElementA ? parseFloat(priceElementA.textContent.replace(/[₱\s,]/g, '')) || 0 : 0;
        const priceB = priceElementB ? parseFloat(priceElementB.textContent.replace(/[₱\s,]/g, '')) || 0 : 0;
        return order === 'min-max' ? priceA - priceB : priceB - priceA;
    });

    grid.innerHTML = '';
    items.forEach(item => grid.appendChild(item));
    return true;
}

document.addEventListener("DOMContentLoaded", () => {
    document.getElementById('sort-price-btn').addEventListener('click', function() {
        const sortOrder = document.getElementById('price-sort').value;

        document.querySelectorAll('.category-section').forEach(section => {
            const productsContainer = section.querySelector('.category-products');
            const products = Array.from(productsContainer.children);

            products.sort((a, b) => {
                const priceA = parseFloat(a.dataset.price);
                const priceB = parseFloat(b.dataset.price);
                return sortOrder === 'min-max' ? priceA - priceB : priceB - priceA;
            });

            // Clear container and append sorted products
            productsContainer.innerHTML = '';
            products.forEach(product => productsContainer.appendChild(product));
        });
    });

document.addEventListener("DOMContentLoaded", () => {
    const searchBtn = document.getElementById("search-btn");
    const searchInput = document.getElementById("product-search-input");
    const searchResults = document.getElementById("search-results");
    const searchModal = new bootstrap.Modal(document.getElementById('searchModal'));

    searchBtn.addEventListener("click", () => {
        const query = searchInput.value.trim();
        if (!query) {
            searchResults.innerHTML = `<p class="text-center text-danger">Please enter a search term.</p>`;
            searchModal.show();
            return;
        }

        fetch(`../search_products.php?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if (data.length === 0) {
                    searchResults.innerHTML = `<p class="text-center">No products found.</p>`;
                } else {
                    let html = '<div class="row g-3">';
                    data.forEach(product => {
                        html += `
                        <div class="col-md-6">
                            <div class="card">
                                <img src="${product.image_url}" class="card-img-top" alt="${product.name}">
                                <div class="card-body">
                                    <h5 class="card-title">${product.name}</h5>
                                    <p class="card-text">${product.description}</p>
                                    <p class="card-text fw-bold">₱${parseFloat(product.price).toFixed(2)}</p>
                                </div>
                            </div>
                        </div>`;
                    });
                    html += '</div>';
                    searchResults.innerHTML = html;
                }
                searchModal.show();
            })
            .catch(err => {
                console.error("Search failed:", err);
                searchResults.innerHTML = `<p class="text-danger text-center">Error fetching results.</p>`;
                searchModal.show();
            });
    });
});

document.getElementById('product-search-btn').addEventListener('click', function() {
    const query = document.getElementById('product-search-input').value.trim();
    if(query === '') return alert('Please enter a product name.');

    fetch('search_products.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'query=' + encodeURIComponent(query)
    })
    .then(res => res.json())
    .then(data => {
        const resultsContainer = document.getElementById('search-results');
        resultsContainer.innerHTML = '';

        if(data.products.length === 0) {
            resultsContainer.innerHTML = '<p class="text-center">No products found.</p>';
        } else {
                    data.products.forEach(prod => {
                        resultsContainer.innerHTML += `
                            <div class="card mb-3 d-flex flex-column">
                                <div class="row g-0 align-items-center">
                                    <div class="col-md-3">
                                        <img src="${prod.image_url}" class="img-fluid rounded-start" alt="${prod.name}">
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card-body">
                                            <h5 class="card-title">${prod.name}</h5>
                                            <p class="card-text">${prod.description}</p>
                                            <p class="card-text fw-bold">₱${parseFloat(prod.price).toFixed(2)}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 d-flex flex-column text-center">
                                        <!-- Add to Cart Form -->
                                        <form method="post" action="../cart/add_to_cart.php" class="mt-auto">
                                            <input type="hidden" name="id" value="${prod.id}">
                                            <input type="hidden" name="name" value="${prod.name.replace(/"/g, '&quot;')}">
                                            <input type="hidden" name="price" value="${prod.price}">
                                            <input type="hidden" name="image_url" value="${prod.image_url}">
                                            <input type="hidden" name="redirect" value="${window.location.pathname}">
                                            
                                            <div class="d-flex align-items-center mb-3">
                                                <input type="number" name="quantity" value="1" min="1" max="${prod.quantity}" class="form-control me-2" style="width: 80px;">
                                                <button type="submit" name="add_to_cart" class="add-to-cart-btn">
                                                    Add to Cart
                                                </button>
                                            </div>
                                        </form>

                                        <!-- Add to Favorites Form -->
                                        <form method="POST" action="../favorites/add_favorites.php">
                                            <input type="hidden" name="product_id" value="${prod.id}">
                                            <input type="hidden" name="product_name" value="${prod.name.replace(/"/g, '&quot;')}">
                                            <input type="hidden" name="product_price" value="${prod.price}">
                                            <input type="hidden" name="product_image" value="${prod.image_url}">
                                            <button type="submit" class="btn btn-outline-danger w-100">
                                                <i class="bi bi-heart"></i> Add to Favorites
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }


        // Show modal
        const searchModal = new bootstrap.Modal(document.getElementById('searchModal'));
        searchModal.show();
    })
    .catch(err => alert('Error searching products'));
});



});
