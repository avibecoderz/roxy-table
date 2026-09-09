/**
 * RoxyTable Admin — Entry Point
 *
 * Initialises all admin-screen interactive behaviours.
 * Runs as a native ES Module (type="module").
 */
document.addEventListener( 'DOMContentLoaded', () => {
	if ( typeof initGlobalSearch === 'function' ) {
		initGlobalSearch();
	}

	const urlParams  = new URLSearchParams( window.location.search );
	const activePage = urlParams.get( 'page' ) || 'roxytable';

	if ( activePage === 'roxytable-import' ) {
		initImportPage();
		initShortcodeCopy();   // success screen — safe to call even when element absent
	}

	if ( activePage === 'roxytable-tables' ) {
		initTablesList();
	}

} );

/* =========================================================
   Import page — all three steps
   ========================================================= */

function initImportPage() {
	initDropzone();
	initZoomControls();
	initPreviewSearch();
	initConfigForm();       // Phase 4 — config form on step 3
}

/* ---------------------------------------------------------
   Step 1 — Drag-drop upload zone
   --------------------------------------------------------- */

function initDropzone() {
	const zone        = document.getElementById( 'roxytable-dropzone' );
	const fileInput   = document.getElementById( 'roxytable-file-input' );
	const browseBtn   = document.getElementById( 'roxytable-browse-btn' );
	const fileInfo    = document.getElementById( 'roxytable-file-info' );
	const submitBtn   = document.getElementById( 'roxytable-upload-submit' );

	if ( ! zone ) return; // Not on step 1

	// Diagnostics: ensure expected elements exist
	if ( ! fileInput ) console.warn( 'RoxyTable: file input (#roxytable-file-input) not found.' );
	if ( ! browseBtn ) console.warn( 'RoxyTable: browse button (#roxytable-browse-btn) not found.' );

	// ----- Browse button triggers hidden file input -----
	if ( browseBtn && fileInput ) {
		browseBtn.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			fileInput.click();
		} );

		// Keyboard accessibility: Enter / Space also open file picker
		browseBtn.addEventListener( 'keydown', ( e ) => {
			if ( e.key === 'Enter' || e.key === ' ' ) {
				e.preventDefault();
				fileInput.click();
			}
		} );
	}

	// ----- Click anywhere on the zone -----
	zone.addEventListener( 'click', () => fileInput.click() );

	// ----- File selected via dialog -----
	fileInput.addEventListener( 'change', () => {
		if ( fileInput.files.length > 0 ) {
			applyFileSelected( fileInput.files[0] );
		}
	} );

	// ----- Drag events -----
	let dragCounter = 0; // Track nested children

	zone.addEventListener( 'dragenter', ( e ) => {
		e.preventDefault();
		dragCounter++;
		zone.classList.add( 'is-dragover' );
	} );

	zone.addEventListener( 'dragover', ( e ) => {
		e.preventDefault(); // Required to allow drop
	} );

	zone.addEventListener( 'dragleave', () => {
		dragCounter--;
		if ( dragCounter <= 0 ) {
			dragCounter = 0;
			zone.classList.remove( 'is-dragover' );
		}
	} );

	zone.addEventListener( 'drop', ( e ) => {
		e.preventDefault();
		dragCounter = 0;
		zone.classList.remove( 'is-dragover' );

		const files = e.dataTransfer.files;
		if ( ! files || files.length === 0 ) return;

		const file = files[0];

		// Validate extension client-side (server validates authoritatively)
		const ext = file.name.split( '.' ).pop().toLowerCase();
		const allowed = [ 'xlsx', 'xls', 'csv' ];
		if ( ! allowed.includes( ext ) ) {
			showDropzoneError( zone, `"${escHtml( file.name )}" is not a supported file type. Please use .xlsx, .xls or .csv.` );
			return;
		}

		// Assign to the hidden file input via DataTransfer
		try {
			const dt = new DataTransfer();
			dt.items.add( file );
			fileInput.files = dt.files;
		} catch {
			// DataTransfer not supported (very old browser) — show instructions
			showDropzoneError( zone, 'Drag-drop not fully supported in this browser. Please use the Browse button.' );
			return;
		}

		applyFileSelected( file );
	} );

	// ----- Helpers -----

	function applyFileSelected( file ) {
		zone.classList.remove( 'is-dragover' );
		zone.classList.add( 'has-file' );

		const sizeLabel = formatBytes( file.size );
		fileInfo.textContent  = `📄 ${file.name}  (${sizeLabel})`;
		fileInfo.style.display = 'block';

		// Enable submit
		submitBtn.disabled = false;
		submitBtn.style.opacity = '1';
	}

	function showDropzoneError( zone, message ) {
		zone.classList.remove( 'has-file', 'is-dragover' );
		fileInfo.innerHTML     = `⚠️ ${message}`;
		fileInfo.style.display = 'block';
		fileInfo.style.backgroundColor = '#fef2f2';
		fileInfo.style.color = '#991b1b';
	}
}

