jQuery(function ($) {
    var progress = $('.wp-delete-products-progress'),
        progressBar = $('.wp-delete-products-progress-bar'),
        progressLabel = $('.wp-delete-products-progress-label'),
        progressWidth = progress.width(),
        productsCount = parseInt($('#wp-delete-products-count').val()),
        productsDeleted = 0,
        chunkSize = 10,
        ajaxInProgress = false;

    function deleteProductsChunk(offset, nonce) {
        $.ajax({
            url: wp_delete_products.ajax_url,
            method: 'POST',
            data: {
                action: 'wp_delete_products_chunk',
                nonce: nonce,
                offset: offset,
                limit: chunkSize,
            },
            beforeSend: function () {
                ajaxInProgress = true;
            },
            success: function (response) {
                if (response.success) {
                    productsDeleted += chunkSize;
                    var percent = (productsDeleted / productsCount) * 100;
                    progressBar.css('width', percent + '%');
                    progressLabel.html(
                        wp_delete_products.progress.replace(
                            '%s',
                            percent.toFixed(2)
                        )
                    );
                    if (productsDeleted < productsCount) {
                        deleteProductsChunk(offset + chunkSize, nonce);
                    } else {
                        ajaxInProgress = false;
                        progress.addClass('done');
                        progressLabel.html(wp_delete_products.done);
                        setTimeout(function () {
                            location.reload();
                        }, 2000);
                    }
                } else {
                    alert(response.data.message);
                }
            },
            error: function () {
                alert(wp_delete_products.error);
            },
            complete: function () {
                ajaxInProgress = false;
            },
        });
    }

    $('#wp-delete-products-button').on('click', function (e) {
        e.preventDefault();

        if (ajaxInProgress) {
            return;
        }

        if (!confirm(wp_delete_products.confirm)) {
            return;
        }

        progress.removeClass('done');
        progressBar.css('width', '0');
        progressLabel.html(wp_delete_products.progress.replace('%s', '0.00'));

        var nonce = wp_delete_products.nonce,
            offset = 0;

        deleteProductsChunk(offset, nonce);
    });
});
