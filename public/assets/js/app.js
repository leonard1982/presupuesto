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

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function normalizeCurrencyDigits(rawValue) {
        var text = String(rawValue || '').trim();
        var isNegative = text.indexOf('-') === 0;
        var digitsOnly = text.replace(/\D/g, '');
        if (digitsOnly === '') {
            return '';
        }

        digitsOnly = digitsOnly.replace(/^0+(?=\d)/, '');
        if (digitsOnly === '') {
            digitsOnly = '0';
        }

        return (isNegative ? '-' : '') + digitsOnly;
    }

    function formatCurrencyDisplay(rawValue, allowEmpty) {
        var normalized = normalizeCurrencyDigits(rawValue);
        if (normalized === '') {
            return allowEmpty ? '' : '0';
        }

        var isNegative = normalized.indexOf('-') === 0;
        var digits = isNegative ? normalized.substring(1) : normalized;
        var formatted = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return isNegative ? '-' + formatted : formatted;
    }

    function applyMoneyInputFormatting(fieldElement) {
        if (!fieldElement) {
            return;
        }

        var allowEmpty = fieldElement.getAttribute('data-empty-allowed') === '1';
        fieldElement.value = formatCurrencyDisplay(fieldElement.value, allowEmpty);
    }

    function extensionByMimeType(mimeType) {
        var normalizedMime = String(mimeType || '').toLowerCase();
        if (normalizedMime === 'image/jpeg') {
            return 'jpg';
        }
        if (normalizedMime === 'image/png') {
            return 'png';
        }
        if (normalizedMime === 'image/webp') {
            return 'webp';
        }
        if (normalizedMime === 'image/gif') {
            return 'gif';
        }
        return 'png';
    }

    var body = document.body;
    var baseUrl = body ? (body.getAttribute('data-base-url') || '') : '';
    var authenticatedUser = body ? String(body.getAttribute('data-auth-user') || '').trim() : '';
    var activeMenu = body ? String(body.getAttribute('data-active-menu') || '').trim().toLowerCase() : '';
    var preferenceNamespace = 'presupuesto_prefs_' + (authenticatedUser !== '' ? authenticatedUser.toLowerCase() : 'anonimo');

    function isTypingContext(targetElement) {
        if (!targetElement || !targetElement.tagName) {
            return false;
        }

        var tagName = String(targetElement.tagName).toLowerCase();
        if (tagName === 'input' || tagName === 'textarea' || tagName === 'select') {
            return true;
        }

        if (targetElement.isContentEditable) {
            return true;
        }

        return false;
    }

    function readUserPreferences() {
        try {
            var rawValue = window.localStorage.getItem(preferenceNamespace);
            if (!rawValue) {
                return {};
            }

            var parsed = JSON.parse(rawValue);
            if (!parsed || typeof parsed !== 'object') {
                return {};
            }

            return parsed;
        } catch (error) {
            return {};
        }
    }

    function writeUserPreferences(preferences) {
        if (!preferences || typeof preferences !== 'object') {
            return;
        }

        try {
            window.localStorage.setItem(preferenceNamespace, JSON.stringify(preferences));
        } catch (error) {
            // almacenamiento local opcional.
        }
    }

    function getUserPreference(key, defaultValue) {
        if (!key) {
            return defaultValue;
        }

        var preferences = readUserPreferences();
        if (Object.prototype.hasOwnProperty.call(preferences, key)) {
            return preferences[key];
        }

        return defaultValue;
    }

    function setUserPreference(key, value) {
        if (!key) {
            return;
        }

        var preferences = readUserPreferences();
        preferences[key] = value;
        writeUserPreferences(preferences);
    }

    var themeToggleButton = document.getElementById('theme-toggle');
    function applyTheme(themeName) {
        if (!body) {
            return;
        }

        if (themeName === 'dark') {
            body.classList.add('theme-dark');
        } else {
            body.classList.remove('theme-dark');
        }

        if (themeToggleButton) {
            themeToggleButton.innerHTML = themeName === 'dark'
                ? '<i class="bi bi-sun"></i>'
                : '<i class="bi bi-moon-stars"></i>';
        }
    }

    try {
        var storedTheme = getUserPreference('theme', null);
        if (storedTheme !== 'dark' && storedTheme !== 'light') {
            storedTheme = window.localStorage.getItem('presupuesto_theme');
        }

        if (storedTheme === 'dark' || storedTheme === 'light') {
            applyTheme(storedTheme);
        } else {
            applyTheme('light');
        }
    } catch (themeError) {
        applyTheme('light');
    }

    if (themeToggleButton) {
        themeToggleButton.addEventListener('click', function () {
            var isDarkEnabled = body && body.classList.contains('theme-dark');
            var nextTheme = isDarkEnabled ? 'light' : 'dark';
            applyTheme(nextTheme);

            try {
                window.localStorage.setItem('presupuesto_theme', nextTheme);
                setUserPreference('theme', nextTheme);
            } catch (persistError) {
                // almacenamiento local opcional.
            }
        });
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

            hideInlineError(clientErrorId);
        });
    }

    var movementForm = document.getElementById('movimiento-form');
    if (movementForm) {
        var movementErrorId = 'movimiento-client-error';
        var supportsInput = document.getElementById('soportes');
        var supportsFileCount = document.getElementById('soportes-file-count');
        var supportsFileList = document.getElementById('soportes-file-list');
        var supportsClipboardInput = document.getElementById('soportes_clipboard_json');
        var supportsPasteZone = document.getElementById('soportes-paste-zone');
        var supportsPasteFeedback = document.getElementById('soportes-paste-feedback');
        var clipboardFallbackItems = [];
        var moneyInputs = movementForm.querySelectorAll('.js-money-input');
        var quickCaptureToggleButton = document.getElementById('toggle-quick-capture');
        var quickCaptureModeInput = document.getElementById('modo_captura');
        var quickCaptureOptionalFields = movementForm.querySelectorAll('.js-quick-capture-optional');
        var quickSelectButtons = movementForm.querySelectorAll('.js-field-quick-select');

        function renderSupportsSelection() {
            if (!supportsInput || !supportsFileCount || !supportsFileList) {
                return;
            }

            var regularFilesCount = supportsInput.files ? supportsInput.files.length : 0;
            var fallbackFilesCount = clipboardFallbackItems.length;
            var totalFilesCount = regularFilesCount + fallbackFilesCount;

            if (totalFilesCount === 0) {
                supportsFileCount.textContent = 'Sin archivos seleccionados';
                supportsFileList.innerHTML = '';
                supportsFileList.classList.add('hidden');
                return;
            }

            supportsFileCount.textContent = totalFilesCount === 1
                ? '1 archivo seleccionado'
                : (String(totalFilesCount) + ' archivos seleccionados');

            var listHtml = '';
            for (var supportListIndex = 0; supportListIndex < regularFilesCount; supportListIndex += 1) {
                listHtml += '<li><i class="bi bi-file-earmark"></i> ' + escapeHtml(supportsInput.files[supportListIndex].name || '') + '</li>';
            }
            for (var fallbackIndex = 0; fallbackIndex < fallbackFilesCount; fallbackIndex += 1) {
                listHtml += '<li><i class="bi bi-clipboard-image"></i> ' + escapeHtml(clipboardFallbackItems[fallbackIndex].name || 'Imagen portapapeles') + ' <span class="muted">(portapapeles)</span></li>';
            }

            supportsFileList.innerHTML = listHtml;
            supportsFileList.classList.remove('hidden');
        }

        function syncClipboardPayload() {
            if (!supportsClipboardInput) {
                return;
            }

            if (clipboardFallbackItems.length === 0) {
                supportsClipboardInput.value = '';
                return;
            }

            supportsClipboardInput.value = JSON.stringify(clipboardFallbackItems);
        }

        function updatePasteFeedback(message, isError) {
            if (!supportsPasteFeedback) {
                return;
            }

            supportsPasteFeedback.textContent = message;
            supportsPasteFeedback.classList.remove('hidden');
            supportsPasteFeedback.classList.toggle('text-danger', !!isError);
        }

        function addPastedImagesToSupports(filesToAdd) {
            if (!supportsInput || !filesToAdd || filesToAdd.length === 0) {
                return false;
            }

            if (typeof DataTransfer === 'undefined') {
                updatePasteFeedback('Tu navegador no soporta pegado directo en este formulario.', true);
                return false;
            }

            var existingCount = supportsInput.files ? supportsInput.files.length : 0;
            var dataTransfer = new DataTransfer();
            if (supportsInput.files && supportsInput.files.length > 0) {
                for (var currentIndex = 0; currentIndex < supportsInput.files.length; currentIndex += 1) {
                    dataTransfer.items.add(supportsInput.files[currentIndex]);
                }
            }

            for (var addIndex = 0; addIndex < filesToAdd.length; addIndex += 1) {
                dataTransfer.items.add(filesToAdd[addIndex]);
            }

            try {
                supportsInput.files = dataTransfer.files;
            } catch (assignError) {
                return false;
            }

            var expectedCount = existingCount + filesToAdd.length;
            if (!supportsInput.files || supportsInput.files.length < expectedCount) {
                return false;
            }

            renderSupportsSelection();
            return true;
        }

        function convertFileToPayload(fileObject, callback) {
            if (!fileObject || typeof FileReader === 'undefined') {
                callback(null);
                return;
            }

            var reader = new FileReader();
            reader.onload = function () {
                var result = String(reader.result || '');
                var base64Marker = 'base64,';
                var markerPosition = result.indexOf(base64Marker);
                if (markerPosition === -1) {
                    callback(null);
                    return;
                }

                var encoded = result.substring(markerPosition + base64Marker.length);
                if (encoded === '') {
                    callback(null);
                    return;
                }

                callback({
                    name: String(fileObject.name || ('portapapeles_' + String(Date.now()) + '.png')),
                    mime: String(fileObject.type || 'image/png'),
                    data_base64: encoded
                });
            };
            reader.onerror = function () {
                callback(null);
            };
            reader.readAsDataURL(fileObject);
        }

        function addPastedImagesAsFallback(filesToAdd) {
            if (!filesToAdd || filesToAdd.length === 0) {
                return;
            }

            var processedCount = 0;
            var insertedCount = 0;

            function finish() {
                processedCount += 1;
                if (processedCount < filesToAdd.length) {
                    return;
                }

                syncClipboardPayload();
                renderSupportsSelection();

                if (insertedCount > 0) {
                    updatePasteFeedback(insertedCount === 1
                        ? 'Se agrego 1 imagen desde el portapapeles.'
                        : ('Se agregaron ' + String(insertedCount) + ' imagenes desde el portapapeles.'), false);
                } else {
                    updatePasteFeedback('No fue posible procesar las imagenes del portapapeles.', true);
                }
            }

            for (var fileIndex = 0; fileIndex < filesToAdd.length; fileIndex += 1) {
                convertFileToPayload(filesToAdd[fileIndex], function (payload) {
                    if (payload) {
                        clipboardFallbackItems.push(payload);
                        insertedCount += 1;
                    }
                    finish();
                });
            }
        }

        function handleClipboardPaste(event) {
            if (!event || !event.clipboardData || !event.clipboardData.items) {
                return;
            }

            var addedImages = [];
            var clipboardItems = event.clipboardData.items;
            for (var itemIndex = 0; itemIndex < clipboardItems.length; itemIndex += 1) {
                var clipboardItem = clipboardItems[itemIndex];
                if (!clipboardItem || clipboardItem.kind !== 'file') {
                    continue;
                }

                var mimeType = String(clipboardItem.type || '').toLowerCase();
                if (mimeType.indexOf('image/') !== 0) {
                    continue;
                }

                var clipboardFile = clipboardItem.getAsFile();
                if (!clipboardFile) {
                    continue;
                }

                var extension = extensionByMimeType(mimeType);
                var generatedName = 'portapapeles_' + String(Date.now()) + '_' + String(itemIndex + 1) + '.' + extension;

                var renamedFile = clipboardFile;
                try {
                    renamedFile = new File([clipboardFile], generatedName, {
                        type: clipboardFile.type || 'image/png',
                        lastModified: Date.now()
                    });
                } catch (fileError) {
                    renamedFile = clipboardFile;
                }

                addedImages.push(renamedFile);
            }

            if (addedImages.length === 0) {
                return;
            }

            event.preventDefault();
            var ok = addPastedImagesToSupports(addedImages);
            if (ok) {
                if (supportsClipboardInput) {
                    clipboardFallbackItems = [];
                    syncClipboardPayload();
                }
                updatePasteFeedback(addedImages.length === 1
                    ? 'Se agrego 1 imagen desde el portapapeles.'
                    : ('Se agregaron ' + String(addedImages.length) + ' imagenes desde el portapapeles.'), false);
                return;
            }

            addPastedImagesAsFallback(addedImages);
        }

        if (supportsInput) {
            supportsInput.addEventListener('change', renderSupportsSelection);
            renderSupportsSelection();
        }

        if (supportsPasteZone) {
            supportsPasteZone.addEventListener('click', function () {
                supportsPasteZone.focus();
            });

            supportsPasteZone.addEventListener('paste', handleClipboardPaste);
        }

        movementForm.addEventListener('paste', function (event) {
            handleClipboardPaste(event);
        });

        function updateQuickCaptureMode(isEnabled, persistMode) {
            movementForm.classList.toggle('quick-capture-active', !!isEnabled);

            if (quickCaptureToggleButton) {
                quickCaptureToggleButton.setAttribute('aria-pressed', isEnabled ? 'true' : 'false');
                quickCaptureToggleButton.innerHTML = isEnabled
                    ? '<i class="bi bi-lightning-charge-fill"></i> Modo rapido activo'
                    : '<i class="bi bi-lightning-charge"></i> Modo rapido';
            }

            if (quickCaptureModeInput) {
                quickCaptureModeInput.value = isEnabled ? 'rapido' : 'completo';
            }

            for (var optionalIndex = 0; optionalIndex < quickCaptureOptionalFields.length; optionalIndex += 1) {
                quickCaptureOptionalFields[optionalIndex].classList.toggle('hidden', !!isEnabled);
            }

            if (persistMode) {
                setUserPreference('movimientos_quick_mode', isEnabled ? 1 : 0);
            }
        }

        if (quickCaptureToggleButton) {
            quickCaptureToggleButton.addEventListener('click', function () {
                var enabled = !movementForm.classList.contains('quick-capture-active');
                updateQuickCaptureMode(enabled, true);
            });
        }

        if (quickSelectButtons && quickSelectButtons.length > 0) {
            for (var quickButtonIndex = 0; quickButtonIndex < quickSelectButtons.length; quickButtonIndex += 1) {
                quickSelectButtons[quickButtonIndex].addEventListener('click', function () {
                    var targetFieldId = String(this.getAttribute('data-target') || '');
                    var targetValue = String(this.getAttribute('data-value') || '');
                    if (targetFieldId === '') {
                        return;
                    }

                    var targetField = document.getElementById(targetFieldId);
                    if (!targetField) {
                        return;
                    }

                    targetField.value = targetValue;
                    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2 && window.jQuery(targetField).hasClass('select2-hidden-accessible')) {
                        window.jQuery(targetField).trigger('change');
                    }
                });
            }
        }

        var quickModeDefault = movementForm.getAttribute('data-quick-mode-default') === '1';
        var storedQuickMode = parseInt(getUserPreference('movimientos_quick_mode', quickModeDefault ? 1 : 0), 10);
        updateQuickCaptureMode(storedQuickMode === 1 || quickModeDefault, false);

        if (moneyInputs && moneyInputs.length > 0) {
            for (var moneyIndex = 0; moneyIndex < moneyInputs.length; moneyIndex += 1) {
                applyMoneyInputFormatting(moneyInputs[moneyIndex]);

                moneyInputs[moneyIndex].addEventListener('input', function () {
                    applyMoneyInputFormatting(this);
                });

                moneyInputs[moneyIndex].addEventListener('blur', function () {
                    applyMoneyInputFormatting(this);
                });
            }
        }

        movementForm.addEventListener('submit', function (event) {
            syncClipboardPayload();

            if (moneyInputs && moneyInputs.length > 0) {
                for (var moneySubmitIndex = 0; moneySubmitIndex < moneyInputs.length; moneySubmitIndex += 1) {
                    applyMoneyInputFormatting(moneyInputs[moneySubmitIndex]);
                }
            }

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

            if (supportsInput && supportsInput.files && supportsInput.files.length > 0) {
                var maxMb = parseInt(supportsInput.getAttribute('data-max-mb') || '10', 10);
                var maxBytes = maxMb * 1024 * 1024;
                var allowedExtensions = String(supportsInput.getAttribute('data-allowed-extensions') || '').toLowerCase().split(',');

                for (var fileIndex = 0; fileIndex < supportsInput.files.length; fileIndex += 1) {
                    var supportFile = supportsInput.files[fileIndex];
                    var fileName = String(supportFile.name || '').toLowerCase();
                    var extension = '';
                    var dotIndex = fileName.lastIndexOf('.');
                    if (dotIndex !== -1) {
                        extension = fileName.substring(dotIndex + 1);
                    }

                    if (allowedExtensions.indexOf(extension) === -1) {
                        event.preventDefault();
                        showInlineError(movementErrorId, 'Hay archivos con extension no permitida.');
                        return;
                    }

                    if (supportFile.size > maxBytes) {
                        event.preventDefault();
                        showInlineError(movementErrorId, 'Hay archivos que superan el tamano maximo permitido.');
                        return;
                    }
                }
            }

            hideInlineError(movementErrorId);
        });
    }

    var dashboardReportForm = document.getElementById('dashboard-report-form');
    if (dashboardReportForm) {
        dashboardReportForm.addEventListener('submit', function (event) {
            var emailField = document.getElementById('correo_destino');
            var submitButton = dashboardReportForm.querySelector('button[type="submit"]');
            var emailValue = emailField ? String(emailField.value || '').trim() : '';
            var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (emailValue === '' || !emailPattern.test(emailValue)) {
                event.preventDefault();
                if (emailField) {
                    emailField.focus();
                }
                showInlineError('dashboard-client-error', 'Ingresa un correo destino valido para enviar el informe.');
                return;
            }

            hideInlineError('dashboard-client-error');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Enviando...';
            }
        });
    }

    var dashboardAiForm = document.getElementById('dashboard-ai-form');
    if (dashboardAiForm) {
        dashboardAiForm.addEventListener('submit', function () {
            var submitButton = dashboardAiForm.querySelector('button[type="submit"]');
            hideInlineError('dashboard-client-error');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="bi bi-cpu"></i> Analizando...';
            }
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

    var movementFilterDateInput = document.getElementById('movement-filter-date');
    var movementFilterClasificacionInput = document.getElementById('movement-filter-clasificacion');
    var movementFilterCategoriaInput = document.getElementById('movement-filter-categoria');
    var movementFilterTipoInput = document.getElementById('movement-filter-tipo');
    var movementFilterResetButton = document.getElementById('movement-filter-reset');
    var movementQuickDateButtons = document.querySelectorAll('.js-movement-quick-date');
    var movementFilterResultInfo = document.getElementById('movement-filter-result-info');
    var movementFiltersCard = document.querySelector('.movement-filters-card');
    var movementFiltersBody = document.getElementById('movement-filters-body');
    var movementFiltersToggleButton = document.getElementById('movement-filters-toggle');
    var movementWorkspace = document.getElementById('movement-workspace');
    var movementTableElement = document.querySelector('.js-movimientos-table');
    var movementTableDataTable = null;
    var movementTableFilterAttached = false;
    var movementSummaryCard = document.getElementById('movement-summary-card');
    var movementSummaryToggleButton = document.getElementById('movement-summary-toggle');
    var movementSummaryBody = document.getElementById('movement-summary-body');
    var movementDateFilterMode = 'exact';
    var movementDateRangeStart = '';
    var movementDateRangeEnd = '';
    var movementInitializationDone = false;

    function normalizeFilterText(value) {
        return String(value || '').trim().toLowerCase();
    }

    function extractDatePart(value) {
        return String(value || '').trim().substring(0, 10);
    }

    function getTodayIsoDate() {
        var currentDate = new Date();
        var year = String(currentDate.getFullYear());
        var month = String(currentDate.getMonth() + 1);
        var day = String(currentDate.getDate());

        if (month.length < 2) {
            month = '0' + month;
        }
        if (day.length < 2) {
            day = '0' + day;
        }

        return year + '-' + month + '-' + day;
    }

    function isoDateFromObject(dateObject) {
        var year = String(dateObject.getFullYear());
        var month = String(dateObject.getMonth() + 1);
        var day = String(dateObject.getDate());

        if (month.length < 2) {
            month = '0' + month;
        }
        if (day.length < 2) {
            day = '0' + day;
        }

        return year + '-' + month + '-' + day;
    }

    function getWeekRange(referenceDate) {
        var baseDate = referenceDate ? new Date(referenceDate + 'T00:00:00') : new Date();
        if (isNaN(baseDate.getTime())) {
            baseDate = new Date();
        }

        var dayOfWeek = baseDate.getDay();
        var offsetToMonday = dayOfWeek === 0 ? -6 : (1 - dayOfWeek);
        var rangeStart = new Date(baseDate);
        rangeStart.setDate(baseDate.getDate() + offsetToMonday);

        var rangeEnd = new Date(rangeStart);
        rangeEnd.setDate(rangeStart.getDate() + 6);

        return {
            start: isoDateFromObject(rangeStart),
            end: isoDateFromObject(rangeEnd)
        };
    }

    function getMonthRange(referenceDate) {
        var baseDate = referenceDate ? new Date(referenceDate + 'T00:00:00') : new Date();
        if (isNaN(baseDate.getTime())) {
            baseDate = new Date();
        }

        var startDate = new Date(baseDate.getFullYear(), baseDate.getMonth(), 1);
        var endDate = new Date(baseDate.getFullYear(), baseDate.getMonth() + 1, 0);

        return {
            start: isoDateFromObject(startDate),
            end: isoDateFromObject(endDate)
        };
    }

    function setQuickRangeButtonState(rangeName) {
        if (!movementQuickDateButtons || movementQuickDateButtons.length === 0) {
            return;
        }

        for (var quickRangeIndex = 0; quickRangeIndex < movementQuickDateButtons.length; quickRangeIndex += 1) {
            var button = movementQuickDateButtons[quickRangeIndex];
            var buttonRange = String(button.getAttribute('data-range') || '');
            button.classList.toggle('active', buttonRange === rangeName);
        }
    }

    function setDateFilterRange(rangeName) {
        var todayDate = getTodayIsoDate();

        if (rangeName === 'today') {
            movementDateFilterMode = 'exact';
            movementDateRangeStart = todayDate;
            movementDateRangeEnd = todayDate;
            if (movementFilterDateInput) {
                movementFilterDateInput.value = todayDate;
            }
            setQuickRangeButtonState('today');
            return;
        }

        if (rangeName === 'week') {
            var weekRange = getWeekRange(todayDate);
            movementDateFilterMode = 'range';
            movementDateRangeStart = weekRange.start;
            movementDateRangeEnd = weekRange.end;
            if (movementFilterDateInput) {
                movementFilterDateInput.value = '';
            }
            setQuickRangeButtonState('week');
            return;
        }

        if (rangeName === 'month') {
            var monthRange = getMonthRange(todayDate);
            movementDateFilterMode = 'range';
            movementDateRangeStart = monthRange.start;
            movementDateRangeEnd = monthRange.end;
            if (movementFilterDateInput) {
                movementFilterDateInput.value = '';
            }
            setQuickRangeButtonState('month');
            return;
        }

        movementDateFilterMode = 'all';
        movementDateRangeStart = '';
        movementDateRangeEnd = '';
        if (movementFilterDateInput) {
            movementFilterDateInput.value = '';
        }
        setQuickRangeButtonState('all');
    }

    function setSelectFieldValue(selectElement, value) {
        if (!selectElement) {
            return;
        }

        selectElement.value = value;
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2 && window.jQuery(selectElement).hasClass('select2-hidden-accessible')) {
            window.jQuery(selectElement).val(value).trigger('change.select2');
        }
    }

    function loadMovementFilterPreferences() {
        var raw = getUserPreference('movimientos_filters', null);
        if (!raw || typeof raw !== 'object') {
            return null;
        }

        return {
            date: String(raw.date || ''),
            clasificacion: String(raw.clasificacion || ''),
            categoria: String(raw.categoria || ''),
            tipo: String(raw.tipo || ''),
            mode: String(raw.mode || ''),
            rangeStart: String(raw.rangeStart || ''),
            rangeEnd: String(raw.rangeEnd || ''),
            quickRange: String(raw.quickRange || '')
        };
    }

    function saveMovementFilterPreferences(filterState) {
        if (!movementInitializationDone || !filterState) {
            return;
        }

        setUserPreference('movimientos_filters', {
            date: filterState.date,
            clasificacion: movementFilterClasificacionInput ? String(movementFilterClasificacionInput.value || '') : '',
            categoria: movementFilterCategoriaInput ? String(movementFilterCategoriaInput.value || '') : '',
            tipo: movementFilterTipoInput ? String(movementFilterTipoInput.value || '') : '',
            mode: filterState.dateMode,
            rangeStart: filterState.dateRangeStart,
            rangeEnd: filterState.dateRangeEnd,
            quickRange: filterState.quickRange
        });
    }

    function movementDateMatchesFilter(rowDate, filterState) {
        var rowDateSafe = String(rowDate || '').trim();
        if (rowDateSafe === '') {
            return false;
        }

        if (filterState.dateMode === 'all') {
            return true;
        }

        if (filterState.dateMode === 'range') {
            if (filterState.dateRangeStart === '' || filterState.dateRangeEnd === '') {
                return true;
            }
            return rowDateSafe >= filterState.dateRangeStart && rowDateSafe <= filterState.dateRangeEnd;
        }

        if (filterState.date === '') {
            return true;
        }

        return rowDateSafe === filterState.date;
    }

    function setMovementFiltersCollapsed(collapsed, persistState) {
        if (!movementFiltersCard || !movementFiltersBody) {
            return;
        }

        movementFiltersCard.classList.toggle('filters-collapsed', !!collapsed);
        if (movementFiltersToggleButton) {
            movementFiltersToggleButton.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            movementFiltersToggleButton.innerHTML = collapsed
                ? '<i class="bi bi-funnel"></i> Mostrar filtros'
                : '<i class="bi bi-funnel"></i> Ocultar filtros';
        }

        if (persistState) {
            setUserPreference('movimientos_filters_collapsed', collapsed ? 1 : 0);
        }
    }

    function setMovementSummaryCollapsed(collapsed, persistState) {
        if (!movementWorkspace || !movementSummaryCard || !movementSummaryBody) {
            return;
        }

        movementWorkspace.classList.toggle('summary-hidden', !!collapsed);
        if (movementSummaryToggleButton) {
            movementSummaryToggleButton.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            movementSummaryToggleButton.innerHTML = collapsed
                ? '<i class="bi bi-layout-sidebar"></i> Mostrar panel'
                : '<i class="bi bi-layout-sidebar-inset"></i> Ocultar panel';
        }

        if (persistState) {
            setUserPreference('movimientos_summary_collapsed', collapsed ? 1 : 0);
        }
    }

    function getMovementFilterState() {
        var quickRange = '';
        if (movementDateFilterMode === 'all') {
            quickRange = 'all';
        } else if (movementDateFilterMode === 'range' && movementDateRangeStart !== '') {
            var todayDate = getTodayIsoDate();
            var currentWeekRange = getWeekRange(todayDate);
            var currentMonthRange = getMonthRange(todayDate);
            if (movementDateRangeStart === currentWeekRange.start && movementDateRangeEnd === currentWeekRange.end) {
                quickRange = 'week';
            } else if (movementDateRangeStart === currentMonthRange.start && movementDateRangeEnd === currentMonthRange.end) {
                quickRange = 'month';
            }
        } else if (movementDateFilterMode === 'exact' && movementFilterDateInput && movementFilterDateInput.value === getTodayIsoDate()) {
            quickRange = 'today';
        }

        return {
            date: movementFilterDateInput ? String(movementFilterDateInput.value || '').trim() : '',
            clasificacion: normalizeFilterText(movementFilterClasificacionInput ? movementFilterClasificacionInput.value : ''),
            categoria: normalizeFilterText(movementFilterCategoriaInput ? movementFilterCategoriaInput.value : ''),
            tipo: normalizeFilterText(movementFilterTipoInput ? movementFilterTipoInput.value : ''),
            dateMode: movementDateFilterMode,
            dateRangeStart: movementDateRangeStart,
            dateRangeEnd: movementDateRangeEnd,
            quickRange: quickRange
        };
    }

    function updateMovementFilterResultInfo(visibleCount) {
        if (!movementFilterResultInfo) {
            return;
        }

        if (typeof visibleCount !== 'number' || visibleCount < 0) {
            movementFilterResultInfo.textContent = '';
            return;
        }

        movementFilterResultInfo.textContent = visibleCount === 1
            ? '1 movimiento encontrado.'
            : (String(visibleCount) + ' movimientos encontrados.');
    }

    function applyMovementMobileFilters(filterState) {
        var mobileCards = document.querySelectorAll('.mobile-movement-card');
        if (!mobileCards || mobileCards.length === 0) {
            updateMovementFilterResultInfo(-1);
            return;
        }

        var visibleCount = 0;
        for (var cardIndex = 0; cardIndex < mobileCards.length; cardIndex += 1) {
            var card = mobileCards[cardIndex];
            var matchDate = movementDateMatchesFilter(extractDatePart(card.getAttribute('data-filter-fecha')), filterState);
            var matchClasificacion = filterState.clasificacion === '' || normalizeFilterText(card.getAttribute('data-filter-clasificacion')) === filterState.clasificacion;
            var matchCategoria = filterState.categoria === '' || normalizeFilterText(card.getAttribute('data-filter-categoria')) === filterState.categoria;
            var matchTipo = filterState.tipo === '' || normalizeFilterText(card.getAttribute('data-filter-tipo')) === filterState.tipo;
            var isVisible = matchDate && matchClasificacion && matchCategoria && matchTipo;

            card.classList.toggle('hidden', !isVisible);
            if (isVisible) {
                visibleCount += 1;
            }
        }

        var mobileEmptyNode = document.getElementById('movement-mobile-empty-filter');
        if (mobileEmptyNode) {
            mobileEmptyNode.classList.toggle('hidden', visibleCount > 0);
        }

        updateMovementFilterResultInfo(visibleCount);
    }

    function ensureMovementDataTableFilter() {
        if (movementTableFilterAttached || !movementTableElement || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.dataTable || !window.jQuery.fn.dataTable.ext) {
            return;
        }

        window.jQuery.fn.dataTable.ext.search.push(function (settings, data) {
            if (!settings || settings.nTable !== movementTableElement) {
                return true;
            }

            var filters = getMovementFilterState();
            var rowDate = extractDatePart(data[1]);
            var rowClasificacion = normalizeFilterText(data[2]);
            var rowCategoria = normalizeFilterText(data[4]);
            var rowTipo = normalizeFilterText(data[5]);

            if (!movementDateMatchesFilter(rowDate, filters)) {
                return false;
            }
            if (filters.clasificacion !== '' && rowClasificacion !== filters.clasificacion) {
                return false;
            }
            if (filters.categoria !== '' && rowCategoria !== filters.categoria) {
                return false;
            }
            if (filters.tipo !== '' && rowTipo !== filters.tipo) {
                return false;
            }

            return true;
        });

        movementTableFilterAttached = true;
    }

    function renderMovementSummary(movementData) {
        if (!movementSummaryBody || !movementData || typeof movementData !== 'object') {
            return;
        }

        var supportItems = movementData.support_items && movementData.support_items.length ? movementData.support_items : [];
        var supportsHtml = '';

        if (supportItems.length > 0) {
            supportsHtml += '<ul class="movement-summary-support-list">';
            for (var supportIndex = 0; supportIndex < supportItems.length; supportIndex += 1) {
                var support = supportItems[supportIndex] || {};
                var supportName = escapeHtml(support.name || ('Soporte ' + String(supportIndex + 1)));
                var supportUrl = escapeHtml(support.url || '#');
                supportsHtml += '<li>';
                supportsHtml += '<span><i class="bi bi-paperclip"></i> ' + supportName + '</span>';
                supportsHtml += '<span class="movement-summary-support-actions">';
                supportsHtml += '<a href="' + supportUrl + '" target="_blank" class="btn btn-ghost btn-inline btn-mini btn-icon-only" aria-label="Ver soporte"><i class="bi bi-eye"></i></a>';
                supportsHtml += '<a href="' + supportUrl + '" target="_blank" download class="btn btn-ghost btn-inline btn-mini btn-icon-only" aria-label="Descargar soporte"><i class="bi bi-download"></i></a>';
                supportsHtml += '</span>';
                supportsHtml += '</li>';
            }
            supportsHtml += '</ul>';
        } else {
            supportsHtml = '<p class="muted">Sin soportes.</p>';
        }

        var editUrl = movementData.urls && movementData.urls.editar ? escapeHtml(movementData.urls.editar) : '';
        var ticketUrl = movementData.urls && movementData.urls.ticket ? escapeHtml(movementData.urls.ticket) : '';

        var summaryHtml = '<div class="movement-summary-grid">';
        summaryHtml += '<div><span>Fecha</span><strong>' + escapeHtml(movementData.fecha || '') + '</strong></div>';
        summaryHtml += '<div><span>Clasificacion</span><strong>' + escapeHtml(movementData.clasificacion || '') + '</strong></div>';
        summaryHtml += '<div><span>Categoria</span><strong>' + escapeHtml(movementData.categoria || '') + '</strong></div>';
        summaryHtml += '<div><span>Tipo</span><strong>' + escapeHtml(movementData.tipo || '') + '</strong></div>';
        summaryHtml += '<div><span>Valor</span><strong>' + escapeHtml(movementData.valor || '') + '</strong></div>';
        summaryHtml += '<div><span>Usuario</span><strong>' + escapeHtml(movementData.usuario || '') + '</strong></div>';
        summaryHtml += '<div><span>Soportes</span><strong>' + escapeHtml(movementData.soportes || 0) + '</strong></div>';
        summaryHtml += '</div>';
        summaryHtml += '<div class="movement-summary-detail"><span>Detalle</span><p>' + escapeHtml(movementData.detalle || '') + '</p></div>';
        summaryHtml += '<div class="movement-summary-supports"><h4><i class="bi bi-paperclip"></i> Soportes</h4>' + supportsHtml + '</div>';
        summaryHtml += '<div class="movement-summary-actions">';
        if (editUrl !== '') {
            summaryHtml += '<a class="btn btn-secondary btn-inline btn-mini" href="' + editUrl + '"><i class="bi bi-pencil-square"></i> Editar</a>';
        }
        if (ticketUrl !== '') {
            summaryHtml += '<a class="btn btn-ghost btn-inline btn-mini" href="' + ticketUrl + '" target="_blank"><i class="bi bi-receipt"></i> Ticket</a>';
        }
        summaryHtml += '</div>';

        movementSummaryBody.innerHTML = summaryHtml;
    }

    function setActiveMovementRow(rowElement) {
        if (!movementTableElement || !rowElement) {
            return;
        }

        var allRows = movementTableElement.querySelectorAll('tbody tr.js-movement-row-summary');
        for (var rowIndex = 0; rowIndex < allRows.length; rowIndex += 1) {
            allRows[rowIndex].classList.remove('movement-row-selected');
        }

        rowElement.classList.add('movement-row-selected');
    }

    function trySelectFirstVisibleMovementRow() {
        if (!movementTableElement || !movementSummaryBody) {
            return;
        }

        var firstRow = null;

        if (movementTableDataTable) {
            var nodes = movementTableDataTable.rows({ page: 'current', search: 'applied' }).nodes();
            if (nodes && nodes.length > 0) {
                firstRow = nodes[0];
            }
        } else {
            firstRow = movementTableElement.querySelector('tbody tr.js-movement-row-summary');
        }

        if (!firstRow) {
            movementSummaryBody.innerHTML = '<p class="muted">No hay movimientos para el filtro seleccionado.</p>';
            return;
        }

        var movementJson = firstRow.getAttribute('data-movement-json') || '{}';
        var movementData = readSafeJson(movementJson);
        setActiveMovementRow(firstRow);
        renderMovementSummary(movementData);
    }

    function applyMovementFilters() {
        var filters = getMovementFilterState();
        saveMovementFilterPreferences(filters);
        applyMovementMobileFilters(filters);

        if (movementTableDataTable) {
            movementTableDataTable.draw();
            var tableInfo = movementTableDataTable.page.info();
            if (tableInfo && typeof tableInfo.recordsDisplay === 'number') {
                updateMovementFilterResultInfo(tableInfo.recordsDisplay);
            }
            trySelectFirstVisibleMovementRow();
        }
    }

    function initializeMovementFilters() {
        if (!movementFilterDateInput && !movementFilterClasificacionInput && !movementFilterCategoriaInput && !movementFilterTipoInput) {
            return;
        }

        if (movementFiltersToggleButton) {
            movementFiltersToggleButton.addEventListener('click', function () {
                var willCollapse = !movementFiltersCard.classList.contains('filters-collapsed');
                setMovementFiltersCollapsed(willCollapse, true);
            });
        }

        var storedFiltersCollapsed = getUserPreference('movimientos_filters_collapsed', null);
        var defaultFiltersCollapsed = window.innerWidth <= 760;
        if (storedFiltersCollapsed === null || typeof storedFiltersCollapsed === 'undefined') {
            setMovementFiltersCollapsed(defaultFiltersCollapsed, false);
        } else {
            setMovementFiltersCollapsed(parseInt(storedFiltersCollapsed, 10) === 1, false);
        }

        var storedFilterState = loadMovementFilterPreferences();
        if (storedFilterState) {
            if (movementFilterDateInput) {
                movementFilterDateInput.value = storedFilterState.date;
            }
            if (movementFilterClasificacionInput) {
                setSelectFieldValue(movementFilterClasificacionInput, storedFilterState.clasificacion);
            }
            if (movementFilterCategoriaInput) {
                setSelectFieldValue(movementFilterCategoriaInput, storedFilterState.categoria);
            }
            if (movementFilterTipoInput) {
                setSelectFieldValue(movementFilterTipoInput, storedFilterState.tipo);
            }

            movementDateFilterMode = storedFilterState.mode || 'exact';
            movementDateRangeStart = storedFilterState.rangeStart || '';
            movementDateRangeEnd = storedFilterState.rangeEnd || '';
            if (storedFilterState.quickRange !== '') {
                setQuickRangeButtonState(storedFilterState.quickRange);
            }
        } else if (movementFilterDateInput && movementFilterDateInput.value === '' && window.innerWidth <= 760) {
            movementDateFilterMode = 'exact';
            movementDateRangeStart = getTodayIsoDate();
            movementDateRangeEnd = movementDateRangeStart;
            movementFilterDateInput.value = movementDateRangeStart;
            setQuickRangeButtonState('today');
        } else {
            movementDateFilterMode = movementFilterDateInput && movementFilterDateInput.value !== '' ? 'exact' : 'all';
            movementDateRangeStart = movementFilterDateInput ? movementFilterDateInput.value : '';
            movementDateRangeEnd = movementFilterDateInput ? movementFilterDateInput.value : '';
            setQuickRangeButtonState('');
        }

        var changeHandlers = [
            movementFilterDateInput,
            movementFilterClasificacionInput,
            movementFilterCategoriaInput,
            movementFilterTipoInput
        ];

        for (var handlerIndex = 0; handlerIndex < changeHandlers.length; handlerIndex += 1) {
            if (changeHandlers[handlerIndex]) {
                changeHandlers[handlerIndex].addEventListener('change', function () {
                    if (this === movementFilterDateInput) {
                        movementDateFilterMode = movementFilterDateInput.value === '' ? 'all' : 'exact';
                        movementDateRangeStart = movementFilterDateInput.value;
                        movementDateRangeEnd = movementFilterDateInput.value;
                        setQuickRangeButtonState('');
                    }
                    applyMovementFilters();
                });
            }
        }

        if (movementQuickDateButtons && movementQuickDateButtons.length > 0) {
            for (var rangeButtonIndex = 0; rangeButtonIndex < movementQuickDateButtons.length; rangeButtonIndex += 1) {
                movementQuickDateButtons[rangeButtonIndex].addEventListener('click', function () {
                    var range = String(this.getAttribute('data-range') || '');
                    if (range === '') {
                        return;
                    }

                    setDateFilterRange(range);
                    applyMovementFilters();
                });
            }
        }

        if (movementFilterResetButton) {
            movementFilterResetButton.addEventListener('click', function () {
                if (movementFilterDateInput) {
                    movementFilterDateInput.value = '';
                }
                if (movementFilterClasificacionInput) {
                    setSelectFieldValue(movementFilterClasificacionInput, '');
                }
                if (movementFilterCategoriaInput) {
                    setSelectFieldValue(movementFilterCategoriaInput, '');
                }
                if (movementFilterTipoInput) {
                    setSelectFieldValue(movementFilterTipoInput, '');
                }

                movementDateFilterMode = window.innerWidth <= 760 ? 'exact' : 'all';
                movementDateRangeStart = movementDateFilterMode === 'exact' ? getTodayIsoDate() : '';
                movementDateRangeEnd = movementDateRangeStart;
                if (movementFilterDateInput && movementDateFilterMode === 'exact') {
                    movementFilterDateInput.value = movementDateRangeStart;
                }
                setQuickRangeButtonState(movementDateFilterMode === 'exact' ? 'today' : 'all');
                applyMovementFilters();
            });
        }

        movementInitializationDone = true;
        applyMovementFilters();
    }

    if (window.jQuery && window.jQuery.fn) {
        if (typeof window.jQuery.fn.select2 === 'function') {
            window.jQuery('.js-searchable-select').each(function () {
                var selectElement = window.jQuery(this);
                if (selectElement.hasClass('select2-hidden-accessible')) {
                    return;
                }

                var placeholder = selectElement.data('placeholder') || 'Buscar...';
                var optionCount = selectElement.find('option').length;
                var showSearch = optionCount >= 8;

                window.jQuery(this).select2({
                    width: '100%',
                    placeholder: placeholder,
                    allowClear: true,
                    minimumResultsForSearch: showSearch ? 0 : Infinity
                });
            });
        }

        if (typeof window.jQuery.fn.DataTable === 'function') {
            window.jQuery('.js-data-table').each(function () {
                var tableElement = this;
                var tableNode = window.jQuery(tableElement);
                var pageLength = parseInt(window.jQuery(this).data('page-length'), 10);
                if (!pageLength || pageLength < 1) {
                    pageLength = 20;
                }

                var tablePreferenceKey = String(tableNode.data('preference-key') || '').trim();
                if (tablePreferenceKey !== '') {
                    var storedPageLength = parseInt(getUserPreference(tablePreferenceKey, pageLength), 10);
                    if (storedPageLength && storedPageLength >= 1) {
                        pageLength = storedPageLength;
                    }
                }

                var isIndexedTable = window.jQuery(this).hasClass('js-indexed-table');
                var exportName = String(window.jQuery(this).data('export-name') || 'reporte').trim();
                if (exportName === '') {
                    exportName = 'reporte';
                }

                var hasButtons = typeof window.jQuery.fn.dataTable !== 'undefined'
                    && typeof window.jQuery.fn.dataTable.Buttons === 'function'
                    && typeof window.JSZip !== 'undefined'
                    && typeof window.pdfMake !== 'undefined';

                var tableDom = hasButtons
                    ? '<"dt-top"<"dt-tools"B><"dt-controls"lf>>t<"dt-bottom"p>'
                    : '<"dt-top"lf>t<"dt-bottom"p>';

                var dataTableOptions = {
                    responsive: true,
                    autoWidth: false,
                    pageLength: pageLength,
                    lengthMenu: [[10, 20, 30, 50, 100, 200, 300, 500, 1000, -1], [10, 20, 30, 50, 100, 200, 300, 500, 1000, 'Todos']],
                    dom: tableDom,
                    info: false,
                    columnDefs: isIndexedTable ? [{ targets: 0, orderable: false, searchable: false }] : [],
                    language: {
                        search: '',
                        searchPlaceholder: 'Buscar...',
                        lengthMenu: '_MENU_',
                        emptyTable: 'Sin registros',
                        zeroRecords: 'Sin coincidencias',
                        paginate: {
                            previous: '<i class="bi bi-chevron-left"></i>',
                            next: '<i class="bi bi-chevron-right"></i>'
                        }
                    }
                };

                if (hasButtons) {
                    dataTableOptions.buttons = [
                        {
                            extend: 'excelHtml5',
                            text: '<i class="bi bi-file-earmark-excel"></i>',
                            titleAttr: 'Exportar a Excel',
                            className: 'btn dt-export-button',
                            filename: exportName,
                            title: null,
                            exportOptions: {
                                columns: ':visible:not(.no-export)'
                            }
                        },
                        {
                            extend: 'pdfHtml5',
                            text: '<i class="bi bi-file-earmark-pdf"></i>',
                            titleAttr: 'Exportar a PDF',
                            className: 'btn dt-export-button',
                            filename: exportName,
                            title: null,
                            orientation: 'landscape',
                            pageSize: 'A4',
                            exportOptions: {
                                columns: ':visible:not(.no-export)'
                            },
                            customize: function (doc) {
                                var printedAt = new Date();
                                var printedAtText = printedAt.toLocaleDateString('es-CO') + ' ' + printedAt.toLocaleTimeString('es-CO');
                                var reportTitle = 'Reporte de movimientos';
                                if (exportName !== '') {
                                    reportTitle = String(exportName).replace(/_/g, ' ').toUpperCase();
                                }

                                doc.pageMargins = [18, 52, 18, 30];
                                doc.defaultStyle.fontSize = 7;
                                doc.styles.tableHeader.fontSize = 8;
                                doc.styles.tableHeader.bold = true;

                                doc.header = function () {
                                    return {
                                        margin: [18, 12, 18, 0],
                                        columns: [
                                            { text: reportTitle, bold: true, fontSize: 10, color: '#0f4c81' },
                                            { text: 'Impresion: ' + printedAtText, alignment: 'right', fontSize: 8, color: '#475569' }
                                        ]
                                    };
                                };

                                doc.footer = function (currentPage, pageCount) {
                                    return {
                                        margin: [18, 0, 18, 8],
                                        columns: [
                                            { text: 'Sistema Presupuesto', fontSize: 7, color: '#64748b' },
                                            { text: 'Pagina ' + currentPage + ' de ' + pageCount, alignment: 'right', fontSize: 8, color: '#334155' }
                                        ]
                                    };
                                };

                                for (var contentIndex = 0; contentIndex < doc.content.length; contentIndex += 1) {
                                    if (!doc.content[contentIndex] || !doc.content[contentIndex].table) {
                                        continue;
                                    }

                                    var tableNode = doc.content[contentIndex];
                                    if (tableNode.table && tableNode.table.body && tableNode.table.body.length > 0) {
                                        var columnCount = tableNode.table.body[0].length;
                                        var tableWidths = [];
                                        for (var columnIndex = 0; columnIndex < columnCount; columnIndex += 1) {
                                            tableWidths.push('*');
                                        }

                                        tableNode.table.widths = tableWidths;
                                        tableNode.layout = {
                                            hLineWidth: function () { return 0.5; },
                                            vLineWidth: function () { return 0.5; },
                                            hLineColor: function () { return '#d1d5db'; },
                                            vLineColor: function () { return '#d1d5db'; },
                                            paddingLeft: function () { return 3; },
                                            paddingRight: function () { return 3; },
                                            paddingTop: function () { return 2; },
                                            paddingBottom: function () { return 2; }
                                        };

                                        for (var rowIndex = 0; rowIndex < tableNode.table.body.length; rowIndex += 1) {
                                            for (var cellIndex = 0; cellIndex < tableNode.table.body[rowIndex].length; cellIndex += 1) {
                                                var cell = tableNode.table.body[rowIndex][cellIndex];
                                                if (typeof cell === 'string') {
                                                    tableNode.table.body[rowIndex][cellIndex] = {
                                                        text: cell,
                                                        noWrap: false
                                                    };
                                                } else if (cell && typeof cell === 'object') {
                                                    cell.noWrap = false;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    ];
                }

                var dataTable = tableNode.DataTable(dataTableOptions);

                if (tablePreferenceKey !== '') {
                    dataTable.on('length.dt', function (event, settings, lengthValue) {
                        setUserPreference(tablePreferenceKey, parseInt(lengthValue, 10));
                    });
                }

                if (window.jQuery(this).hasClass('js-movimientos-table')) {
                    movementTableDataTable = dataTable;
                    ensureMovementDataTableFilter();
                    dataTable.on('draw.dt', function () {
                        trySelectFirstVisibleMovementRow();
                    });
                }

                if (isIndexedTable) {
                    dataTable.on('order.dt search.dt draw.dt', function () {
                        var info = dataTable.page.info();
                        dataTable.column(0, { search: 'applied', order: 'applied', page: 'current' })
                            .nodes()
                            .each(function (cell, index) {
                                cell.innerHTML = String(info.start + index + 1);
                            });
                    }).draw();
                }
            });
        }
    }

    initializeMovementFilters();

    if (movementSummaryToggleButton) {
        movementSummaryToggleButton.addEventListener('click', function () {
            var isHidden = movementWorkspace ? movementWorkspace.classList.contains('summary-hidden') : false;
            var willCollapse = !isHidden;
            setMovementSummaryCollapsed(willCollapse, true);
        });
    }

    if (movementSummaryCard) {
        var storedSummaryCollapsed = getUserPreference('movimientos_summary_collapsed', 0);
        setMovementSummaryCollapsed(parseInt(storedSummaryCollapsed, 10) === 1, false);
    }

    if (movementTableElement) {
        movementTableElement.addEventListener('click', function (event) {
            var target = event.target;
            if (!target || typeof target.closest !== 'function') {
                return;
            }

            if (target.closest('a,button,form,input,summary,details,.dt-control')) {
                return;
            }

            var row = target.closest('tr.js-movement-row-summary');
            if (!row) {
                return;
            }

            var movementJson = row.getAttribute('data-movement-json') || '{}';
            var movementData = readSafeJson(movementJson);
            setActiveMovementRow(row);
            renderMovementSummary(movementData);
        });

        trySelectFirstVisibleMovementRow();
    }

    var movementSaveModal = document.getElementById('movement-save-modal');
    var movementMobileModal = document.getElementById('movement-mobile-modal');
    var movementMobileModalTitle = document.getElementById('movement-mobile-modal-title');
    var movementMobileModalBody = document.getElementById('movement-mobile-modal-body');
    var installButtons = document.querySelectorAll('.js-app-install-button');
    var pwaInstallModal = document.getElementById('pwa-install-modal');
    var pwaInstallModalText = document.getElementById('pwa-install-modal-text');
    var pwaInstallModalSteps = document.getElementById('pwa-install-modal-steps');
    var pwaInstallConfirm = document.getElementById('pwa-install-confirm');
    var confirmActionModal = document.getElementById('confirm-action-modal');
    var confirmActionModalTitle = document.getElementById('confirm-action-modal-title');
    var confirmActionModalText = document.getElementById('confirm-action-modal-text');
    var confirmActionModalAccept = document.getElementById('confirm-action-modal-accept');
    var pendingConfirmForm = null;
    var deferredInstallPrompt = null;

    function setInstallButtonsVisibility(isVisible) {
        if (!installButtons || installButtons.length === 0) {
            return;
        }

        for (var buttonIndex = 0; buttonIndex < installButtons.length; buttonIndex += 1) {
            installButtons[buttonIndex].classList.toggle('hidden', !isVisible);
        }
    }

    function closePwaInstallModal() {
        if (!pwaInstallModal) {
            return;
        }

        pwaInstallModal.classList.add('hidden');
        pwaInstallModal.setAttribute('aria-hidden', 'true');
    }

    function openPwaInstallModal(mode) {
        if (!pwaInstallModal || !pwaInstallModalText || !pwaInstallModalSteps || !pwaInstallConfirm) {
            return;
        }

        var steps = arrayFromPwaSteps(mode);
        pwaInstallModalText.textContent = pwaDescriptionByMode(mode);
        pwaInstallModalSteps.innerHTML = '';

        for (var stepIndex = 0; stepIndex < steps.length; stepIndex += 1) {
            var listItem = document.createElement('li');
            listItem.textContent = steps[stepIndex];
            pwaInstallModalSteps.appendChild(listItem);
        }

        pwaInstallConfirm.classList.toggle('hidden', mode !== 'prompt' || !deferredInstallPrompt);
        pwaInstallModal.classList.remove('hidden');
        pwaInstallModal.setAttribute('aria-hidden', 'false');
    }

    function pwaDescriptionByMode(mode) {
        if (mode === 'prompt') {
            return 'Puedes instalar esta aplicacion en tu celular para abrirla rapido desde la pantalla principal.';
        }

        if (mode === 'ios') {
            return 'En iPhone o iPad la instalacion se hace desde el menu de compartir del navegador.';
        }

        return 'Puedes agregar esta aplicacion a inicio desde el menu de tu navegador para usarla como app.';
    }

    function arrayFromPwaSteps(mode) {
        if (mode === 'prompt') {
            return [
                'Pulsa "Instalar ahora".',
                'Confirma la instalacion en el cuadro del navegador.',
                'Abre la app desde tu pantalla principal.'
            ];
        }

        if (mode === 'ios') {
            return [
                'Abre esta pagina en Safari.',
                'Pulsa el boton Compartir del navegador.',
                'Elige "Agregar a pantalla de inicio".'
            ];
        }

        return [
            'Abre el menu del navegador (tres puntos).',
            'Selecciona "Instalar aplicacion" o "Agregar a pantalla de inicio".',
            'Confirma y abre la app desde tu pantalla principal.'
        ];
    }

    function closeMovementSaveModal() {
        if (!movementSaveModal) {
            return;
        }

        movementSaveModal.classList.add('hidden');
        movementSaveModal.setAttribute('aria-hidden', 'true');
    }

    function closeConfirmActionModal() {
        if (!confirmActionModal) {
            return;
        }

        confirmActionModal.classList.add('hidden');
        confirmActionModal.setAttribute('aria-hidden', 'true');
    }

    function openConfirmActionModal(formElement) {
        if (!confirmActionModal || !confirmActionModalText || !formElement) {
            return;
        }

        var titleText = formElement.getAttribute('data-confirm-title') || 'Confirmar accion';
        var messageText = formElement.getAttribute('data-confirm-message') || 'Deseas continuar con esta accion?';
        var acceptText = formElement.getAttribute('data-confirm-accept') || 'Si, continuar';

        if (confirmActionModalTitle) {
            confirmActionModalTitle.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> ' + escapeHtml(titleText);
        }

        confirmActionModalText.textContent = messageText;

        if (confirmActionModalAccept) {
            confirmActionModalAccept.innerHTML = '<i class="bi bi-trash3"></i> ' + escapeHtml(acceptText);
        }

        pendingConfirmForm = formElement;
        confirmActionModal.classList.remove('hidden');
        confirmActionModal.setAttribute('aria-hidden', 'false');
    }

    function closeMovementMobileModal() {
        if (!movementMobileModal) {
            return;
        }

        movementMobileModal.classList.add('hidden');
        movementMobileModal.setAttribute('aria-hidden', 'true');
    }

    function openMovementMobileModal(buttonElement) {
        if (!movementMobileModal || !movementMobileModalBody || !buttonElement) {
            return;
        }

        var movementJson = buttonElement.getAttribute('data-movement-json') || '{}';
        var movementData = readSafeJson(movementJson);
        if (!movementData || typeof movementData !== 'object') {
            return;
        }

        if (movementMobileModalTitle) {
            movementMobileModalTitle.innerHTML = '<i class="bi bi-card-text"></i> Detalle del movimiento';
        }

        var detailRows = [
            { label: 'Fecha', value: movementData.fecha || '' },
            { label: 'Clasificacion', value: movementData.clasificacion || '' },
            { label: 'Detalle', value: movementData.detalle || '' },
            { label: 'Categoria', value: movementData.categoria || '' },
            { label: 'Tipo', value: movementData.tipo || '' },
            { label: 'Valor', value: movementData.valor || '' },
            { label: 'Usuario', value: movementData.usuario || '' },
            { label: 'Soportes', value: String(movementData.soportes || 0) }
        ];

        var html = '<div class="movement-detail-grid">';
        for (var rowIndex = 0; rowIndex < detailRows.length; rowIndex += 1) {
            var row = detailRows[rowIndex];
            html += '<div class="movement-detail-item">';
            html += '<span>' + escapeHtml(row.label) + '</span>';
            html += '<strong>' + escapeHtml(row.value) + '</strong>';
            html += '</div>';
        }
        html += '</div>';

        var supportsItems = movementData.support_items && movementData.support_items.length ? movementData.support_items : [];
        if (supportsItems.length > 0) {
            html += '<div class="movement-mobile-supports-box"><h4><i class="bi bi-paperclip"></i> Soportes</h4><ul class="movement-mobile-supports-list">';
            for (var supportIndex = 0; supportIndex < supportsItems.length; supportIndex += 1) {
                var supportItem = supportsItems[supportIndex] || {};
                var supportName = escapeHtml(supportItem.name || ('Soporte ' + String(supportIndex + 1)));
                var supportUrl = escapeHtml(supportItem.url || '#');
                html += '<li>';
                html += '<span>' + supportName + '</span>';
                html += '<span class="movement-mobile-support-actions">';
                html += '<a href="' + supportUrl + '" target="_blank" class="btn btn-ghost btn-inline btn-mini btn-icon-only" aria-label="Ver soporte"><i class="bi bi-eye"></i></a>';
                html += '<a href="' + supportUrl + '" target="_blank" download class="btn btn-ghost btn-inline btn-mini btn-icon-only" aria-label="Descargar soporte"><i class="bi bi-download"></i></a>';
                html += '</span>';
                html += '</li>';
            }
            html += '</ul></div>';
        }

        var editUrl = movementData.urls && movementData.urls.editar ? escapeHtml(movementData.urls.editar) : '';
        var ticketUrl = movementData.urls && movementData.urls.ticket ? escapeHtml(movementData.urls.ticket) : '';
        if (editUrl !== '' || ticketUrl !== '') {
            html += '<div class="movement-mobile-detail-actions">';
            if (editUrl !== '') {
                html += '<a href="' + editUrl + '" class="btn btn-secondary btn-inline btn-mini"><i class="bi bi-pencil-square"></i> Editar</a>';
            }
            if (ticketUrl !== '') {
                html += '<a href="' + ticketUrl + '" target="_blank" class="btn btn-ghost btn-inline btn-mini"><i class="bi bi-receipt"></i> Ticket</a>';
            }
            html += '</div>';
        }

        movementMobileModalBody.innerHTML = html;
        movementMobileModal.classList.remove('hidden');
        movementMobileModal.setAttribute('aria-hidden', 'false');
    }

    document.addEventListener('click', function (event) {
        if (!event.target || typeof event.target.closest !== 'function') {
            return;
        }

        if (!event.target.closest('.supports-inline-dropdown')) {
            var openSupportDropdowns = document.querySelectorAll('.supports-inline-dropdown[open]');
            for (var dropdownIndex = 0; dropdownIndex < openSupportDropdowns.length; dropdownIndex += 1) {
                openSupportDropdowns[dropdownIndex].removeAttribute('open');
            }
        }

        if (movementSaveModal && event.target === movementSaveModal) {
            closeMovementSaveModal();
            return;
        }

        var closeNoticeButton = event.target.closest('.js-close-notification-modal');
        if (closeNoticeButton) {
            event.preventDefault();
            closeMovementSaveModal();
            return;
        }

        if (confirmActionModalAccept && event.target.closest('#confirm-action-modal-accept')) {
            event.preventDefault();
            if (pendingConfirmForm) {
                var targetForm = pendingConfirmForm;
                pendingConfirmForm = null;
                closeConfirmActionModal();
                targetForm.setAttribute('data-confirm-approved', '1');
                targetForm.submit();
            } else {
                closeConfirmActionModal();
            }
            return;
        }

        var closeConfirmButton = event.target.closest('.js-close-confirm-modal');
        if (closeConfirmButton) {
            event.preventDefault();
            pendingConfirmForm = null;
            closeConfirmActionModal();
            return;
        }

        var openInstallButton = event.target.closest('.js-app-install-button');
        if (openInstallButton) {
            event.preventDefault();
            if (deferredInstallPrompt) {
                openPwaInstallModal('prompt');
            } else {
                var isIosDevice = /iphone|ipad|ipod/i.test(window.navigator.userAgent || '');
                openPwaInstallModal(isIosDevice ? 'ios' : 'general');
            }
            return;
        }

        var closeInstallButton = event.target.closest('.js-close-pwa-modal');
        if (closeInstallButton) {
            event.preventDefault();
            closePwaInstallModal();
            return;
        }

        if (pwaInstallConfirm && event.target.closest('#pwa-install-confirm')) {
            event.preventDefault();
            if (deferredInstallPrompt && typeof deferredInstallPrompt.prompt === 'function') {
                deferredInstallPrompt.prompt();
                deferredInstallPrompt.userChoice.then(function () {
                    deferredInstallPrompt = null;
                    setInstallButtonsVisibility(false);
                    closePwaInstallModal();
                }).catch(function () {
                    closePwaInstallModal();
                });
            } else {
                closePwaInstallModal();
            }
            return;
        }

        var openMovementDetailButton = event.target.closest('.js-open-movement-mobile-modal');
        if (openMovementDetailButton) {
            event.preventDefault();
            openMovementMobileModal(openMovementDetailButton);
            return;
        }

        var closeMovementDetailButton = event.target.closest('.js-close-movement-mobile-modal');
        if (closeMovementDetailButton) {
            event.preventDefault();
            closeMovementMobileModal();
            return;
        }

        if (movementMobileModal && event.target === movementMobileModal) {
            closeMovementMobileModal();
            return;
        }

        if (confirmActionModal && event.target === confirmActionModal) {
            pendingConfirmForm = null;
            closeConfirmActionModal();
            return;
        }

        if (pwaInstallModal && event.target === pwaInstallModal) {
            closePwaInstallModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMovementSaveModal();
            closeMovementMobileModal();
            pendingConfirmForm = null;
            closeConfirmActionModal();
            closePwaInstallModal();
            var openSupportDropdowns = document.querySelectorAll('.supports-inline-dropdown[open]');
            for (var dropdownIndex = 0; dropdownIndex < openSupportDropdowns.length; dropdownIndex += 1) {
                openSupportDropdowns[dropdownIndex].removeAttribute('open');
            }
            return;
        }

        if (event.ctrlKey || event.altKey || event.metaKey) {
            return;
        }

        if (isTypingContext(event.target)) {
            return;
        }

        var pressedKey = String(event.key || '').toLowerCase();
        if (pressedKey === 'n' && authenticatedUser !== '') {
            event.preventDefault();
            window.location.href = baseUrl + '/index.php?route=movimientos/nuevo';
            return;
        }

        if (pressedKey === 'f' && authenticatedUser !== '') {
            event.preventDefault();
            if (activeMenu === 'movimientos' && movementTableElement) {
                var wrapper = movementTableElement.closest('.dataTables_wrapper');
                var tableSearchInput = wrapper ? wrapper.querySelector('.dataTables_filter input') : null;
                if (tableSearchInput) {
                    tableSearchInput.focus();
                    tableSearchInput.select();
                    return;
                }
            }

            if (quickSearchInput) {
                quickSearchInput.focus();
                quickSearchInput.select();
            }
            return;
        }

        if (pressedKey === 'e' && authenticatedUser !== '' && activeMenu === 'movimientos' && movementTableElement) {
            event.preventDefault();
            var movementWrapper = movementTableElement.closest('.dataTables_wrapper');
            if (!movementWrapper) {
                return;
            }

            var excelButton = movementWrapper.querySelector('.buttons-excel');
            if (!excelButton) {
                excelButton = movementWrapper.querySelector('.dt-export-button');
            }
            if (excelButton && typeof excelButton.click === 'function') {
                excelButton.click();
            }
        }
    });

    var confirmDeleteForms = document.querySelectorAll('.js-confirm-delete');
    if (confirmDeleteForms && confirmDeleteForms.length > 0) {
        for (var formIndex = 0; formIndex < confirmDeleteForms.length; formIndex += 1) {
            confirmDeleteForms[formIndex].addEventListener('submit', function (event) {
                if (this.getAttribute('data-confirm-approved') === '1') {
                    this.removeAttribute('data-confirm-approved');
                    return;
                }

                event.preventDefault();
                openConfirmActionModal(this);
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
    var isStandaloneMode = window.matchMedia && window.matchMedia('(display-mode: standalone)').matches;
    var isIosStandalone = typeof window.navigator.standalone !== 'undefined' && window.navigator.standalone === true;
    var isAppAlreadyInstalled = isStandaloneMode || isIosStandalone;
    var isIosDevice = /iphone|ipad|ipod/i.test(window.navigator.userAgent || '');

    if (enablePwa) {
        if (isAppAlreadyInstalled) {
            setInstallButtonsVisibility(false);
        } else {
            setInstallButtonsVisibility(true);
        }

        window.addEventListener('beforeinstallprompt', function (event) {
            event.preventDefault();
            deferredInstallPrompt = event;
            setInstallButtonsVisibility(true);
        });

        window.addEventListener('appinstalled', function () {
            deferredInstallPrompt = null;
            setInstallButtonsVisibility(false);
            closePwaInstallModal();
        });
    } else {
        setInstallButtonsVisibility(false);
    }

    if (enablePwa && 'serviceWorker' in navigator) {
        navigator.serviceWorker.register(baseUrl + '/public/sw.js?v=' + encodeURIComponent(assetVersion)).catch(function () {
            // Registro opcional de service worker.
        });
    }
})();