/* ---------------------------------------------------------
   Step 3 — Zoom controls (A- / Reset / A+)
   --------------------------------------------------------- */

function initZoomControls() {
	const table    = document.getElementById( 'roxytable-preview-table' );
	const zoomIn   = document.getElementById( 'roxytable-zoom-in' );
	const zoomOut  = document.getElementById( 'roxytable-zoom-out' );
	const zoomReset = document.getElementById( 'roxytable-zoom-reset' );

	if ( ! table ) return; // Not on step 3

	const ZOOM_LEVELS = [ 'roxytable-zoom-sm', 'roxytable-zoom-md', 'roxytable-zoom-lg' ];
	let currentLevel  = 1; // index into ZOOM_LEVELS — default is 'md'

	function applyZoom( level ) {
		ZOOM_LEVELS.forEach( ( cls ) => table.classList.remove( cls ) );
		table.classList.add( ZOOM_LEVELS[ level ] );
		currentLevel = level;

		// Visual feedback on buttons
		zoomOut.style.opacity  = level === 0 ? '0.4' : '1';
		zoomIn.style.opacity   = level === ZOOM_LEVELS.length - 1 ? '0.4' : '1';
	}

	// Initialise to default (md)
	applyZoom( currentLevel );

	zoomIn.addEventListener( 'click', () => {
		if ( currentLevel < ZOOM_LEVELS.length - 1 ) applyZoom( currentLevel + 1 );
	} );

	zoomOut.addEventListener( 'click', () => {
		if ( currentLevel > 0 ) applyZoom( currentLevel - 1 );
	} );

	zoomReset.addEventListener( 'click', () => applyZoom( 1 ) );
}

/* ---------------------------------------------------------
   Step 3 — Live preview row search
   --------------------------------------------------------- */

function initPreviewSearch() {
	const searchInput = document.getElementById( 'roxytable-preview-search' );
	const table       = document.getElementById( 'roxytable-preview-table' );

	if ( ! searchInput || ! table ) return; // Not on step 3

	const tbody = table.querySelector( 'tbody' );
	if ( ! tbody ) return;

	initPreviewFilters( table );

	searchInput.addEventListener( 'input', () => {
		renderPreviewRows( table );
	} );
}

function initPreviewFilters( table ) {
	const filterContainer = document.getElementById( 'roxytable-preview-filters' );
	if ( ! filterContainer ) return;

	const headers = Array.from( table.querySelectorAll( 'thead th' ) ).map( ( th ) => th.textContent.trim() );
	const rows = Array.from( table.querySelectorAll( 'tbody tr' ) );

	const candidateColumns = headers.map( ( header, index ) => {
		const values = new Set();
		rows.forEach( ( row ) => {
			const cell = row.children[ index ];
			if ( cell ) {
				const text = cell.textContent.trim();
				if ( text !== '' ) {
					values.add( text );
				}
			}
		} );
		return {
			header,
			index,
			values: Array.from( values ).sort(),
			size: values.size,
		};
	} ).filter( ( column ) => column.size > 1 && column.size <= 50 );

	// Show filters for every suitable column from the sheet.
	candidateColumns.sort( ( a, b ) => a.size - b.size );
	// Create a custom searchable dropdown for each column
	candidateColumns.forEach( ( column ) => {
		const dd = createCustomDropdown( column, table );
		filterContainer.appendChild( dd );
	} );
}

