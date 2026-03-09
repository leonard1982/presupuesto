// Proyecto PRESUPUESTO - Comportamiento base UI y PWA.
(function () {
    var loginForm = document.getElementById('login-form');
    if (loginForm) {
        var clientError = document.getElementById('login-client-error');
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
                if (clientError) {
                    clientError.textContent = 'Valida usuario y contrasena antes de continuar.';
                    clientError.classList.remove('hidden');
                }
                return;
            }

            if (clientError) {
                clientError.textContent = '';
                clientError.classList.add('hidden');
            }
        });
    }

    var body = document.body;
    var enablePwa = body.getAttribute('data-enable-pwa') === 'true';
    var baseUrl = body.getAttribute('data-base-url') || '';
    var assetVersion = body.getAttribute('data-asset-version') || '0.1.0';

    if (enablePwa && 'serviceWorker' in navigator) {
        navigator.serviceWorker.register(baseUrl + '/public/sw.js?v=' + encodeURIComponent(assetVersion)).catch(function () {
            // Registro de SW opcional.
        });
    }
})();
