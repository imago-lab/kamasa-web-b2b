(function ($) {
    'use strict';

    var modalSelector = '#kamasa-cross-sell-modal';
    var popupData = window.kamasaPopupData || {};

    function getLabel(key, fallback) {
        if (popupData && popupData.i18n && popupData.i18n[key]) {
            return popupData.i18n[key];
        }

        return fallback;
    }

    function ensureModal() {
        var $modal = $(modalSelector);

        if ($modal.length) {
            return $modal;
        }

        var closeLabel = getLabel('close', 'Cerrar');
        var titleLabel = getLabel('title', 'Quizás también necesites:');

        var modalHtml = '' +
            '<div id="kamasa-cross-sell-modal" class="kamasa-cross-sell-modal" aria-hidden="true">' +
                '<div class="kamasa-cross-sell-overlay" role="presentation"></div>' +
                '<div class="kamasa-cross-sell-dialog" role="dialog" aria-modal="true" aria-label="' + titleLabel + '">' +
                    '<button type="button" class="kamasa-cross-sell-close" aria-label="' + closeLabel + '">&times;</button>' +
                    '<h3 class="kamasa-cross-sell-heading">' + titleLabel + '</h3>' +
                    '<div class="kamasa-cross-sell-body"></div>' +
                '</div>' +
            '</div>';

        $modal = $(modalHtml);
        $('body').append($modal);

        return $modal;
    }

    function openModal(html) {
        if (!html) {
            return;
        }

        var $modal = ensureModal();

        $modal.find('.kamasa-cross-sell-body').html(html);
        $modal.attr('aria-hidden', 'false').addClass('is-visible');
        $('body').addClass('kamasa-cross-sell-modal-open');
    }

    function closeModal() {
        var $modal = $(modalSelector);

        if (!$modal.length) {
            return;
        }

        $modal.attr('aria-hidden', 'true').removeClass('is-visible');
        $modal.find('.kamasa-cross-sell-body').empty();
        $('body').removeClass('kamasa-cross-sell-modal-open');
    }

    $(document).on('click', modalSelector + ' .kamasa-cross-sell-close', function (event) {
        event.preventDefault();
        closeModal();
    });

    $(document).on('click', modalSelector + ' .kamasa-cross-sell-overlay', function () {
        closeModal();
    });

    $(document).on('keyup', function (event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    function requestCrossSells(productId) {
        if (!popupData.ajax_url || !popupData.nonce) {
            return;
        }

        $.ajax({
            url: popupData.ajax_url,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'kamasa_get_cross_sells',
                product_id: productId,
                _ajax_nonce: popupData.nonce
            }
        }).done(function (response) {
            if (response && response.success && response.data && response.data.html) {
                openModal(response.data.html);
            }
        }).fail(function (jqXHR, textStatus) {
            if (window.console && window.console.error) {
                window.console.error('Cross-sell request failed:', textStatus);
            }
        });
    }

    $(document.body).on('added_to_cart', function (event, fragments, cartHash, $button) {
        if (!$button || !$button.length) {
            return;
        }

        var productId = parseInt($button.data('product_id'), 10);

        if (!productId) {
            return;
        }

        requestCrossSells(productId);
    });

})(jQuery);
