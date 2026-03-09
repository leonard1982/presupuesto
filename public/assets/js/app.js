// Proyecto PRESUPUESTO - Comportamiento base UI y PWA.
(function () {
    function showInlineError(elementId, message) {
        var target = document.getElementById(elementId);
        if (!target) {
            return;
        }

        target.textContent = message;
        target.classList.remove('hidden');
    }

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

            var clientError = document.getElementById(clientErrorId);
            if (clientError) {
                clientError.textContent = '';
                clientError.classList.add('hidden');
            }
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

            var movementErrorBox = document.getElementById(movementErrorId);
            if (movementErrorBox) {
                movementErrorBox.textContent = '';
                movementErrorBox.classList.add('hidden');
            }
        });
    }

    var navToggle = document.getElementById('nav-toggle');
    var mainNav = document.getElementById('main-nav');
    if (navToggle && mainNav) {
        navToggle.addEventListener('click', function () {
            mainNav.classList.toggle('nav-open');
        });
    }

    var baseUrl = '';
    var body = document.body;
    if (body) {
        baseUrl = body.getAttribute('data-base-url') || '';
    }

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
                    targetRoute = quickSearchRoutes['movimientos'];
                } else if (userQuery.indexOf('clas') !== -1) {
                    targetRoute = quickSearchRoutes['clasificaciones'];
                } else if (userQuery.indexOf('medio') !== -1 || userQuery.indexOf('pago') !== -1) {
                    targetRoute = quickSearchRoutes['medios de pago'];
                } else {
                    targetRoute = quickSearchRoutes['dashboard'];
                }
            }

            window.location.href = baseUrl + targetRoute;
        });
    }

    if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.select2 === 'function') {
        window.jQuery('.js-searchable-select').each(function () {
            var placeholder = window.jQuery(this).data('placeholder') || 'Buscar...';
            window.jQuery(this).select2({
                width: '100%',
                placeholder: placeholder,
                allowClear: true
            });
        });
    }

    var enablePwa = body && body.getAttribute('data-enable-pwa') === 'true';
    baseUrl = body ? (body.getAttribute('data-base-url') || '') : '';
    var assetVersion = body ? (body.getAttribute('data-asset-version') || '0.1.0') : '0.1.0';

    if (enablePwa && 'serviceWorker' in navigator) {
        navigator.serviceWorker.register(baseUrl + '/public/sw.js?v=' + encodeURIComponent(assetVersion)).catch(function () {
            // Registro de SW opcional.
        });
    }
})();
