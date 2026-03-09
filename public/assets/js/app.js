// Proyecto PRESUPUESTO - Comportamiento base UI y PWA.
(function () {
    var loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function (event) {
            var username = document.getElementById('username');
            var password = document.getElementById('password');

            var usernameValid = username && username.value.trim().length > 0;
            var passwordValid = password && password.value.length >= 4;

            if (!usernameValid || !passwordValid) {
                event.preventDefault();
                alert('Valida usuario y contrasena antes de continuar.');
            }
        });
    }

    var body = document.body;
    var enablePwa = body.getAttribute('data-enable-pwa') === 'true';
    var baseUrl = body.getAttribute('data-base-url') || '';
    var assetVersion = body.getAttribute('data-asset-version') || '0.1.0';

    if (enablePwa && 'serviceWorker' in navigator) {
        navigator.serviceWorker.register(baseUrl + '/sw.js?v=' + encodeURIComponent(assetVersion)).catch(function () {
            // Registro de SW opcional.
        });
    }
})();
