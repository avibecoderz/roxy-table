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

    function enhanceTable(container, table){
        var data = tableToData(table);
        if (!data.headers.length) return;

        // Controls container
        var controls = document.createElement('div');
        controls.className = 'roxytable-controls';
        controls.style.display = 'flex';
        controls.style.gap = '8px';
        controls.style.flexWrap = 'wrap';
        controls.style.alignItems = 'center';
        controls.style.marginBottom = '10px';

        // Search
        var search = createControl('input', { type: 'search', placeholder: 'Search...' });
        search.style.padding = '8px'; search.style.borderRadius = '6px'; search.style.border = '1px solid #ddd';
        controls.appendChild(search);

        // Rows per page
        var rpp = createControl('select', {});
        [10,25,50,100].forEach(function(n){ var o = document.createElement('option'); o.value = n; o.textContent = n + ' rows'; rpp.appendChild(o); });
        rpp.value = '25'; rpp.style.padding='8px'; rpp.style.border='1px solid #ddd'; rpp.style.borderRadius='6px';
        controls.appendChild(rpp);

        // Dynamic filter selects (generated from column values)
        function buildFilterSelects(){
            // identify candidate categorical columns
            var candidates = data.headers.map(function(h, idx){
                var values = new Set();
                allRows.forEach(function(tr){ var td = tr.children[idx]; if (td){ var t = textOf(td); if (t!=='') values.add(t); } });
                return { header: h, index: idx, values: Array.from(values).sort(), size: values.size };
            }).filter(function(col){ return col.size > 1 && col.size <= 200; });

            // create a select for each candidate and insert before export button
            candidates.forEach(function(col){
                var sel = createControl('select', {});
                sel.className = 'rt-filter-select';
                sel.dataset.col = String(col.index);
                sel.style.padding='8px'; sel.style.border='1px solid #ddd'; sel.style.borderRadius='6px';
                var def = document.createElement('option'); def.value=''; def.textContent = col.header + ' — All'; sel.appendChild(def);
                col.values.forEach(function(v){ var o = document.createElement('option'); o.value = v; o.textContent = v; sel.appendChild(o); });
                // on change -> applySearch (which applies filters)
                sel.addEventListener('change', function(){ applySearch(search.value || ''); });
                // insert before exportBtn
                controls.insertBefore(sel, exportBtn);
            });
        }

        // Export
        var exportBtn = createControl('button', { type: 'button' }); exportBtn.textContent = 'Export CSV'; exportBtn.className='roxytable-btn roxytable-btn-secondary';
        controls.appendChild(exportBtn);

        // Now build filter selects (inserted before the Export button)
        buildFilterSelects();

        // Reset filters button
        var resetBtn = createControl('button', { type: 'button' }); resetBtn.textContent = 'Reset'; resetBtn.className = 'roxytable-btn roxytable-btn-secondary';
        resetBtn.addEventListener('click', function(){
            // clear search
            search.value = '';
            // reset selects
            Array.from(controls.querySelectorAll('.rt-filter-select')).forEach(function(s){ s.value = ''; });
            // reset pagination and filtered
            filtered = allRows.slice(); currentPage = 1; renderPage();
        });
        controls.insertBefore(resetBtn, exportBtn.nextSibling);

        // Column visibility
        var colBtn = createControl('button', { type: 'button' }); colBtn.textContent = 'Columns ▾'; colBtn.className='roxytable-btn';
        controls.appendChild(colBtn);
        var colMenu = document.createElement('div'); colMenu.style.position='absolute'; colMenu.style.background='#fff'; colMenu.style.border='1px solid #ddd'; colMenu.style.padding='8px'; colMenu.style.display='none'; colMenu.style.zIndex='9999';
        data.headers.forEach(function(h, idx){
            var label = document.createElement('label'); label.style.display='block'; label.style.fontSize='0.9rem';
            var cb = document.createElement('input'); cb.type='checkbox'; cb.checked = true; cb.dataset.col = idx;
            cb.style.marginRight='8px'; label.appendChild(cb); label.appendChild(document.createTextNode(h)); colMenu.appendChild(label);
        });
        document.body.appendChild(colMenu);

        // Pagination container
        var pager = document.createElement('div'); pager.className='rt-pager'; pager.style.display='flex'; pager.style.gap='6px'; pager.style.alignItems='center';
        pager.style.marginLeft='auto'; controls.appendChild(pager);

        // Insert controls before table
        container.insertBefore(controls, table);

        var tbody = table.querySelector('tbody');
        var allRows = Array.from(tbody.querySelectorAll('tr'));
        // Add data-label attributes for each TD to support mobile stacked layout
        function setDataLabels(){
            try{
                Array.from(tbody.querySelectorAll('tr')).forEach(function(tr){
                    Array.from(tr.children).forEach(function(td, i){
                        var label = data.headers[i] || '';
                        if (label) td.setAttribute('data-label', label);
                    });
                });
            }catch(e){ /* silent */ }
        }
        setDataLabels();
        var filtered = allRows.slice();
        var currentPage = 1;
        var rowsPerPage = parseInt(rpp.value,10) || 25;
        var sortIndex = -1; var sortDir = 1; // 1 asc, -1 desc

        function renderPage(){
            // clear tbody
            tbody.innerHTML = '';
            var start = (currentPage-1)*rowsPerPage; var end = start + rowsPerPage;
            var pageRows = filtered.slice(start,end);
            pageRows.forEach(function(r){ tbody.appendChild(r); });
            renderPager();
        }

        function renderPager(){
            pager.innerHTML = '';
            var matched = filtered.length; var total = allRows.length; var pages = Math.max(1, Math.ceil(matched/rowsPerPage));
            var start = Math.min( (currentPage-1)*rowsPerPage + 1, matched );
            var end = Math.min( currentPage*rowsPerPage, matched );
            var info = document.createElement('div'); info.style.color='#666'; info.style.fontSize='0.9rem';
            info.textContent = matched > 0 ? ('Showing ' + start + '–' + end + ' of ' + matched + ' (' + total + ' total)') : ('Showing 0 of ' + total + ' records');
            pager.appendChild(info);
            var prev = createControl('button', { type:'button' }); prev.textContent = '< Prev'; prev.className='roxytable-btn roxytable-btn-secondary'; prev.disabled = currentPage<=1;
            prev.addEventListener('click', function(){ if (currentPage>1){ currentPage--; renderPage(); } }); pager.appendChild(prev);
            var next = createControl('button', { type:'button' }); next.textContent = 'Next >'; next.className='roxytable-btn roxytable-btn-secondary'; next.disabled = currentPage>=pages;
            next.addEventListener('click', function(){ if (currentPage<pages){ currentPage++; renderPage(); } }); pager.appendChild(next);
        }

        function applySearch(q){ q=q.trim().toLowerCase(); if(!q){ filtered = allRows.slice(); } else {
            filtered = allRows.filter(function(tr){ return Array.from(tr.children).some(function(td){ return textOf(td).toLowerCase().indexOf(q) !== -1; }); });
        }
            // apply sort
            if (sortIndex >=0){ filtered.sort(function(a,b){ var A = textOf(a.children[sortIndex]).toLowerCase(); var B = textOf(b.children[sortIndex]).toLowerCase(); return sortDir * (A>B?1:(A<B?-1:0)); }); }
            // apply selected filters (multiple)
            var selects = Array.from(controls.querySelectorAll('.rt-filter-select'));
            if (selects.length){
                filtered = filtered.filter(function(tr){
                    return selects.every(function(s){ var v = s.value; if (!v) return true; var idx = parseInt(s.dataset.col,10); var td = tr.children[idx]; if (!td) return false; return textOf(td) === v; });
                });
            }

            currentPage = 1; renderPage();
        }

        function applySort(idx){ if (sortIndex === idx) sortDir = -sortDir; else { sortIndex = idx; sortDir = 1; } applySearch(search.value || ''); }

        // Header sort handlers
        Array.from(table.querySelectorAll('thead th')).forEach(function(th, idx){ th.style.cursor='pointer'; th.addEventListener('click', function(){ applySort(idx); }); });

        // Rows per page change
        rpp.addEventListener('change', function(){ rowsPerPage = parseInt(rpp.value,10)||25; currentPage=1; renderPage(); });

        // Search input
        var searchTimeout = null; search.addEventListener('input', function(){ clearTimeout(searchTimeout); searchTimeout = setTimeout(function(){ applySearch(search.value); }, 150); });

        // Export
        exportBtn.addEventListener('click', function(){
            var visibleCols = Array.from(colMenu.querySelectorAll('input[type=checkbox]')).map(function(cb,i){ return cb.checked ? i : -1; }).filter(function(i){ return i>=0; });
            var headers = visibleCols.map(function(i){ return data.headers[i]; });
            var rows = filtered.map(function(tr){ return visibleCols.map(function(i){ return textOf(tr.children[i]); }); });
            downloadCSV((document.title||'table') + '.csv', headers, rows);
        });

        // Column menu toggle
        colBtn.addEventListener('click', function(e){ var rect = colBtn.getBoundingClientRect(); colMenu.style.top = (rect.bottom + window.scrollY + 6) + 'px'; colMenu.style.left = (rect.left + window.scrollX) + 'px'; colMenu.style.display = colMenu.style.display === 'none' ? 'block' : 'none'; });
        document.addEventListener('click', function(e){ if (!colMenu.contains(e.target) && e.target !== colBtn) colMenu.style.display='none'; });

        // Column visibility handler
        colMenu.addEventListener('change', function(e){ if (e.target && e.target.dataset && e.target.dataset.col){ var col = parseInt(e.target.dataset.col,10); var visible = e.target.checked;
            // toggle header
            var th = table.querySelectorAll('thead th')[col]; if (th) th.style.display = visible ? '' : 'none';
            // toggle each cell
            table.querySelectorAll('tbody tr').forEach(function(tr){ var td = tr.children[col]; if (td) td.style.display = visible ? '' : 'none'; });
            // refresh data-labels in case columns shifted/hidden
            setDataLabels();
        }});

        // initial render
        renderPage();
    }

    function init(){
        // Enhance admin preview specific elements if present
        var previewTable = document.getElementById('roxytable-preview-table');
        if (previewTable){ var container = previewTable.parentElement; enhanceTable(container, previewTable);
            // wire existing search and export buttons if present
            var search = document.getElementById('roxytable-preview-search');
            if (search){ search.style.minWidth='220px'; }
        }

        // Enhance shortcode tables on frontend
        var frontTables = document.querySelectorAll('.roxytable-embed .roxytable-table, .roxytable-table');
        frontTables.forEach(function(table){ enhanceTable(table.parentElement, table); });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