function createCustomDropdown( column, table ) {
	const wrapper = document.createElement( 'div' );
	wrapper.className = 'rt-dropdown';
	wrapper.dataset.columnIndex = String( column.index );

	const toggle = document.createElement( 'button' );
	toggle.type = 'button';
	toggle.className = 'rt-dropdown-toggle roxytable-btn';
	toggle.innerHTML = `<span class="rt-dropdown-label">${ column.header }</span> <span class="rt-dropdown-value">${ window.RoxyTable ? window.RoxyTable.allLabel || 'All' : 'All' }</span> <span class="rt-dropdown-caret">▾</span>`;
	wrapper.appendChild( toggle );

	const menu = document.createElement( 'div' );
	menu.className = 'rt-dropdown-menu';
	menu.style.display = 'none';

	const search = document.createElement( 'input' );
	search.type = 'search';
	search.placeholder = 'Search...';
	search.className = 'rt-dropdown-search';
	menu.appendChild( search );

	const list = document.createElement( 'div' );
	list.className = 'rt-dropdown-list';

	column.values.forEach( ( value ) => {
		const item = document.createElement( 'button' );
		item.type = 'button';
		item.className = 'rt-dropdown-item';
		item.textContent = value;
		item.dataset.value = value;
		item.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			toggle.dataset.value = value;
			const labelEl = toggle.querySelector('.rt-dropdown-value');
			if ( labelEl ) labelEl.textContent = value;
			// close menu
			menu.style.display = 'none';
			renderPreviewRows( table );
		} );
		list.appendChild( item );
	} );

	// 'All' item to clear filter
	const clear = document.createElement( 'button' );
	clear.type = 'button';
	clear.className = 'rt-dropdown-item rt-dropdown-clear';
	clear.textContent = `${ column.header } — ${ window.RoxyTable ? window.RoxyTable.allLabel || 'All' : 'All' }`;
	clear.addEventListener( 'click', ( e ) => {
		e.preventDefault();
		delete toggle.dataset.value;
		const labelEl = toggle.querySelector('.rt-dropdown-value');
		if ( labelEl ) labelEl.textContent = window.RoxyTable ? window.RoxyTable.allLabel || 'All' : 'All';
		menu.style.display = 'none';
		renderPreviewRows( table );
	} );
	list.insertBefore( clear, list.firstChild );

	menu.appendChild( list );
	wrapper.appendChild( menu );

	// Toggle menu
	toggle.addEventListener( 'click', ( e ) => {
		e.preventDefault();
		menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
	} );

	// Close when clicking outside
	document.addEventListener( 'click', ( e ) => {
		if ( ! wrapper.contains( e.target ) ) menu.style.display = 'none';
	} );

	// Search within menu
	search.addEventListener( 'input', () => {
		const q = search.value.trim().toLowerCase();
		Array.from( list.querySelectorAll( '.rt-dropdown-item' ) ).forEach( ( it ) => {
			if ( it.classList.contains( 'rt-dropdown-clear' ) ) return;
			it.style.display = q === '' || it.textContent.toLowerCase().includes( q ) ? '' : 'none';
		} );
	} );

	return wrapper;

function renderPreviewRows( table ) {
	const searchInput = document.getElementById( 'roxytable-preview-search' );
	const filterContainer = document.getElementById( 'roxytable-preview-filters' );
	const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
	const selects = filterContainer ? Array.from( filterContainer.querySelectorAll( 'select' ) ) : [];
	const tbody = table.querySelector( 'tbody' );
	if ( ! tbody ) return;

	const rows = Array.from( tbody.querySelectorAll( 'tr' ) );
	rows.forEach( ( row ) => {
		const cells = Array.from( row.querySelectorAll( 'td' ) );
		let matchesSearch = query === '';
		let matchesFilters = true;

		cells.forEach( ( cell ) => {
			cell.classList.remove( 'rt-search-match' );
			if ( query === '' ) return;
			const text = cell.textContent.toLowerCase();
			if ( text.includes( query ) ) {
				matchesSearch = true;
				cell.classList.add( 'rt-search-match' );
			}
		} );

		selects.forEach( ( select ) => {
			const selectedValue = select.value;
			if ( selectedValue === '' ) return;
			const index = parseInt( select.dataset.columnIndex, 10 );
			const cell = cells[ index ];
			if ( ! cell || cell.textContent.trim() !== selectedValue ) {
				matchesFilters = false;
			}
		} );

		row.style.display = matchesSearch && matchesFilters ? '' : 'none';
	} );

	updateNoResultsBanner( tbody, query );
}

