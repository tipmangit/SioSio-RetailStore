<script>
$(document).ready(function() {
    $('.add-to-favorites-btn').click(function() {
        var button = $(this);

        $.ajax({
            url: '../favorites/add_favorites.php',
            type: 'POST',
            data: {
                product_id: button.data('product-id'),
                product_name: button.data('product-name'),
                product_price: button.data('product-price'),
                product_image: button.data('product-image')
            },
            success: function(response) {
                $('#favorite-alert').html(`
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Added to favorites!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
            },
            error: function() {
                $('#favorite-alert').html(`
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        Failed to add to favorites.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
            }
        });
    })
});
</script>
