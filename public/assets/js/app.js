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
        var storedTheme = window.localStorage.getItem('presupuesto_theme');
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
                    pageLength = 20;
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
                            orientation: 'portrait',
                            pageSize: 'A4',
                            exportOptions: {
                                columns: ':visible:not(.no-export)'
                            }
                        }
                    ];
                }

                var dataTable = window.jQuery(this).DataTable(dataTableOptions);

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

    var supportsModal = document.getElementById('supports-modal');
    var supportsModalBody = document.getElementById('supports-modal-body');
    var supportsModalTitle = document.getElementById('supports-modal-title');
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

    function closeSupportsModal() {
        if (!supportsModal) {
            return;
        }

        supportsModal.classList.add('hidden');
        supportsModal.setAttribute('aria-hidden', 'true');
        if (supportsModalBody) {
            supportsModalBody.innerHTML = '<p class="muted">No hay soportes para mostrar.</p>';
        }
    }

    function openSupportsModal(buttonElement) {
        if (!supportsModal || !buttonElement) {
            return;
        }

        var titleText = buttonElement.getAttribute('data-supports-title') || 'Soportes';
        var supportsJson = buttonElement.getAttribute('data-supports-json') || '';
        var supportsList = readSafeJson(supportsJson);
        var sourceId = buttonElement.getAttribute('data-supports-target') || '';
        var sourceNode = sourceId !== '' ? document.getElementById(sourceId) : null;

        if (supportsModalTitle) {
            supportsModalTitle.innerHTML = '<i class="bi bi-paperclip"></i> ' + escapeHtml(titleText);
        }

        if (supportsModalBody) {
            if (supportsList && supportsList.length > 0) {
                var modalHtml = '<ul class="supports-modal-list">';
                for (var supportIndex = 0; supportIndex < supportsList.length; supportIndex += 1) {
                    var supportItem = supportsList[supportIndex] || {};
                    var supportName = escapeHtml(supportItem.name || ('Soporte ' + (supportIndex + 1)));
                    var supportUrl = escapeHtml(supportItem.url || '#');

                    modalHtml += '<li class="supports-modal-item">';
                    modalHtml += '<span class="supports-modal-name"><i class="bi bi-file-earmark-text"></i>' + supportName + '</span>';
                    modalHtml += '<span class="supports-modal-actions">';
                    modalHtml += '<a class="btn btn-ghost btn-inline btn-mini btn-icon-only" href="' + supportUrl + '" target="_blank" title="Ver soporte" aria-label="Ver soporte"><i class="bi bi-eye"></i></a>';
                    modalHtml += '<a class="btn btn-ghost btn-inline btn-mini btn-icon-only" href="' + supportUrl + '" target="_blank" download title="Descargar soporte" aria-label="Descargar soporte"><i class="bi bi-download"></i></a>';
                    modalHtml += '</span>';
                    modalHtml += '</li>';
                }
                modalHtml += '</ul>';

                supportsModalBody.innerHTML = modalHtml;
            } else {
                supportsModalBody.innerHTML = sourceNode ? sourceNode.innerHTML : '<p class="muted">No hay soportes para mostrar.</p>';
            }
        }

        supportsModal.classList.remove('hidden');
        supportsModal.setAttribute('aria-hidden', 'false');
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

        var movementId = movementData.id ? String(movementData.id) : '';
        if (movementMobileModalTitle) {
            movementMobileModalTitle.innerHTML = '<i class="bi bi-card-text"></i> Detalle del movimiento' + (movementId !== '' ? ' #' + escapeHtml(movementId) : '');
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

        movementMobileModalBody.innerHTML = html;
        movementMobileModal.classList.remove('hidden');
        movementMobileModal.setAttribute('aria-hidden', 'false');
    }

    document.addEventListener('click', function (event) {
        if (!event.target || typeof event.target.closest !== 'function') {
            return;
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

        var openButton = event.target.closest('.js-open-supports-modal');
        if (openButton) {
            event.preventDefault();
            openSupportsModal(openButton);
            return;
        }

        var closeButton = event.target.closest('.js-close-supports-modal');
        if (closeButton) {
            event.preventDefault();
            closeSupportsModal();
            return;
        }

        if (supportsModal && event.target === supportsModal) {
            closeSupportsModal();
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
            closeSupportsModal();
            closeMovementSaveModal();
            closeMovementMobileModal();
            pendingConfirmForm = null;
            closeConfirmActionModal();
            closePwaInstallModal();
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