function updateNoResultsBanner( tbody, query ) {
	const existingBanner = tbody.querySelector( '.rt-no-results-row' );

	const visibleRows = Array.from( tbody.querySelectorAll( 'tr:not(.rt-no-results-row)' ) )
		.filter( ( r ) => r.style.display !== 'none' );

	if ( query && visibleRows.length === 0 ) {
		if ( ! existingBanner ) {
			const colspan = tbody.closest( 'table' ).querySelectorAll( 'thead th' ).length || 1;
			const row = document.createElement( 'tr' );
			row.className = 'rt-no-results-row';
			row.innerHTML = `<td colspan="${colspan}" style="text-align:center;padding:24px;color:#64748b;font-style:italic;">No rows match "<strong>${escHtml( query )}</strong>"</td>`;
			tbody.appendChild( row );
		}
	} else if ( existingBanner ) {
		existingBanner.remove();
	}
}

/* =========================================================
   Utility helpers
   ========================================================= */

function formatBytes( bytes ) {
	if ( bytes === 0 ) return '0 B';
	const k     = 1024;
	const sizes = [ 'B', 'KB', 'MB', 'GB' ];
	const i     = Math.floor( Math.log( bytes ) / Math.log( k ) );
	return parseFloat( ( bytes / Math.pow( k, i ) ).toFixed( 1 ) ) + ' ' + sizes[ i ];
}

