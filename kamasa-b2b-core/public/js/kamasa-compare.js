(function (window, document, $, data) {
    'use strict';

    if (!data || (!data.ajax_url && !data.ajaxUrl)) {
        return;
    }

    var ajaxUrl = data.ajax_url || data.ajaxUrl;
    var maxItems = parseInt(data.maxItems, 10);
    if (isNaN(maxItems) || maxItems < 1) {
        maxItems = 3;
    }

    function setButtonState(button, isSelected) {
        if (!button) {
            return;
        }

        var label = button.querySelector('.kamasa-compare-button__text');
        var icon = button.querySelector('.kamasa-compare-button__icon');
        var addLabel = button.getAttribute('data-label-add') || '';
        var removeLabel = button.getAttribute('data-label-remove') || '';
        var iconAdd = button.getAttribute('data-icon-add') || '';
        var iconRemove = button.getAttribute('data-icon-remove') || '';

        button.dataset.selected = isSelected ? 'true' : 'false';
        button.setAttribute('aria-pressed', isSelected ? 'true' : 'false');

        if (isSelected) {
            button.classList.add('added');
            if (label) {
                label.textContent = removeLabel || addLabel;
            }
            if (icon) {
                icon.classList.remove(iconAdd);
                if (iconRemove) {
                    icon.classList.add(iconRemove);
                }
            }
        } else {
            button.classList.remove('added');
            if (label) {
                label.textContent = addLabel || removeLabel;
            }
            if (icon) {
                icon.classList.remove(iconRemove);
                if (iconAdd) {
                    icon.classList.add(iconAdd);
                }
            }
        }
    }

    function updateRelatedButtons(productId, isSelected) {
        document.querySelectorAll('.kamasa-compare-button[data-product-id="' + productId + '"]').forEach(function (relatedButton) {
            setButtonState(relatedButton, isSelected);
        });
    }

    function updateIndicator(count) {
        var indicator = document.getElementById('kamasa-compare-indicator');
        if (!indicator) {
            return;
        }

        var countElement = indicator.querySelector('#kamasa-compare-count');
        if (countElement) {
            countElement.textContent = count;
        }

        if (count > 0) {
            indicator.classList.remove('is-hidden');
            indicator.removeAttribute('aria-hidden');
        } else {
            indicator.classList.add('is-hidden');
            indicator.setAttribute('aria-hidden', 'true');
        }

        if (typeof data === 'object') {
            data.currentCount = count;
        }
    }

    function toggleLoading(button, isLoading) {
        if (!button) {
            return;
        }

        if (isLoading) {
            button.classList.add('is-loading');
            button.setAttribute('disabled', 'disabled');
        } else {
            button.classList.remove('is-loading');
            button.removeAttribute('disabled');
        }
    }

    function showAlert(message) {
        if (message) {
            window.alert(message);
        }
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('.kamasa-compare-button');

        if (!button) {
            return;
        }

        event.preventDefault();

        var productId = parseInt(button.getAttribute('data-product-id'), 10);
        if (!productId) {
            return;
        }

        var isSelected = button.getAttribute('data-selected') === 'true';
        var currentCount = parseInt(data.currentCount, 10);
        if (isNaN(currentCount) && typeof data.current_count !== 'undefined') {
            currentCount = parseInt(data.current_count, 10);
        }
        if (isNaN(currentCount)) {
            currentCount = 0;
        }

        if (!isSelected && currentCount >= maxItems) {
            showAlert(data.messages && data.messages.limitReached ? data.messages.limitReached : 'Limit reached');
            return;
        }

        toggleLoading(button, true);

        $.ajax({
            url: ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'kamasa_toggle_compare',
                product_id: productId,
                nonce: data.nonce
            }
        })
            .done(function (response) {
                if (!response || !response.success || !response.data) {
                    showAlert(data.messages && data.messages.genericError ? data.messages.genericError : 'Error');
                    return;
                }

                var action = response.data.action;
                var count = parseInt(response.data.count, 10);
                if (isNaN(count)) {
                    count = 0;
                }

                if (action === 'limit_reached') {
                    updateIndicator(count);
                    showAlert(data.messages && data.messages.limitReached ? data.messages.limitReached : 'Limit reached');
                    return;
                }

                var nextSelected = action === 'added';
                updateRelatedButtons(productId, nextSelected);
                updateIndicator(count);
            })
            .fail(function () {
                showAlert(data.messages && data.messages.genericError ? data.messages.genericError : 'Error');
            })
            .always(function () {
                toggleLoading(button, false);
            });
    });

    var initialCount = parseInt(data.currentCount, 10);
    if (isNaN(initialCount) && typeof data.current_count !== 'undefined') {
        initialCount = parseInt(data.current_count, 10);
    }
    if (isNaN(initialCount)) {
        initialCount = 0;
    }
    updateIndicator(initialCount);
})(window, document, window.jQuery, window.kamasaCompareData || {});
