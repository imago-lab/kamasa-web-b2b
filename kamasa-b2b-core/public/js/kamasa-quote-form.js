(function () {
    'use strict';

    function setStatusMessage(container, message, type) {
        if (!container) {
            return;
        }

        container.textContent = message || '';
        container.classList.remove('is-error', 'is-success');

        if (type) {
            container.classList.add(type);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('form-cotizacion');

        if (!form) {
            return;
        }

        var messageContainer = form.querySelector('.kamasa-quote-form__messages');

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            if (!window.kamasaQuoteForm || !kamasaQuoteForm.ajaxUrl) {
                var fallbackMessage = (window.kamasaQuoteForm && kamasaQuoteForm.missingAjaxMessage) ? kamasaQuoteForm.missingAjaxMessage : 'AJAX endpoint not available.';
                setStatusMessage(messageContainer, fallbackMessage, 'is-error');
                return;
            }

            setStatusMessage(messageContainer, '');

            var formData = new FormData(form);
            formData.append('action', 'kamasa_enviar_cotizacion');

            fetch(kamasaQuoteForm.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }

                    return response.json();
                })
                .then(function (data) {
                    if (data.success) {
                        form.reset();
                        var successMessage = (data.data && data.data.message) ? data.data.message : (kamasaQuoteForm.successMessage || 'Solicitud enviada correctamente.');
                        setStatusMessage(messageContainer, successMessage, 'is-success');
                    } else {
                        var errorMessage = (data.data && data.data.message) ? data.data.message : (kamasaQuoteForm.errorMessage || 'No se pudo enviar la solicitud.');
                        setStatusMessage(messageContainer, errorMessage, 'is-error');
                    }
                })
                .catch(function () {
                    var fallbackError = (kamasaQuoteForm && kamasaQuoteForm.errorMessage) ? kamasaQuoteForm.errorMessage : 'No se pudo enviar la solicitud.';
                    setStatusMessage(messageContainer, fallbackError, 'is-error');
                });
        });
    });
})();
