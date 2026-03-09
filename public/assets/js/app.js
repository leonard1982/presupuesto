// Proyecto PRESUPUESTO - Comportamiento base UI, tablas, graficos y PWA.
(function () {
    function showInlineError(elementId, message) {
        var target = document.getElementById(elementId);
        if (!target) {
            return;
        }

        target.textContent = message;
        target.classList.remove('hidden');
    }

    function hideInlineError(elementId) {
        var target = document.getElementById(elementId);
        if (!target) {
            return;
        }

        target.textContent = '';
        target.classList.add('hidden');
    }

    function readSafeJson(jsonText) {
        try {
            return JSON.parse(jsonText);
        } catch (error) {
            return null;
        }
    }

    var body = document.body;
    var baseUrl = body ? (body.getAttribute('data-base-url') || '') : '';

    var loginForm = document.getElementById('login-form');
    if (loginForm) {
        var clientErrorId = 'login-client-error';
        var username = document.getElementById('username');
        var password = document.getElementById('password');
        var togglePassword = document.getElementById('toggle-password');

        if (togglePassword && password) {
            togglePassword.addEventListener('click', function () {
                var showPassword = password.getAttribute('type') === 'password';
                password.setAttribute('type', showPassword ? 'text' : 'password');
                togglePassword.textContent = showPassword ? 'Ocultar' : 'Ver';
            });
        }

        loginForm.addEventListener('submit', function (event) {
            var usernameValid = username && username.value.trim().length > 0;
            var passwordValid = password && password.value.length >= 4;

            if (!usernameValid || !passwordValid) {
                event.preventDefault();
                showInlineError(clientErrorId, 'Valida usuario y contrasena antes de continuar.');
                return;
            }

            hideInlineError(clientErrorId);
        });
    }

    var movementForm = document.getElementById('movimiento-form');
    if (movementForm) {
        var movementErrorId = 'movimiento-client-error';
        movementForm.addEventListener('submit', function (event) {
            var requiredFields = ['fecha', 'id_clasificacion', 'detalle', 'valor', 'gasto_costo', 'tipo'];
            var invalidField = null;

            for (var index = 0; index < requiredFields.length; index += 1) {
                var field = document.getElementById(requiredFields[index]);
                if (!field || String(field.value || '').trim() === '') {
                    invalidField = field;
                    break;
                }
            }

            if (invalidField) {
                event.preventDefault();
                invalidField.focus();
                showInlineError(movementErrorId, 'Completa los campos obligatorios del movimiento.');
                return;
            }

            hideInlineError(movementErrorId);
        });
    }

    var sidebar = document.getElementById('app-sidebar');
    var sidebarToggle = document.getElementById('sidebar-toggle');
    var sidebarMobileToggle = document.getElementById('sidebar-mobile-toggle');
    var sidebarBackdrop = document.getElementById('sidebar-backdrop');

    function setSidebarCollapsed(collapsed) {
        if (!body) {
            return;
        }

        if (collapsed) {
            body.classList.add('sidebar-collapsed');
        } else {
            body.classList.remove('sidebar-collapsed');
        }

        try {
            window.localStorage.setItem('presupuesto_sidebar_collapsed', collapsed ? '1' : '0');
        } catch (error) {
            // almacenamiento local opcional.
        }
    }

    function setSidebarOpen(opened) {
        if (!body) {
            return;
        }

        if (opened) {
            body.classList.add('sidebar-open');
        } else {
            body.classList.remove('sidebar-open');
        }
    }

    if (sidebar) {
        try {
            var persistedCollapsed = window.localStorage.getItem('presupuesto_sidebar_collapsed');
            if (persistedCollapsed === '1') {
                setSidebarCollapsed(true);
            }
        } catch (error) {
            // almacenamiento local opcional.
        }
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            var willCollapse = !(body && body.classList.contains('sidebar-collapsed'));
            setSidebarCollapsed(willCollapse);
        });
    }

    if (sidebarMobileToggle) {
        sidebarMobileToggle.addEventListener('click', function () {
            var willOpen = !(body && body.classList.contains('sidebar-open'));
            setSidebarOpen(willOpen);
        });
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', function () {
            setSidebarOpen(false);
        });
    }

    window.addEventListener('resize', function () {
        if (window.innerWidth > 840) {
            setSidebarOpen(false);
        }
    });

    var quickSearchInput = document.getElementById('global-nav-search');
    if (quickSearchInput) {
        var quickSearchRoutes = {
            'dashboard': '/index.php?route=dashboard',
            'panel': '/index.php?route=dashboard',
            'movimientos': '/index.php?route=movimientos',
            'nuevo movimiento': '/index.php?route=movimientos/nuevo',
            'clasificaciones': '/index.php?route=clasificaciones',
            'medios de pago': '/index.php?route=medios-pago'
        };

        quickSearchInput.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') {
                return;
            }

            event.preventDefault();
            var userQuery = String(quickSearchInput.value || '').trim().toLowerCase();
            if (userQuery === '') {
                return;
            }

            var targetRoute = quickSearchRoutes[userQuery];
            if (!targetRoute) {
                if (userQuery.indexOf('mov') !== -1) {
                    targetRoute = quickSearchRoutes.movimientos;
                } else if (userQuery.indexOf('clas') !== -1) {
                    targetRoute = quickSearchRoutes.clasificaciones;
                } else if (userQuery.indexOf('medio') !== -1 || userQuery.indexOf('pago') !== -1) {
                    targetRoute = quickSearchRoutes['medios de pago'];
                } else {
                    targetRoute = quickSearchRoutes.dashboard;
                }
            }

            window.location.href = baseUrl + targetRoute;
        });
    }

    if (window.jQuery && window.jQuery.fn) {
        if (typeof window.jQuery.fn.select2 === 'function') {
            window.jQuery('.js-searchable-select').each(function () {
                var placeholder = window.jQuery(this).data('placeholder') || 'Buscar...';
                window.jQuery(this).select2({
                    width: '100%',
                    placeholder: placeholder,
                    allowClear: true
                });
            });
        }

        if (typeof window.jQuery.fn.DataTable === 'function') {
            window.jQuery('.js-data-table').each(function () {
                var pageLength = parseInt(window.jQuery(this).data('page-length'), 10);
                if (!pageLength || pageLength < 1) {
                    pageLength = 10;
                }

                window.jQuery(this).DataTable({
                    responsive: true,
                    pageLength: pageLength,
                    lengthMenu: [[10, 25, 50], [10, 25, 50]],
                    language: {
                        search: 'Buscar:',
                        lengthMenu: 'Mostrar _MENU_',
                        info: 'Mostrando _START_ a _END_ de _TOTAL_',
                        infoEmpty: 'Sin datos para mostrar',
                        zeroRecords: 'No se encontraron resultados',
                        paginate: {
                            previous: 'Anterior',
                            next: 'Siguiente'
                        }
                    }
                });
            });
        }
    }

    if (window.Chart) {
        var chartDataNode = document.getElementById('dashboard-chart-data');
        if (chartDataNode) {
            var chartData = readSafeJson(chartDataNode.textContent || '{}');

            if (chartData) {
                var trendCanvas = document.getElementById('chart-trend');
                if (trendCanvas && chartData.trend) {
                    new window.Chart(trendCanvas, {
                        type: 'line',
                        data: {
                            labels: chartData.trend.labels || [],
                            datasets: [
                                {
                                    label: 'Ingresos',
                                    data: chartData.trend.ingresos || [],
                                    borderColor: '#1f77b4',
                                    backgroundColor: 'rgba(31,119,180,0.12)',
                                    fill: true,
                                    tension: 0.32
                                },
                                {
                                    label: 'Gastos',
                                    data: chartData.trend.gastos || [],
                                    borderColor: '#d95f02',
                                    backgroundColor: 'rgba(217,95,2,0.11)',
                                    fill: true,
                                    tension: 0.32
                                },
                                {
                                    label: 'Costos',
                                    data: chartData.trend.costos || [],
                                    borderColor: '#7570b3',
                                    backgroundColor: 'rgba(117,112,179,0.11)',
                                    fill: true,
                                    tension: 0.32
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }

                var topCanvas = document.getElementById('chart-top-clasificaciones');
                if (topCanvas && chartData.topClasificaciones) {
                    new window.Chart(topCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: chartData.topClasificaciones.labels || [],
                            datasets: [
                                {
                                    data: chartData.topClasificaciones.totals || [],
                                    backgroundColor: ['#0f4c81', '#1f78b4', '#33a02c', '#e31a1c', '#ff7f00', '#6a3d9a']
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                }
            }
        }
    }

    var enablePwa = body && body.getAttribute('data-enable-pwa') === 'true';
    var assetVersion = body ? (body.getAttribute('data-asset-version') || '0.1.0') : '0.1.0';

    if (enablePwa && 'serviceWorker' in navigator) {
        navigator.serviceWorker.register(baseUrl + '/public/sw.js?v=' + encodeURIComponent(assetVersion)).catch(function () {
            // Registro opcional de service worker.
        });
    }
})();