function escHtml( str ) {
	return String( str )
		.replace( /&/g, '&amp;' )
		.replace( /</g, '&lt;' )
		.replace( />/g, '&gt;' )
		.replace( /"/g, '&quot;' );
}

/* =========================================================
   Phase 4 — Config form: auto-slug from name
   ========================================================= */

function initConfigForm() {
	const nameInput = document.getElementById( 'rt-table-name' );
	const slugInput = document.getElementById( 'rt-table-slug' );

	if ( ! nameInput || ! slugInput ) return; // Not on step 3 config form

	let userEditedSlug = false;
	let debounceTimer  = null;

	// If user manually edits slug field, stop auto-generation
	slugInput.addEventListener( 'input', () => {
		userEditedSlug = true;
	} );

	// Auto-generate slug from name with 400ms debounce
	nameInput.addEventListener( 'input', () => {
		if ( userEditedSlug ) return;

		clearTimeout( debounceTimer );
		debounceTimer = setTimeout( () => {
			slugInput.value = sanitizeSlug( nameInput.value );
		}, 400 );
	} );

	// Normalise slug when user leaves the slug field
	slugInput.addEventListener( 'blur', () => {
		if ( slugInput.value ) {
			slugInput.value = sanitizeSlug( slugInput.value );
		}
	} );

	// Trigger initial slug if name already has a value (pre-filled from sheet name)
	if ( nameInput.value && ! slugInput.value ) {
		slugInput.value = sanitizeSlug( nameInput.value );
	}

	// Submit: show loading state
	const form      = document.getElementById( 'rt-confirm-import-form' );
	const submitBtn = document.getElementById( 'rt-submit-import' );

	if ( form && submitBtn ) {
		form.addEventListener( 'submit', () => {
			submitBtn.disabled     = true;
			submitBtn.textContent  = 'Saving…';
			submitBtn.style.opacity = '0.7';
		} );
	}
}

/**
 * Convert a free-text string into a URL-safe slug.
 * Mirrors WordPress sanitize_title() heuristics.
 *
 * @param {string} str
 * @returns {string}
 */
function sanitizeSlug( str ) {
	return str
		.toLowerCase()
		.trim()
		.replace( /[^\w\s-]/g, '' )   // remove non-word chars (keep hyphens)
		.replace( /[\s_]+/g, '-' )    // spaces / underscores → hyphens
		.replace( /-{2,}/g, '-' )     // collapse multiple hyphens
		.replace( /^-+|-+$/g, '' );   // trim leading / trailing hyphens
}

/* =========================================================
   Phase 4 — Success screen: shortcode copy button
   ========================================================= */

function initTablesList() {
	console.debug && console.debug( 'RoxyTable: initTablesList() running' );
	const selectAll = document.getElementById( 'rt-select-all-tables' );
	if ( selectAll ) {
		selectAll.addEventListener( 'change', () => {
			const checkboxes = document.querySelectorAll( 'input[name="rt_table_ids[]"]' );
			checkboxes.forEach( ( checkbox ) => {
				checkbox.checked = selectAll.checked;
			} );
		} );
	}

	document.querySelectorAll( '.rt-copy-shortcode' ).forEach( ( button ) => {
		button.addEventListener( 'click', async () => {
			const shortcode = button.dataset.shortcode || '';
			try {
				await navigator.clipboard.writeText( shortcode );
			} catch {
				const ta = document.createElement( 'textarea' );
				ta.value = shortcode;
				ta.style.position = 'absolute';
				ta.style.left = '-9999px';
				document.body.appendChild( ta );
				ta.select();
				document.execCommand( 'copy' );
				document.body.removeChild( ta );
			}

			const originalText = button.textContent;
			button.textContent = 'Copied!';
			setTimeout( () => {
				button.textContent = originalText;
			}, 1200 );
		} );
	} );

	// Action link: show small popup with shortcode and copy button
	// Attach to existing links and also via delegated listener for dynamic rows
	const actionLinks = document.querySelectorAll( '.rt-action-shortcode' );
	console.debug && console.debug( 'RoxyTable: rt-action-shortcode links found', actionLinks.length );

	actionLinks.forEach( ( link ) => {
		link.addEventListener( 'click', ( e ) => handleShortcodeActionClick( e, link ) );
	} );

	// Native browser confirmation for delete actions
	document.querySelectorAll( '.rt-action-delete' ).forEach( ( link ) => {
		link.addEventListener( 'click', ( e ) => {
			const message = link.dataset.confirm || 'Are you sure?';
			if ( ! window.confirm( message ) ) {
				e.preventDefault();
			}
		} );
	} );

	// Delegated listener to catch clicks if rows are updated dynamically
	document.addEventListener( 'click', ( e ) => {
		const target = e.target.closest && e.target.closest( '.rt-action-shortcode' );
		if ( target ) {
			e.preventDefault();
			handleShortcodeActionClick( e, target );
		}
	} );


function handleShortcodeActionClick( e, link ) {
	try { console.debug && console.debug( 'RoxyTable: handleShortcodeActionClick', link ); } catch ( _ ) {}
	const shortcode = link.dataset.shortcode || '';

	// Remove any existing popup
	const existing = document.querySelector( '.rt-shortcode-popup' );
	if ( existing ) existing.remove();

	// Create popup element
	const popup = document.createElement( 'div' );
	popup.className = 'rt-shortcode-popup';
	popup.innerHTML = `
		<div class="rt-shortcode-popup-inner">
			<button type="button" class="rt-shortcode-popup-close" aria-label="Close">×</button>
			<div class="rt-shortcode-popup-body">
				<label class="rt-shortcode-popup-label">Shortcode</label>
				<input class="rt-shortcode-popup-input" readonly value="${shortcode}" />
				<div style="display:flex;gap:8px;margin-top:8px;justify-content:flex-end;">
					<button type="button" class="rt-shortcode-popup-copy roxytable-btn roxytable-btn-primary">Copy</button>
				</div>
			</div>
		</div>`;

	document.body.appendChild( popup );

	// Position popup near link
	const rect = link.getBoundingClientRect();
	const top = rect.bottom + window.scrollY + 8; // below link
	let left = rect.left + window.scrollX;
	const maxWidth = Math.min( 420, document.documentElement.clientWidth - 24 );
	popup.style.maxWidth = maxWidth + 'px';
	const popupWidth = Math.min( maxWidth, 360 );
	if ( left + popupWidth + 16 > window.scrollX + document.documentElement.clientWidth ) {
		left = window.scrollX + document.documentElement.clientWidth - popupWidth - 16;
	}
	popup.style.position = 'absolute';
	popup.style.top = top + 'px';
	popup.style.left = left + 'px';

	const input = popup.querySelector( '.rt-shortcode-popup-input' );
	const copyBtn = popup.querySelector( '.rt-shortcode-popup-copy' );
	const closeBtn = popup.querySelector( '.rt-shortcode-popup-close' );

	// Auto-select input contents
	input.focus();
	input.select();

	const removePopup = () => {
		if ( popup && popup.parentNode ) popup.parentNode.removeChild( popup );
		document.removeEventListener( 'click', docClick);
		document.removeEventListener( 'keydown', escKey );
	};

	copyBtn.addEventListener( 'click', async () => {
		try {
			await navigator.clipboard.writeText( input.value );
		} catch {
			input.select();
			document.execCommand( 'copy' );
		}
		copyBtn.textContent = 'Copied!';
		setTimeout( () => { copyBtn.textContent = 'Copy'; }, 1200 );
	} );

	closeBtn.addEventListener( 'click', () => removePopup() );

	const docClick = ( ev ) => {
		if ( ! popup.contains( ev.target ) && ev.target !== link ) {
			removePopup();
		}
	};

	const escKey = ( ev ) => { if ( ev.key === 'Escape' ) removePopup(); };

	setTimeout( () => {
		document.addEventListener( 'click', docClick );
		document.addEventListener( 'keydown', escKey );
	}, 10 );
}
	// Diagnostic: log clicks on action links to see if clicks are reaching the page
	document.querySelectorAll( '.rt-table-row-actions a' ).forEach( ( link ) => {
		link.addEventListener( 'click', ( e ) => {
			try {
				console.log( 'RoxyTable: action link clicked', { href: link.href, text: link.textContent.trim() } );
			} catch ( err ) {
				console.log( 'RoxyTable: action link clicked (unable to read properties)' );
			}
			// Do not call preventDefault here — we want normal navigation to occur.
		} );
	} );
}

function initShortcodeCopy() {
	const copyBtn   = document.getElementById( 'rt-copy-shortcode' );
	const pill      = document.getElementById( 'rt-shortcode-pill' );
	const copyLabel = document.getElementById( 'rt-copy-label' );
	const copyIcon  = document.getElementById( 'rt-copy-icon' );

	if ( ! copyBtn ) return; // Not on success screen

	const shortcode = copyBtn.dataset.shortcode || pill?.textContent?.trim() || '';

	copyBtn.addEventListener( 'click', async () => {
		try {
			await navigator.clipboard.writeText( shortcode );
		} catch {
			// Fallback for older browsers / insecure contexts
			const ta = document.createElement( 'textarea' );
			ta.value = shortcode;
			ta.style.position = 'absolute';
			ta.style.left = '-9999px';
			document.body.appendChild( ta );
			ta.select();
			document.execCommand( 'copy' );
			document.body.removeChild( ta );
		}

		// Visual feedback
		copyBtn.classList.add( 'rt-copied' );
		pill?.classList.add( 'rt-copy-flash' );
		if ( copyLabel ) copyLabel.textContent = 'Copied!';

		// Swap icon to checkmark
		if ( copyIcon ) {
			copyIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />';
		}

		// Reset after 2 s
		setTimeout( () => {
			copyBtn.classList.remove( 'rt-copied' );
			pill?.classList.remove( 'rt-copy-flash' );
			if ( copyLabel ) copyLabel.textContent = 'Copy';
			if ( copyIcon ) {
				copyIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />';
			}
		}, 2000 );
	} );
}
