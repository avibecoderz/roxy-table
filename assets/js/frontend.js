(function(){
    'use strict';

    function textOf(node){ return (node && node.textContent || '').trim(); }

    function tableToData(table){
        var headers = Array.from(table.querySelectorAll('thead th')).map(textOf);
        var rows = Array.from(table.querySelectorAll('tbody tr')).map(function(tr){
            return Array.from(tr.children).map(function(td){ return textOf(td); });
        });
        return { headers: headers, rows: rows };
    }

    function createControl(tag, attrs){
        var el = document.createElement(tag);
        for(var k in attrs) if(attrs.hasOwnProperty(k)) el.setAttribute(k, attrs[k]);
        return el;
    }

    function downloadCSV(filename, headers, rows){
        var cols = headers.map(function(h){ return '"' + ((''+h).replace(/"/g,'""')) + '"'; }).join(',');
        var lines = [cols];
        rows.forEach(function(r){
            lines.push(r.map(function(c){ return '"' + ((''+c).replace(/"/g,'""')) + '"'; }).join(','));
        });
        var csv = lines.join('\n');
        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url; a.download = filename || 'export.csv';
        document.body.appendChild(a); a.click(); a.remove();
        URL.revokeObjectURL(url);
    }

    function isSerialCol(name){
        if (!name) return false;
        var clean = String(name).trim().toLowerCase();
        return clean === '#' || clean === 's/n' || clean === 'sn' || clean === 's.n.' || clean === 's.n' || clean === 'serial' || clean === 'serial no' || clean === 'serial number' || clean === 'no.' || clean === 'no' || clean === 'index' || clean === 'sl' || clean === 'sl.no' || clean === 'sl. no.';
    }

    function isWordPressAdmin(){
        if (window.roxyTableData && window.roxyTableData.is_admin) return true;
        if (typeof document !== 'undefined' && document.body && document.body.classList && document.body.classList.contains('wp-admin')) return true;
        if (typeof window !== 'undefined' && window.location && typeof window.location.pathname === 'string' && window.location.pathname.indexOf('/wp-admin/') !== -1) return true;
        return false;
    }

    function createSafeMutationObserverClass(){
        var NativeObserver = typeof window !== 'undefined' ? window.MutationObserver : undefined;
        if (!NativeObserver) return undefined;
        return function SafeMutationObserver(callback){
            var isExecuting = false;
            var targets = [];
            var instance = new NativeObserver(function(mutations, obs){
                if (isExecuting) return;
                isExecuting = true;
                try {
                    obs.disconnect();
                    callback.call(obs, mutations, obs);
                } catch (err) {
                    console.error('[RoxyTable] Custom JS MutationObserver error:', err);
                } finally {
                    for (var i = 0; i < targets.length; i++) {
                        try {
                            origObserve.call(obs, targets[i].target, targets[i].options);
                        } catch (e) {}
                    }
                    Promise.resolve().then(function(){
                        isExecuting = false;
                    });
                }
            });
            var origObserve = instance.observe;
            var origDisconnect = instance.disconnect;
            instance.observe = function(target, options){
                if (target) {
                    var exists = false;
                    for (var i = 0; i < targets.length; i++) {
                        if (targets[i].target === target) {
                            targets[i].options = options;
                            exists = true;
                            break;
                        }
                    }
                    if (!exists) {
                        targets.push({ target: target, options: options });
                    }
                }
                return origObserve.call(instance, target, options);
            };
            instance.disconnect = function(){
                return origDisconnect.call(instance);
            };
            return instance;
        };
    }

    function executeCustomJs(embed, table, tableId, settings){
        // Allow execution on frontend pages and on the dedicated table preview (#roxytable-preview-table).
        if (isWordPressAdmin() && (!table || table.id !== 'roxytable-preview-table')) return;
        if (!settings) return;
        var enabled = !Object.prototype.hasOwnProperty.call(settings, 'custom_js_enabled') ||
            settings.custom_js_enabled === true ||
            settings.custom_js_enabled === 1 ||
            settings.custom_js_enabled === '1';
        var code = typeof settings.custom_js === 'string' ? settings.custom_js.trim() : '';
        if (!enabled || !code) return;
        if (embed.getAttribute('data-rt-custom-js-executed') === '1') return;
        embed.setAttribute('data-rt-custom-js-executed', '1');

        // Strip any accidental <script> or </script> wrapper tags
        code = code.replace(/<\s*\/?\s*script\b[^>]*>/gi, '').trim();
        if (!code) return;

        var SafeMO = createSafeMutationObserverClass();
        var origMO = typeof window !== 'undefined' ? window.MutationObserver : undefined;
        var origDocAdd = document.addEventListener;
        var origWinAdd = window.addEventListener;

        try {
            if (SafeMO) {
                window.MutationObserver = SafeMO;
            }
            if (document.readyState !== 'loading') {
                document.addEventListener = function(type, listener, options){
                    if (type === 'DOMContentLoaded' && typeof listener === 'function') {
                        try { listener.call(document, new Event('DOMContentLoaded')); } catch (e) { console.error(e); }
                        return;
                    }
                    return origDocAdd.call(document, type, listener, options);
                };
            }
            if (document.readyState === 'complete') {
                window.addEventListener = function(type, listener, options){
                    if (type === 'load' && typeof listener === 'function') {
                        try { listener.call(window, new Event('load')); } catch (e) { console.error(e); }
                        return;
                    }
                    return origWinAdd.call(window, type, listener, options);
                };
            }

            var fn = new Function(
                'table',
                'tableElement',
                'container',
                'containerElement',
                'embed',
                'tableId',
                'settings',
                'MutationObserver',
                code
            );
            fn.call(table, table, table, embed, embed, embed, tableId, settings, SafeMO || origMO);
        } catch (err) {
            console.error('[RoxyTable] Custom JavaScript error (Table #' + tableId + '):', err);
        } finally {
            if (SafeMO && origMO) {
                window.MutationObserver = origMO;
            }
            document.addEventListener = origDocAdd;
            window.addEventListener = origWinAdd;
        }
    }

    function enhanceTable(container, table){
        if (table.getAttribute('data-rt-enhanced')) return;
        table.setAttribute('data-rt-enhanced', '1');

        var embed = container.closest('.roxytable-embed') || container;
        var tableId = parseInt(embed.getAttribute('data-table-id') || '0', 10);
        var totalRowsCount = parseInt(embed.getAttribute('data-total-rows') || '0', 10);
        var nonce = embed.getAttribute('data-nonce') || (window.roxyTableData && window.roxyTableData.nonce) || '';
        
        var stats = [];
        try { stats = JSON.parse(embed.getAttribute('data-stats') || '[]'); } catch(e){}
        var sheets = [];
        try { sheets = JSON.parse(embed.getAttribute('data-sheets') || '[]'); } catch(e){}
        var settings = {};
        try { settings = JSON.parse(embed.getAttribute('data-settings') || '{}'); } catch(e){}

        function featureEnabled(key, defaultValue){
            if (!Object.prototype.hasOwnProperty.call(settings, key)) return defaultValue;
            return settings[key] !== false && settings[key] !== 0 && settings[key] !== '0';
        }

        var searchEnabled = featureEnabled('search', true);
        var sheetFilteringEnabled = featureEnabled('sheet_filtering', true);
        var tableFilteringEnabled = featureEnabled('table_filtering', true);
        var sortingEnabled = featureEnabled('sort', true);
        var configuredRowsPerPage = parseInt(settings.rows_per_page, 10);
        if (isNaN(configuredRowsPerPage) || configuredRowsPerPage < 0) configuredRowsPerPage = 25;
        var paginationEnabled = featureEnabled('pagination', true) && configuredRowsPerPage !== 0;
        var visibilityEnabled = featureEnabled('visibility', true);
        var exportEnabled = featureEnabled('export', false);

        // Run user-configured Custom JavaScript before tableToData/pagination so any added columns/cells apply to all rows
        executeCustomJs(embed, table, tableId, settings);

        // Run user-configured Custom JavaScript before tableToData/pagination so any added columns/cells apply to all rows
        executeCustomJs(embed, table, tableId, settings);

        var data = tableToData(table);
        if (!data.headers.length) return;

        // Auto-compute stats if not supplied
        if (!stats || !stats.length) {
            stats = data.headers.map(function(h, idx){
                var colValues = new Set();
                var numValues = [];
                Array.from(table.querySelectorAll('tbody tr')).forEach(function(tr){
                    var td = tr.children[idx];
                    if (td){
                        var txt = textOf(td);
                        if (txt !== ''){
                            colValues.add(txt);
                            var cleanNum = parseFloat(txt.replace(/[^0-9.-]/g, ''));
                            if (!isNaN(cleanNum)) numValues.push(cleanNum);
                        }
                    }
                });
                var cats = Array.from(colValues).sort();
                var isNum = numValues.length > 0 && numValues.length >= cats.length * 0.5;
                return {
                    index: idx,
                    name: h,
                    type: isNum ? 'number' : 'text',
                    is_numeric: isNum,
                    min: isNum ? Math.min.apply(null, numValues) : null,
                    max: isNum ? Math.max.apply(null, numValues) : null,
                    categories: cats
                };
            });
        }

        if (sortingEnabled) {
            Array.from(table.querySelectorAll('thead th')).forEach(function(th){
                if (!th.querySelector('.rt-sort-icon')) {
                    var sortSpan = document.createElement('span');
                    sortSpan.className = 'rt-sort-icon';
                    sortSpan.setAttribute('aria-hidden', 'true');
                    th.appendChild(sortSpan);
                }
            });
        }

        // Toolbar Container (Matching Reference UI Layout)
        var controls = document.createElement('div');
        controls.className = 'rt-filter-toolbar roxytable-controls';

        var toolbarLeft = document.createElement('div');
        toolbarLeft.className = 'rt-toolbar-left';

        var toolbarRight = document.createElement('div');
        toolbarRight.className = 'rt-toolbar-right';

        // Categorical Filter Selects (Left side: e.g. Year ▾, Country ▾)
        var catSelects = [];
        var filterColumnsConfigured = Array.isArray(settings.filter_columns);
        var selectedFilterColumns = filterColumnsConfigured ? settings.filter_columns.map(function(index){ return parseInt(index, 10); }) : [];
        if (tableFilteringEnabled) {
            var filterStats = {};
            stats.forEach(function(st){ filterStats[parseInt(st.index, 10)] = st; });
            var columnsToFilter = filterColumnsConfigured
                ? selectedFilterColumns
                : stats.filter(function(st){
                    return !isSerialCol(st.name) && st.categories && st.categories.length > 1 && st.categories.length <= 300;
                }).map(function(st){ return parseInt(st.index, 10); });

            columnsToFilter.forEach(function(colIndex){
                if (colIndex < 0 || colIndex >= data.headers.length) return;
                var st = filterStats[colIndex] || { index: colIndex, name: data.headers[colIndex] };
                var categories = filterColumnsConfigured
                    ? Array.from(new Set(data.rows.map(function(row){ return row[colIndex] || ''; }).filter(function(value){ return value !== ''; }))).sort(function(a, b){ return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' }); })
                    : (st.categories || []);
                var sel = createControl('select', { class: 'rt-filter-select' });
                sel.dataset.col = String(colIndex);
                var def = document.createElement('option');
                def.value = '';
                def.textContent = st.name || data.headers[colIndex];
                sel.appendChild(def);

                categories.forEach(function(cVal){
                    var opt = document.createElement('option');
                    opt.value = cVal;
                    opt.textContent = cVal;
                    sel.appendChild(opt);
                });

                sel.addEventListener('change', function(){ triggerFilter(); });
                toolbarLeft.appendChild(sel);
                catSelects.push(sel);
            });
        }

        // Search Input (Right side: Search: [ input ])
        var searchContainer = document.createElement('div');
        searchContainer.className = 'rt-search-container';
        var searchLabel = document.createElement('label');
        searchLabel.textContent = 'Search:';
        var search = createControl('input', { type: 'search', placeholder: '' });
        if (searchEnabled) {
            searchContainer.appendChild(searchLabel);
            searchContainer.appendChild(search);
            toolbarRight.appendChild(searchContainer);
        }

        // Action Buttons (Rule Builder, Export, Reset)
        var ruleToggleBtn = createControl('button', { type: 'button' });
        ruleToggleBtn.textContent = 'Rules ▾';
        ruleToggleBtn.className = 'roxytable-btn roxytable-btn-secondary';
        if (sheetFilteringEnabled) toolbarRight.appendChild(ruleToggleBtn);

        var exportBtn = createControl('button', { type: 'button' });
        exportBtn.textContent = (window.roxyTableData && window.roxyTableData.i18n.export) || 'Export CSV';
        exportBtn.className = 'roxytable-btn roxytable-btn-secondary';
        if (exportEnabled) toolbarRight.appendChild(exportBtn);

        var resetBtn = createControl('button', { type: 'button' });
        resetBtn.textContent = (window.roxyTableData && window.roxyTableData.i18n.reset) || 'Reset';
        resetBtn.className = 'roxytable-btn roxytable-btn-secondary';
        resetBtn.addEventListener('click', function(){ resetAllFilters(); });
        if (searchEnabled || tableFilteringEnabled || sheetFilteringEnabled) toolbarRight.appendChild(resetBtn);

        var visibilityPanel = null;
        var visibilityChecks = [];
        if (visibilityEnabled) {
            var visibilityBtn = createControl('button', { type: 'button' });
            visibilityBtn.textContent = 'Columns';
            visibilityBtn.className = 'roxytable-btn roxytable-btn-secondary';
            visibilityBtn.setAttribute('aria-expanded', 'false');
            visibilityPanel = document.createElement('div');
            visibilityPanel.className = 'rt-column-visibility';
            visibilityPanel.style.display = 'none';
            data.headers.forEach(function(header, idx){
                var label = document.createElement('label');
                var checkbox = createControl('input', { type: 'checkbox', checked: 'checked' });
                checkbox.checked = true;
                checkbox.addEventListener('change', function(){ applyColumnVisibility(); });
                label.appendChild(checkbox);
                label.appendChild(document.createTextNode(' ' + header));
                visibilityPanel.appendChild(label);
                visibilityChecks[idx] = checkbox;
            });
            visibilityBtn.addEventListener('click', function(){
                var isOpen = visibilityPanel.style.display !== 'none';
                visibilityPanel.style.display = isOpen ? 'none' : 'flex';
                visibilityBtn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            });
            toolbarRight.appendChild(visibilityBtn);
        }

        controls.appendChild(toolbarLeft);
        controls.appendChild(toolbarRight);
        if (visibilityPanel) container.insertBefore(visibilityPanel, table);

        // Active Filters Bar
        var activeBar = document.createElement('div');
        activeBar.className = 'rt-active-filters-bar';
        activeBar.style.display = 'none';

        // Multi-Condition Rule Builder Card Container
        var ruleCard = document.createElement('div');
        ruleCard.className = 'rt-rule-builder-card';
        ruleCard.style.display = 'none';

        var ruleHeader = document.createElement('div');
        ruleHeader.className = 'rt-rule-builder-header';
        ruleHeader.innerHTML = '<strong style="font-size:0.9rem;">Multi-Condition Sheet Filter Rules</strong>';

        var matchSelect = createControl('select', {});
        matchSelect.innerHTML = '<option value="and">Match ALL conditions (AND)</option><option value="or">Match ANY condition (OR)</option>';
        matchSelect.style.padding = '4px 8px'; matchSelect.style.fontSize = '0.8125rem';
        matchSelect.addEventListener('change', function(){ triggerFilter(); });
        ruleHeader.appendChild(matchSelect);
        ruleCard.appendChild(ruleHeader);

        var ruleRowsContainer = document.createElement('div');
        ruleRowsContainer.className = 'rt-rule-rows-container';
        ruleCard.appendChild(ruleRowsContainer);

        var addRuleBtn = createControl('button', { type: 'button' });
        addRuleBtn.textContent = '+ Add Condition Rule';
        addRuleBtn.className = 'roxytable-btn roxytable-btn-secondary';
        addRuleBtn.style.marginTop = '8px';
        addRuleBtn.addEventListener('click', function(){ addRuleRow(); });
        ruleCard.appendChild(addRuleBtn);

        if (sheetFilteringEnabled) ruleToggleBtn.addEventListener('click', function(){
            ruleCard.style.display = ruleCard.style.display === 'none' ? 'block' : 'none';
        });

        // Insert controls before table
        container.insertBefore(controls, table);
        container.insertBefore(ruleCard, table);
        container.insertBefore(activeBar, table);

        // Pagination Container
        var pager = document.createElement('div');
        pager.className = 'rt-pager';
        pager.style.display = 'flex';
        pager.style.gap = '6px';
        pager.style.alignItems = 'center';
        pager.style.marginTop = '10px';
        container.appendChild(pager);

        // Setup Expandable Row (+) Buttons on left of each row
        var tbody = table.querySelector('tbody');
        var allRows = Array.from(tbody.querySelectorAll('tr'));

        var filtered = allRows.slice();
        var currentPage = 1;
        var rowsPerPage = configuredRowsPerPage;
        var sortIndex = -1; var sortDir = 1;

        function addRuleRow(){
            var rowDiv = document.createElement('div');
            rowDiv.className = 'rt-rule-row';

            var colSel = createControl('select', {});
            data.headers.forEach(function(h, idx){
                if (!isSerialCol(h)) {
                    var opt = document.createElement('option'); opt.value = idx; opt.textContent = h;
                    colSel.appendChild(opt);
                }
            });

            var opSel = createControl('select', {});
            var ops = [
                { v: 'contains', t: 'Contains' },
                { v: 'equals', t: 'Equals' },
                { v: 'not_equals', t: 'Not Equals' },
                { v: 'not_contains', t: 'Does Not Contain' },
                { v: 'starts_with', t: 'Starts With' },
                { v: 'ends_with', t: 'Ends With' },
                { v: 'greater_than', t: 'Greater Than (>)' },
                { v: 'greater_than_equal', t: 'Greater Than or Equal (>=)' },
                { v: 'less_than', t: 'Less Than (<)' },
                { v: 'less_than_equal', t: 'Less Than or Equal (<=)' },
                { v: 'between', t: 'Between Range' },
                { v: 'is_empty', t: 'Is Empty' },
                { v: 'is_not_empty', t: 'Is Not Empty' }
            ];
            ops.forEach(function(op){
                var o = document.createElement('option'); o.value = op.v; o.textContent = op.t;
                opSel.appendChild(o);
            });

            var valInput = createControl('input', { type: 'text', placeholder: 'Value...' });
            var valInput2 = createControl('input', { type: 'text', placeholder: 'Max Value...', style: 'display:none;' });

            opSel.addEventListener('change', function(){
                valInput2.style.display = opSel.value === 'between' ? 'inline-block' : 'none';
                valInput.style.display = (opSel.value === 'is_empty' || opSel.value === 'is_not_empty') ? 'none' : 'inline-block';
                triggerFilter();
            });

            valInput.addEventListener('input', function(){ triggerFilter(); });
            valInput2.addEventListener('input', function(){ triggerFilter(); });
            colSel.addEventListener('change', function(){ triggerFilter(); });

            var removeBtn = createControl('button', { type: 'button' });
            removeBtn.textContent = '×';
            removeBtn.className = 'rt-rule-remove-btn';
            removeBtn.addEventListener('click', function(){
                rowDiv.remove();
                triggerFilter();
            });

            rowDiv.appendChild(colSel);
            rowDiv.appendChild(opSel);
            rowDiv.appendChild(valInput);
            rowDiv.appendChild(valInput2);
            rowDiv.appendChild(removeBtn);
            ruleRowsContainer.appendChild(rowDiv);
            triggerFilter();
        }

        function getActiveSheetRules(){
            var rules = [];
            Array.from(ruleRowsContainer.querySelectorAll('.rt-rule-row')).forEach(function(row){
                var selects = row.querySelectorAll('select');
                var inputs = row.querySelectorAll('input');
                if (selects.length >= 2){
                    rules.push({
                        col: parseInt(selects[0].value, 10),
                        op: selects[1].value,
                        val: inputs[0] ? inputs[0].value : '',
                        val2: inputs[1] ? inputs[1].value : ''
                    });
                }
            });
            return rules;
        }

        var filterTimeout = null;
        function triggerFilter(){
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(function(){
                applyFilters();
            }, 100);
        }

        function updateActiveFilterBadges(){
            activeBar.innerHTML = '';
            var activeCount = 0;

            if (searchEnabled && search.value.trim() !== '') {
                activeCount++;
                var badge = createFilterBadge('Search: ' + search.value.trim(), function(){
                    search.value = '';
                    triggerFilter();
                });
                activeBar.appendChild(badge);
            }

            catSelects.forEach(function(s){
                if (s.value !== '') {
                    activeCount++;
                    var colName = data.headers[parseInt(s.dataset.col, 10)] || 'Col';
                    var badge = createFilterBadge(colName + ': ' + s.value, function(){
                        s.value = '';
                        triggerFilter();
                    });
                    activeBar.appendChild(badge);
                }
            });

            var rules = sheetFilteringEnabled ? getActiveSheetRules() : [];
            if (rules.length > 0) {
                activeCount++;
                var badge = createFilterBadge('Sheet Rules (' + rules.length + ')', function(){
                    ruleRowsContainer.innerHTML = '';
                    triggerFilter();
                });
                activeBar.appendChild(badge);
            }

            activeBar.style.display = activeCount > 0 ? 'flex' : 'none';
        }

        function createFilterBadge(text, onRemove){
            var el = document.createElement('span');
            el.className = 'rt-filter-badge';
            el.textContent = text + ' ';
            var x = document.createElement('span');
            x.className = 'rt-filter-badge-remove';
            x.textContent = '×';
            x.addEventListener('click', onRemove);
            el.appendChild(x);
            return el;
        }

        function resetAllFilters(){
            search.value = '';
            catSelects.forEach(function(s){ s.value = ''; });
            ruleRowsContainer.innerHTML = '';
            currentPage = 1;
            applyFilters();
        }

        var isAjaxMode = (totalRowsCount > 1000) || (settings.server_side === true);

        function applyFilters(){
            updateActiveFilterBadges();

            if (isAjaxMode && window.roxyTableData && window.roxyTableData.ajax_url) {
                fetchAjaxFilters();
            } else {
                applyClientFilters();
            }
        }

        function applyClientFilters(){
            var q = searchEnabled ? search.value.trim().toLowerCase() : '';
            var rules = sheetFilteringEnabled ? getActiveSheetRules() : [];
            var isOr = matchSelect.value === 'or';

            filtered = allRows.filter(function(tr){
                var cells = Array.from(tr.children).map(textOf);

                // 1. Search text
                if (q !== '') {
                    var hasSearch = cells.some(function(c){ return c.toLowerCase().indexOf(q) !== -1; });
                    if (!hasSearch) return false;
                }

                // 2. Categorical dropdowns
                var catPass = !tableFilteringEnabled || catSelects.every(function(s){
                    if (s.value === '') return true;
                    var colI = parseInt(s.dataset.col, 10);
                    return cells[colI] === s.value;
                });
                if (!catPass) return false;

                // 3. Multi-condition sheet rules
                if (rules.length > 0) {
                    var ruleResults = rules.map(function(r){
                        var cellStr = cells[r.col] || '';
                        var cellNum = parseFloat(cellStr.replace(/[^0-9.-]/g, ''));
                        var tVal = (r.val || '').toLowerCase();
                        var tNum = parseFloat(r.val);

                        switch(r.op){
                            case 'equals': return cellStr.toLowerCase() === tVal;
                            case 'not_equals': return cellStr.toLowerCase() !== tVal;
                            case 'contains': return cellStr.toLowerCase().indexOf(tVal) !== -1;
                            case 'not_contains': return cellStr.toLowerCase().indexOf(tVal) === -1;
                            case 'starts_with': return cellStr.toLowerCase().indexOf(tVal) === 0;
                            case 'ends_with': return cellStr.toLowerCase().slice(-tVal.length) === tVal;
                            case 'greater_than': return cellNum > tNum;
                            case 'greater_than_equal': return cellNum >= tNum;
                            case 'less_than': return cellNum < tNum;
                            case 'less_than_equal': return cellNum <= tNum;
                            case 'between': return cellNum >= parseFloat(r.val) && cellNum <= parseFloat(r.val2);
                            case 'is_empty': return cellStr.trim() === '';
                            case 'is_not_empty': return cellStr.trim() !== '';
                            default: return true;
                        }
                    });

                    if (isOr) {
                        if (!ruleResults.some(Boolean)) return false;
                    } else {
                        if (!ruleResults.every(Boolean)) return false;
                    }
                }

                return true;
            });

            // Sorting (handles numbers, currency $, percentages %, time hh:mm, text)
            if (sortIndex >= 0) {
                filtered.sort(function(a,b){
                    var A = textOf(a.children[sortIndex]).toLowerCase();
                    var B = textOf(b.children[sortIndex]).toLowerCase();

                    var numA = parseFloat(A.replace(/[^0-9.-]/g, ''));
                    var numB = parseFloat(B.replace(/[^0-9.-]/g, ''));

                    if (!isNaN(numA) && !isNaN(numB) && A.replace(/[^0-9.-]/g, '').length > 0 && B.replace(/[^0-9.-]/g, '').length > 0) {
                        return sortDir * (numA - numB);
                    }
                    return sortDir * (A > B ? 1 : (A < B ? -1 : 0));
                });
            }

            currentPage = 1;
            renderPage();
        }

        function fetchAjaxFilters(){
            var payload = new FormData();
            payload.append('action', 'roxytable_filter_rows');
            payload.append('table_id', tableId);
            payload.append('nonce', nonce);
            payload.append('limit', -1);
            payload.append('offset', 0);
            payload.append('search', search.value.trim());
            payload.append('sort_col', sortIndex);
            payload.append('sort_dir', sortDir === 1 ? 'asc' : 'desc');
            payload.append('rule_match', matchSelect.value);

            catSelects.forEach(function(s){
                if (s.value !== '') {
                    payload.append('categorical[' + s.dataset.col + '][]', s.value);
                }
            });

            getActiveSheetRules().forEach(function(r, idx){
                payload.append('sheet_rules[' + idx + '][col]', r.col);
                payload.append('sheet_rules[' + idx + '][op]', r.op);
                payload.append('sheet_rules[' + idx + '][val]', r.val);
                payload.append('sheet_rules[' + idx + '][val2]', r.val2);
            });

            fetch(window.roxyTableData.ajax_url, { method: 'POST', body: payload })
                .then(function(res){ return res.json(); })
                .then(function(res){
                    if (res.success && res.data) {
                        renderAjaxData(res.data);
                    }
                })
                .catch(function(err){ console.error('RoxyTable AJAX Error:', err); });
        }

        function renderAjaxData(respData){
            tbody.innerHTML = '';
            allRows = [];
            (respData.rows || []).forEach(function(rObj){
                var tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid #f1f5f9';
                (rObj.cells || []).forEach(function(cellText, colI){
                    var td = document.createElement('td');
                    td.style.padding = '8px';
                    var label = data.headers[colI] || '';
                    if (label) td.setAttribute('data-label', label);
                    var cleanText = (cellText === '#rowspan#' || cellText === '#colspan#') ? '' : cellText;
                    td.innerHTML = cleanText;
                    tr.appendChild(td);
                });
                allRows.push(tr);
            });
            filtered = allRows.slice();
            currentPage = 1;
            renderPage();
            try {
                var ajaxEvt = new CustomEvent('roxytable:ajaxLoaded', {
                    detail: { tableId: tableId, table: table, container: embed, data: respData }
                });
                table.dispatchEvent(ajaxEvt);
                embed.dispatchEvent(ajaxEvt);
            } catch (e) {}
        }

        function renderPage(){
            tbody.innerHTML = '';
            var pageCount = paginationEnabled ? Math.max(1, Math.ceil(filtered.length / rowsPerPage)) : 1;
            currentPage = Math.min(currentPage, pageCount);
            var start = paginationEnabled ? (currentPage - 1) * rowsPerPage : 0;
            var visibleRows = paginationEnabled ? filtered.slice(start, start + rowsPerPage) : filtered;
            visibleRows.forEach(function(r){ tbody.appendChild(r); });
            applyColumnVisibility();
            renderPager();
        }

        function renderPager(){
            pager.innerHTML = '';
            pager.style.display = paginationEnabled ? 'flex' : 'none';
            if (!paginationEnabled) return;
            var matched = filtered.length;
            var info = document.createElement('div');
            info.style.color = '#666';
            info.style.fontSize = '0.9rem';
            var start = matched ? (currentPage - 1) * rowsPerPage + 1 : 0;
            var end = Math.min(currentPage * rowsPerPage, matched);
            info.textContent = matched > 0 ? ('Showing ' + start + '–' + end + ' of ' + matched + ' (' + totalRowsCount + ' total)') : ('Showing 0 of ' + totalRowsCount + ' records');
            pager.appendChild(info);

            var pageCount = Math.max(1, Math.ceil(matched / rowsPerPage));
            if (pageCount > 1) {
                var previous = createControl('button', { type: 'button' });
                previous.textContent = 'Previous';
                previous.disabled = currentPage <= 1;
                previous.addEventListener('click', function(){ currentPage--; renderPage(); });
                pager.appendChild(previous);

                var pageStatus = document.createElement('span');
                pageStatus.textContent = 'Page ' + currentPage + ' of ' + pageCount;
                pager.appendChild(pageStatus);

                var next = createControl('button', { type: 'button' });
                next.textContent = 'Next';
                next.disabled = currentPage >= pageCount;
                next.addEventListener('click', function(){ currentPage++; renderPage(); });
                pager.appendChild(next);
            }
        }

        function applyColumnVisibility(){
            if (!visibilityEnabled) return;
            var headers = Array.from(table.querySelectorAll('thead th'));
            headers.forEach(function(cell, idx){
                cell.style.display = !visibilityChecks[idx] || visibilityChecks[idx].checked ? '' : 'none';
            });
            allRows.forEach(function(row){
                Array.from(row.children).forEach(function(cell, idx){
                    cell.style.display = !visibilityChecks[idx] || visibilityChecks[idx].checked ? '' : 'none';
                });
            });
        }

        function applySort(idx){
            if (sortIndex === idx) sortDir = -sortDir; else { sortIndex = idx; sortDir = 1; }
            applyFilters();
        }

        // Header sort handlers
        if (sortingEnabled) Array.from(table.querySelectorAll('thead th')).forEach(function(th, idx){
            th.style.cursor = 'pointer';
            th.addEventListener('click', function(){ applySort(idx); });
        });

        // Search input
        if (searchEnabled) search.addEventListener('input', function(){ triggerFilter(); });

        // Export CSV
        if (exportEnabled) exportBtn.addEventListener('click', function(){
            var headers = data.headers;
            var rows = filtered.map(function(tr){ return Array.from(tr.children).map(textOf); });
            downloadCSV((document.title || 'table') + '.csv', headers, rows);
        });

        // Initial render
        renderPage();

        // Execute user-configured Custom JavaScript on frontend after table HTML is rendered
        executeCustomJs(embed, table, tableId, settings);
    }

    function scanAndEnhanceTables(root){
        var scope = root || document;
        if (scope === document) {
            var previewTable = document.getElementById('roxytable-preview-table');
            if (previewTable){
                var pEmbed = previewTable.closest('.roxytable-embed') || previewTable.parentElement;
                enhanceTable(pEmbed, previewTable);
            }
        }

        var frontTables = scope.querySelectorAll
            ? scope.querySelectorAll('.roxytable-embed .roxytable-table, .roxytable-table, table.roxytable-preview-table')
            : [];
        frontTables.forEach(function(table){
            var tEmbed = table.closest('.roxytable-embed') || table.parentElement;
            enhanceTable(tEmbed, table);
        });
    }

    function init(){
        scanAndEnhanceTables(document);

        // Observe dynamically injected / AJAX-loaded tables on frontend pages
        if (!isWordPressAdmin() && typeof MutationObserver !== 'undefined' && document.body) {
            var observer = new MutationObserver(function(mutations){
                for (var i = 0; i < mutations.length; i++) {
                    var added = mutations[i].addedNodes;
                    for (var j = 0; j < added.length; j++) {
                        var node = added[j];
                        if (node && node.nodeType === 1) {
                            if (node.matches && (node.matches('.roxytable-embed') || node.matches('.roxytable-table'))) {
                                var tbl = node.matches('.roxytable-table') ? node : node.querySelector('.roxytable-table');
                                if (tbl) {
                                    var emb = tbl.closest('.roxytable-embed') || tbl.parentElement;
                                    enhanceTable(emb, tbl);
                                }
                            } else if (node.querySelectorAll) {
                                scanAndEnhanceTables(node);
                            }
                        }
                    }
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
