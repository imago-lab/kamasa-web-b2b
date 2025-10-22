(function () {
    const onReady = (callback) => {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    };

    onReady(() => {
        const restSettings = window.wpApiSettings || {};
        const namespaceSetting = window.kamasaMyAccountSettings && window.kamasaMyAccountSettings.namespace;
        const namespace = typeof namespaceSetting === 'string' && namespaceSetting ? namespaceSetting : 'kamasa/v1';

        if (!restSettings.root || !restSettings.nonce) {
            // Sin credenciales de la API REST no podemos continuar.
            return;
        }

        const apiRoot = String(restSettings.root).replace(/\/+$/, '');
        const normalizedNamespace = namespace.replace(/^\/+|\/+$/g, '');
        const apiBase = `${apiRoot}/${normalizedNamespace}`;
        const siteLocale = document.documentElement.lang || 'es-MX';
        const currencySettings = window.wcSettings && window.wcSettings.currency ? window.wcSettings.currency : {};
        const currencySymbol = currencySettings.symbol || '$';
        const currencyPosition = currencySettings.position || 'left';

        const joinCurrency = (formattedNumber) => {
            switch (currencyPosition) {
                case 'left_space':
                    return `${currencySymbol} ${formattedNumber}`;
                case 'right':
                    return `${formattedNumber}${currencySymbol}`;
                case 'right_space':
                    return `${formattedNumber} ${currencySymbol}`;
                case 'left':
                default:
                    return `${currencySymbol}${formattedNumber}`;
            }
        };

        const formatCurrency = (value) => {
            const numberValue = typeof value === 'number' ? value : parseFloat(value);

            if (Number.isFinite(numberValue)) {
                const formattedNumber = numberValue.toLocaleString(siteLocale, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });

                return joinCurrency(formattedNumber);
            }

            if (value === null || value === undefined || value === '') {
                return '—';
            }

            return String(value);
        };

        const formatNumber = (value) => {
            const numberValue = typeof value === 'number' ? value : parseFloat(value);

            if (Number.isFinite(numberValue)) {
                return numberValue.toLocaleString(siteLocale);
            }

            if (value === null || value === undefined || value === '') {
                return '—';
            }

            return String(value);
        };

        const formatDate = (value) => {
            if (!value) {
                return '—';
            }

            const parsedDate = new Date(value);

            if (Number.isNaN(parsedDate.getTime())) {
                return String(value);
            }

            return parsedDate.toLocaleDateString(siteLocale, {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
            });
        };

        const formatLabel = (text) => {
            if (typeof text !== 'string') {
                return '';
            }

            return text
                .replace(/_/g, ' ')
                .replace(/\b\w/g, (character) => character.toUpperCase());
        };

        const formatValueByKey = (value, key) => {
            if (value === null || value === undefined || value === '') {
                return '—';
            }

            if (typeof value === 'number') {
                if (key && /total|monto|importe|saldo|pago/i.test(key)) {
                    return formatCurrency(value);
                }

                return formatNumber(value);
            }

            if (typeof value === 'string') {
                const trimmed = value.trim();

                if (key && /total|monto|importe|saldo|pago/i.test(key)) {
                    const numericFromString = parseFloat(trimmed.replace(/,/g, ''));

                    if (Number.isFinite(numericFromString)) {
                        return formatCurrency(numericFromString);
                    }
                }

                if (trimmed.match(/\d{4}-\d{2}-\d{2}/)) {
                    return formatDate(trimmed);
                }

                return trimmed;
            }

            return String(value);
        };

        const buildApiUrl = (endpoint) => {
            const sanitizedEndpoint = typeof endpoint === 'string' ? endpoint.replace(/^\/+/, '') : '';

            return `${apiBase}/${sanitizedEndpoint}`;
        };

        const fetchFromApi = (endpoint) => {
            const url = buildApiUrl(endpoint);

            return fetch(url, {
                credentials: 'same-origin',
                headers: {
                    'X-WP-Nonce': restSettings.nonce,
                    Accept: 'application/json',
                },
            }).then((response) => {
                if (!response.ok) {
                    return response.json().catch(() => ({})).then((errorPayload) => {
                        const message = errorPayload && (errorPayload.message || (errorPayload.data && errorPayload.data.message));
                        throw new Error(message || 'Se produjo un error al consultar la API.');
                    });
                }

                return response.json();
            });
        };

        const showError = (element, message) => {
            if (!element) {
                return;
            }

            element.textContent = message;
            element.style.display = '';
        };

        const hideError = (element) => {
            if (!element) {
                return;
            }

            element.textContent = '';
            element.style.display = 'none';
        };

        const renderFacturas = (facturas, container) => {
            if (!container) {
                return;
            }

            if (!Array.isArray(facturas) || facturas.length === 0) {
                container.textContent = 'No tienes facturas pendientes.';
                return;
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'kamasa-facturas-pendientes';

            facturas.forEach((factura) => {
                const item = document.createElement('article');
                item.className = 'kamasa-factura-item';

                if (factura && typeof factura === 'object') {
                    Object.entries(factura).forEach(([key, value]) => {
                        const row = document.createElement('p');
                        row.className = `kamasa-factura-${key}`;

                        const label = document.createElement('strong');
                        label.textContent = `${formatLabel(key)}: `;

                        const valueNode = document.createElement('span');
                        valueNode.textContent = formatValueByKey(value, key);

                        row.appendChild(label);
                        row.appendChild(valueNode);
                        item.appendChild(row);
                    });
                } else {
                    item.textContent = String(factura);
                }

                wrapper.appendChild(item);
            });

            container.innerHTML = '';
            container.appendChild(wrapper);
        };

        const loadFinancialPanel = () => {
            const creditContainer = document.getElementById('kamasa-credito');
            const saldoContainer = document.getElementById('kamasa-saldo-vencido');
            const facturasContainer = document.getElementById('kamasa-facturas');
            const errorContainer = document.getElementById('kamasa-financiero-error');

            if (!creditContainer && !saldoContainer && !facturasContainer) {
                return;
            }

            hideError(errorContainer);

            fetchFromApi('cliente/financiero')
                .then((data) => {
                    if (creditContainer) {
                        creditContainer.textContent = formatCurrency(data && data.linea_credito_disponible);
                    }

                    if (saldoContainer) {
                        saldoContainer.textContent = formatCurrency(data && data.saldo_vencido);
                    }

                    renderFacturas(data && data.facturas_pendientes, facturasContainer);
                })
                .catch((error) => {
                    if (creditContainer) {
                        creditContainer.textContent = '—';
                    }

                    if (saldoContainer) {
                        saldoContainer.textContent = '—';
                    }

                    if (facturasContainer) {
                        facturasContainer.textContent = '';
                    }

                    showError(errorContainer, error.message || 'No fue posible cargar el resumen financiero.');
                    console.error('Kamasa B2B - financiero', error);
                });
        };

        const loadAdvisorInfo = () => {
            const nameContainer = document.getElementById('kamasa-asesor-nombre');
            const emailLink = document.getElementById('kamasa-asesor-email');
            const phoneContainer = document.getElementById('kamasa-asesor-telefono');
            const mailtoButton = document.getElementById('kamasa-asesor-mailto');
            const whatsappButton = document.getElementById('kamasa-asesor-whatsapp');
            const errorContainer = document.getElementById('kamasa-asesor-error');

            if (!nameContainer && !emailLink && !phoneContainer) {
                return;
            }

            hideError(errorContainer);

            fetchFromApi('cliente/asesor')
                .then((data) => {
                    const nombre = data && (data.nombre || data.nombre_completo);
                    const correo = data && data.email;
                    const telefono = data && data.telefono;

                    if (nameContainer) {
                        nameContainer.textContent = nombre || '—';
                    }

                    if (emailLink) {
                        if (correo) {
                            emailLink.textContent = correo;
                            emailLink.href = `mailto:${correo}`;
                        } else {
                            emailLink.textContent = '—';
                            emailLink.href = '#';
                        }
                    }

                    if (mailtoButton) {
                        if (correo) {
                            mailtoButton.href = `mailto:${correo}`;
                            mailtoButton.style.display = '';
                        } else {
                            mailtoButton.href = '#';
                            mailtoButton.style.display = 'none';
                        }
                    }

                    if (phoneContainer) {
                        phoneContainer.textContent = telefono || '—';
                    }

                    if (whatsappButton) {
                        if (telefono) {
                            const phoneDigits = String(telefono).replace(/\D+/g, '');
                            whatsappButton.href = `https://wa.me/${phoneDigits}`;
                            whatsappButton.style.display = phoneDigits ? '' : 'none';
                        } else {
                            whatsappButton.href = '#';
                            whatsappButton.style.display = 'none';
                        }
                    }
                })
                .catch((error) => {
                    if (nameContainer) {
                        nameContainer.textContent = '—';
                    }

                    if (emailLink) {
                        emailLink.textContent = '—';
                        emailLink.href = '#';
                    }

                    if (mailtoButton) {
                        mailtoButton.href = '#';
                        mailtoButton.style.display = 'none';
                    }

                    if (phoneContainer) {
                        phoneContainer.textContent = '—';
                    }

                    if (whatsappButton) {
                        whatsappButton.href = '#';
                        whatsappButton.style.display = 'none';
                    }

                    showError(errorContainer, error.message || 'No fue posible cargar la información del asesor.');
                    console.error('Kamasa B2B - asesor', error);
                });
        };

        const renderOrdersHistory = (orders, container) => {
            if (!container) {
                return;
            }

            container.innerHTML = '';

            const heading = document.createElement('h3');
            heading.textContent = 'Historial rápido';
            container.appendChild(heading);

            if (!Array.isArray(orders) || orders.length === 0) {
                const emptyMessage = document.createElement('p');
                emptyMessage.textContent = 'No hay pedidos recientes.';
                container.appendChild(emptyMessage);
                return;
            }

            const table = document.createElement('table');
            table.className = 'kamasa-historial-rapido__tabla';

            const thead = document.createElement('thead');
            const headerRow = document.createElement('tr');
            ['Pedido', 'Fecha', 'Total', 'Estado'].forEach((label) => {
                const th = document.createElement('th');
                th.textContent = label;
                headerRow.appendChild(th);
            });
            thead.appendChild(headerRow);
            table.appendChild(thead);

            const tbody = document.createElement('tbody');
            orders.slice(0, 10).forEach((order) => {
                const row = document.createElement('tr');

                const idCell = document.createElement('td');
                idCell.textContent = order && order.id ? `#${order.id}` : '—';
                row.appendChild(idCell);

                const dateCell = document.createElement('td');
                dateCell.textContent = order && order.fecha ? formatDate(order.fecha) : '—';
                row.appendChild(dateCell);

                const totalCell = document.createElement('td');
                totalCell.textContent = order && typeof order.total !== 'undefined' ? formatCurrency(order.total) : '—';
                row.appendChild(totalCell);

                const statusCell = document.createElement('td');
                if (order && order.estado) {
                    statusCell.textContent = formatLabel(String(order.estado));
                } else {
                    statusCell.textContent = '—';
                }
                row.appendChild(statusCell);

                tbody.appendChild(row);
            });
            table.appendChild(tbody);

            container.appendChild(table);
        };

        const loadOrdersHistory = () => {
            const accountContent = document.querySelector('.woocommerce-account .woocommerce-MyAccount-content');

            if (!accountContent) {
                return;
            }

            const ordersNavigation = document.querySelector('.woocommerce-MyAccount-navigation-link--orders.is-active');
            const hasOrdersTable = accountContent.querySelector('.woocommerce-orders-table');
            const inOrdersUrl = window.location.pathname.includes('orders') || window.location.pathname.includes('pedidos');

            if (!ordersNavigation && !hasOrdersTable && !inOrdersUrl) {
                return;
            }

            let quickHistory = document.getElementById('kamasa-historial-rapido');
            if (!quickHistory) {
                quickHistory = document.createElement('section');
                quickHistory.id = 'kamasa-historial-rapido';
                quickHistory.className = 'kamasa-historial-rapido';
                accountContent.insertBefore(quickHistory, accountContent.firstChild);
            }

            quickHistory.innerHTML = '';
            const heading = document.createElement('h3');
            heading.textContent = 'Historial rápido';
            quickHistory.appendChild(heading);

            const loading = document.createElement('p');
            loading.className = 'kamasa-historial-rapido__loading';
            loading.textContent = 'Cargando…';
            quickHistory.appendChild(loading);

            fetchFromApi('cliente/historial-compras')
                .then((orders) => {
                    renderOrdersHistory(orders, quickHistory);
                })
                .catch((error) => {
                    quickHistory.innerHTML = '';
                    const title = document.createElement('h3');
                    title.textContent = 'Historial rápido';
                    quickHistory.appendChild(title);

                    const errorMessage = document.createElement('p');
                    errorMessage.className = 'kamasa-historial-rapido__error';
                    errorMessage.textContent = error.message || 'No fue posible cargar tu historial de compras.';
                    quickHistory.appendChild(errorMessage);

                    console.error('Kamasa B2B - historial de compras', error);
                });
        };

        const creditExists = document.getElementById('kamasa-credito');
        if (creditExists) {
            loadFinancialPanel();
            loadAdvisorInfo();
        }

        loadOrdersHistory();
    });
})();
