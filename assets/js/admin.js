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

	initAjaxForms();

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
}

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

	document.addEventListener( 'click', async ( event ) => {
		const button = event.target.closest && event.target.closest( '.rt-copy-shortcode' );
		if ( ! button ) return;

		event.preventDefault();
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
	const escapedShortcode = escHtml( shortcode );
	popup.innerHTML = `
		<div class="rt-shortcode-popup-inner">
			<button type="button" class="rt-shortcode-popup-close" aria-label="Close">×</button>
			<div class="rt-shortcode-popup-body">
				<label class="rt-shortcode-popup-label">Shortcode</label>
				<input class="rt-shortcode-popup-input" readonly value="${escapedShortcode}" />
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

/* =========================================================
   Native WordPress AJAX Handlers & Toast System
   ========================================================= */

function showToast( message, type = 'success', duration = null ) {
	let container = document.querySelector( '.rt-toast-container' );
	if ( ! container ) {
		container = document.createElement( 'div' );
		container.className = 'rt-toast-container';
		document.body.appendChild( container );
	}
	const toast = document.createElement( 'div' );
	toast.className = `rt-toast rt-toast-${type}`;
	const icon = type === 'success' ? '✓' : ( type === 'error' ? '✕' : 'ℹ' );
	toast.innerHTML = `<span style="font-weight:700; flex-shrink:0;">${icon}</span> <span style="line-height:1.4;">${escHtml( message )}</span>`;
	container.appendChild( toast );

	const displayTime = duration !== null ? duration : ( type === 'error' ? 6000 : 3500 );

	setTimeout( () => {
		toast.style.opacity = '0';
		toast.style.transform = 'translateY(16px)';
		setTimeout( () => toast.remove(), 300 );
	}, displayTime );
}

function initAjaxForms() {
	const ajaxData = window.roxyAdminData;
	if ( ! ajaxData || ! ajaxData.ajax_url || ! ajaxData.nonces ) return;

	// 1. Table Metadata Edit Form (#rt-edit-table-meta-form)
	const metaForm = document.getElementById( 'rt-edit-table-meta-form' );
	if ( metaForm ) {
		metaForm.addEventListener( 'submit', async ( e ) => {
			e.preventDefault();
			const submitBtn = metaForm.querySelector( 'button[type="submit"]' );
			const originalText = submitBtn ? submitBtn.textContent : '';

			if ( submitBtn ) {
				submitBtn.disabled = true;
				submitBtn.textContent = ajaxData.i18n?.saving || 'Saving...';
				submitBtn.style.opacity = '0.7';
			}

			try {
				const formData = new FormData( metaForm );
				formData.append( 'action', 'roxytable_save_table' );
				formData.append( 'nonce', ajaxData.nonces.save_table );

				const res = await fetch( ajaxData.ajax_url, {
					method: 'POST',
					body: formData
				} );
				let json = null;
				try {
					const raw = await res.text();
					json = JSON.parse( raw );
				} catch ( parseErr ) {
					console.warn( '[RoxyTable] Non-JSON response received during save table:', parseErr );
				}

				if ( res.ok && json && json.success ) {
					showToast( json.data?.message || ajaxData.i18n?.saved || 'Saved successfully!', 'success' );
				} else {
					showToast( json?.data?.message || ajaxData.i18n?.error || 'An error occurred.', 'error' );
				}
			} catch ( err ) {
				showToast( ajaxData.i18n?.error || 'An error occurred.', 'error' );
			} finally {
				if ( submitBtn ) {
					submitBtn.disabled = false;
					submitBtn.textContent = originalText;
					submitBtn.style.opacity = '1';
				}
			}
		} );
	}

	// 1b. Edit Action click handler on tables list & Options section toggle on edit screen
	document.addEventListener( 'click', ( e ) => {
		const editBtn = e.target.closest && e.target.closest( '.rt-action-edit' );
		if ( ! editBtn ) return;

		const tableId = editBtn.dataset.tableId;
		if ( tableId ) {
			sessionStorage.setItem( `roxytable_options_visible_${tableId}`, '1' );
			localStorage.setItem( 'roxytable_last_edited_table', tableId );
		}
	} );

	const optionsSection = document.getElementById( 'rt-table-options-section' );
	const toggleOptionsBtn = document.getElementById( 'rt-toggle-options-btn' );
	const toggleOptionsText = document.getElementById( 'rt-toggle-options-text' );

	if ( optionsSection && toggleOptionsBtn ) {
		const activeTableId = optionsSection.dataset.tableId;
		const storageKey = `roxytable_options_visible_${activeTableId}`;

		function applyOptionsVisibility( isVisible ) {
			if ( isVisible ) {
				optionsSection.classList.remove( 'is-hidden' );
				optionsSection.style.display = 'flex';
				toggleOptionsBtn.setAttribute( 'aria-expanded', 'true' );
				if ( toggleOptionsText ) toggleOptionsText.textContent = 'Hide Options';
			} else {
				optionsSection.classList.add( 'is-hidden' );
				optionsSection.style.display = 'none';
				toggleOptionsBtn.setAttribute( 'aria-expanded', 'false' );
				if ( toggleOptionsText ) toggleOptionsText.textContent = 'Show Options';
			}
		}

		// Initial state: default to visible immediately upon triggering edit action
		const storedVal = sessionStorage.getItem( storageKey );
		let isOptionsVisible = storedVal !== null ? storedVal === '1' : true;
		applyOptionsVisibility( isOptionsVisible );

		toggleOptionsBtn.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			isOptionsVisible = ! isOptionsVisible;
			sessionStorage.setItem( storageKey, isOptionsVisible ? '1' : '0' );
			applyOptionsVisibility( isOptionsVisible );
		} );

		// Smooth scroll if navigated with hash
		if ( window.location.hash === '#rt-table-options-section' ) {
			applyOptionsVisibility( true );
			setTimeout( () => {
				optionsSection.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
			}, 150 );
		}
	}

	// 2. Table Data Edit Form (#rt-edit-table-data-form)
	const dataForm = document.getElementById( 'rt-edit-table-data-form' );
	if ( dataForm ) {
		dataForm.addEventListener( 'submit', async ( e ) => {
			e.preventDefault();
			const submitBtn = dataForm.querySelector( 'button[type="submit"]' );
			const originalText = submitBtn ? submitBtn.textContent : '';

			if ( submitBtn ) {
				submitBtn.disabled = true;
				submitBtn.textContent = ajaxData.i18n?.saving || 'Saving...';
				submitBtn.style.opacity = '0.7';
			}

			try {
				const formData = new FormData( dataForm );
				formData.append( 'action', 'roxytable_save_data' );
				formData.append( 'nonce', ajaxData.nonces.save_data );

				const res = await fetch( ajaxData.ajax_url, {
					method: 'POST',
					body: formData
				} );
				const json = await res.json();

				if ( json.success ) {
					showToast( json.data?.message || ajaxData.i18n?.saved || 'Data saved successfully!', 'success' );
				} else {
					showToast( json.data?.message || ajaxData.i18n?.error || 'An error occurred.', 'error' );
				}
			} catch ( err ) {
				showToast( ajaxData.i18n?.error || 'An error occurred.', 'error' );
			} finally {
				if ( submitBtn ) {
					submitBtn.disabled = false;
					submitBtn.textContent = originalText;
					submitBtn.style.opacity = '1';
				}
			}
		} );
	}

	// 3. Single Table Delete Action (.rt-action-delete)
	document.addEventListener( 'click', async ( e ) => {
		const deleteLink = e.target.closest && e.target.closest( '.rt-action-delete' );
		if ( ! deleteLink ) return;

		e.preventDefault();

		const message = deleteLink.dataset.confirm || 'Are you sure you want to delete this table?';
		if ( ! window.confirm( message ) ) return;

		const url = new URL( deleteLink.href, window.location.origin );
		const tableId = url.searchParams.get( 'id' );
		const nonce = url.searchParams.get( '_wpnonce' );

		if ( ! tableId ) return;

		const row = deleteLink.closest( 'tr' );
		deleteLink.style.opacity = '0.5';
		deleteLink.style.pointerEvents = 'none';

		try {
			const formData = new FormData();
			formData.append( 'action', 'roxytable_delete_table' );
			formData.append( 'table_id', tableId );
			formData.append( 'nonce', nonce || ajaxData.nonces.delete_table || '' );

			const res = await fetch( ajaxData.ajax_url, {
				method: 'POST',
				body: formData
			} );

			let json = null;
			try {
				const raw = await res.text();
				json = JSON.parse( raw );
			} catch ( parseErr ) {
				console.warn( '[RoxyTable] Non-JSON response received during delete:', parseErr );
			}

			if ( res.ok && json && json.success ) {
				showToast( json.data?.message || 'Table deleted successfully!', 'success' );
				if ( row ) {
					row.style.transition = 'all 0.3s ease';
					row.style.opacity = '0';
					row.style.height = '0';
					setTimeout( () => row.remove(), 300 );
				}
			} else {
				showToast( json?.data?.message || 'Failed to delete table.', 'error' );
				deleteLink.style.opacity = '1';
				deleteLink.style.pointerEvents = 'auto';
			}
		} catch ( err ) {
			showToast( ajaxData.i18n?.error || 'An error occurred.', 'error' );
			deleteLink.style.opacity = '1';
			deleteLink.style.pointerEvents = 'auto';
		}
	} );

	// 4. Bulk Table Actions Form (#rt-tables-bulk-form)
	const bulkForm = document.getElementById( 'rt-tables-bulk-form' );
	if ( bulkForm ) {
		bulkForm.addEventListener( 'submit', async ( e ) => {
			const actionSelect = bulkForm.querySelector( 'select[name="rt_bulk_action"]' );
			const selectedAction = actionSelect ? actionSelect.value : '';

			// CSV export requires native download navigation
			if ( selectedAction === 'export_csv' || ! selectedAction ) return;

			e.preventDefault();

			const checkedBoxes = Array.from( bulkForm.querySelectorAll( 'input[name="rt_table_ids[]"]:checked' ) );
			if ( checkedBoxes.length === 0 ) {
				showToast( 'Please select at least one table.', 'error' );
				return;
			}

			if ( selectedAction === 'delete' && ! window.confirm( `Are you sure you want to delete ${checkedBoxes.length} selected tables?` ) ) {
				return;
			}

			const submitBtn = bulkForm.querySelector( 'button[name="roxytable_tables_bulk_action"]' );
			const originalText = submitBtn ? submitBtn.textContent : '';
			if ( submitBtn ) {
				submitBtn.disabled = true;
				submitBtn.textContent = ajaxData.i18n?.saving || 'Applying...';
			}

			try {
				const formData = new FormData( bulkForm );
				formData.append( 'action', 'roxytable_bulk_tables' );
				formData.append( 'nonce', ajaxData.nonces.bulk_tables );

				const res = await fetch( ajaxData.ajax_url, {
					method: 'POST',
					body: formData
				} );
				const json = await res.json();

				if ( json.success ) {
					showToast( json.data?.message || 'Bulk action applied successfully!', 'success' );
					if ( json.data?.action === 'delete' ) {
						checkedBoxes.forEach( ( cb ) => {
							const tr = cb.closest( 'tr' );
							if ( tr ) tr.remove();
						} );
					} else if ( json.data?.action === 'status' ) {
						const newStatus = json.data?.status;
						checkedBoxes.forEach( ( cb ) => {
							const tr = cb.closest( 'tr' );
							if ( tr ) {
								const badge = tr.querySelector( 'td span[style*="border-radius"]' );
								if ( badge ) {
									if ( newStatus === 'published' ) {
										badge.textContent = 'Published';
										badge.style.background = '#ecfdf5';
										badge.style.color = '#10b981';
									} else {
										badge.textContent = 'Draft';
										badge.style.background = '#fffbeb';
										badge.style.color = '#f59e0b';
									}
								}
							}
						} );
					}
				} else {
					showToast( json.data?.message || ajaxData.i18n?.error || 'An error occurred.', 'error' );
				}
			} catch ( err ) {
				showToast( ajaxData.i18n?.error || 'An error occurred.', 'error' );
			} finally {
				if ( submitBtn ) {
					submitBtn.disabled = false;
					submitBtn.textContent = originalText;
				}
			}
		} );
	}

	// 5. Template Selection Forms (.rt-template-form)
	document.querySelectorAll( '.rt-template-form' ).forEach( ( form ) => {
		form.addEventListener( 'submit', async ( e ) => {
			e.preventDefault();
			const submitBtn = form.querySelector( 'button[type="submit"]' );
			const templateVal = submitBtn ? submitBtn.value : '';
			const originalText = submitBtn ? submitBtn.textContent : '';

			if ( submitBtn ) {
				submitBtn.disabled = true;
				submitBtn.textContent = 'Creating Table...';
				submitBtn.style.opacity = '0.7';
			}

			try {
				const formData = new FormData();
				formData.append( 'action', 'roxytable_use_template' );
				formData.append( 'template', templateVal );
				formData.append( 'nonce', ajaxData.nonces.use_template );

				const res = await fetch( ajaxData.ajax_url, {
					method: 'POST',
					body: formData
				} );
				const json = await res.json();

				if ( json.success && json.data?.redirect_url ) {
					showToast( json.data?.message || 'Template applied!', 'success' );
					setTimeout( () => {
						window.location.href = json.data.redirect_url;
					}, 500 );
				} else {
					showToast( json.data?.message || 'Failed to apply template.', 'error' );
					if ( submitBtn ) {
						submitBtn.disabled = false;
						submitBtn.textContent = originalText;
						submitBtn.style.opacity = '1';
					}
				}
			} catch ( err ) {
				showToast( 'An error occurred.', 'error' );
				if ( submitBtn ) {
					submitBtn.disabled = false;
					submitBtn.textContent = originalText;
					submitBtn.style.opacity = '1';
				}
			}
		} );
	} );

	// Helper functions for import alert banner
	const uploadAlertBox = document.getElementById( 'roxytable-upload-alert' );
	const uploadAlertMsg = document.getElementById( 'roxytable-upload-alert-message' );
	const uploadAlertClose = document.getElementById( 'roxytable-upload-alert-close' );

	if ( uploadAlertClose ) {
		uploadAlertClose.addEventListener( 'click', () => {
			if ( uploadAlertBox ) uploadAlertBox.style.display = 'none';
		} );
	}

	function showUploadAlert( message ) {
		if ( uploadAlertBox && uploadAlertMsg ) {
			uploadAlertMsg.innerHTML = message;
			uploadAlertBox.style.display = 'block';
			uploadAlertBox.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
		}
	}

	function hideUploadAlert() {
		if ( uploadAlertBox ) {
			uploadAlertBox.style.display = 'none';
		}
	}

	// 6. Import Step 1 Upload Form (#roxytable-upload-form)
	const uploadForm = document.getElementById( 'roxytable-upload-form' );
	if ( uploadForm ) {
		uploadForm.addEventListener( 'submit', async ( e ) => {
			e.preventDefault();
			hideUploadAlert();

			const submitBtn = document.getElementById( 'roxytable-upload-submit' );
			const originalText = submitBtn ? submitBtn.textContent : '';

			// 1. Client-side pre-validation: file selection
			const fileInput = document.getElementById( 'roxytable-file-input' );
			if ( ! fileInput || ! fileInput.files || fileInput.files.length === 0 ) {
				const noFileMsg = ajaxData.i18n?.no_file_selected || 'Please select a spreadsheet file (.xlsx, .xls, or .csv) to upload.';
				showToast( noFileMsg, 'error' );
				showUploadAlert( `⚠️ <strong>${noFileMsg}</strong>` );
				return;
			}

			const file = fileInput.files[0];

			// 2. Client-side pre-validation: extension
			const ext = file.name.split( '.' ).pop().toLowerCase();
			const allowedExts = ajaxData.allowed_extensions || [ 'xlsx', 'xls', 'csv' ];
			if ( ! allowedExts.includes( ext ) ) {
				const extMsg = `Invalid file format "<strong>.${escHtml( ext )}</strong>". RoxyTable only supports <strong>.xlsx, .xls, and .csv</strong> files.`;
				showToast( `Invalid file format ".${ext}". Please choose a .xlsx, .xls, or .csv file.`, 'error' );
				showUploadAlert( `⚠️ ${extMsg}` );
				return;
			}

			// 3. Client-side pre-validation: file size
			if ( ajaxData.max_upload_size && file.size > ajaxData.max_upload_size ) {
				const maxFormatted = ajaxData.max_upload_size_formatted || formatBytes( ajaxData.max_upload_size );
				const sizeMsg = `File size (<strong>${formatBytes( file.size )}</strong>) exceeds the server maximum upload limit of <strong>${maxFormatted}</strong>. Please choose a smaller file.`;
				showToast( `File size exceeds maximum upload limit of ${maxFormatted}.`, 'error' );
				showUploadAlert( `⚠️ ${sizeMsg}` );
				return;
			}

			if ( submitBtn ) {
				submitBtn.disabled = true;
				submitBtn.textContent = ajaxData.i18n?.uploading || 'Uploading file...';
				submitBtn.style.opacity = '0.7';
			}

			console.groupCollapsed( `[RoxyTable] Uploading ${file.name} (${formatBytes( file.size )})` );
			console.log( 'Endpoint:', ajaxData.ajax_url );
			console.log( 'File details:', { name: file.name, size: file.size, type: file.type } );

			try {
				const formData = new FormData( uploadForm );
				formData.append( 'action', 'roxytable_upload_file' );
				formData.append( 'nonce', ajaxData.nonces.upload_file );

				const res = await fetch( ajaxData.ajax_url, {
					method: 'POST',
					body: formData
				} );

				console.log( 'Response status:', res.status, res.statusText );

				let rawText = '';
				let json = null;

				try {
					rawText = await res.text();
					json = JSON.parse( rawText );
				} catch ( parseErr ) {
					console.warn( '[RoxyTable] Non-JSON server response received:', rawText );
				}

				if ( res.ok && json && json.success ) {
					console.log( '[RoxyTable] Upload succeeded:', json.data );
					console.groupEnd();
					showToast( json.data?.message || 'File uploaded successfully!', 'success' );
					setTimeout( () => {
						window.location.href = window.location.pathname + '?page=roxytable-import&step=2';
					}, 400 );
				} else {
					let errorTitle = 'Upload Failed';
					let errorDetail = '';

					if ( json && json.data && json.data.message ) {
						errorDetail = json.data.message;
						if ( json.data.code ) {
							errorTitle = `Upload Failed (${json.data.code})`;
						}
					} else if ( res.status === 403 ) {
						errorTitle = 'Access Denied (403)';
						errorDetail = ajaxData.i18n?.permission_error || 'You do not have permission to upload files, or your security session has expired. Please refresh the page and try again.';
					} else if ( res.status === 413 ) {
						errorTitle = 'File Too Large (413)';
						errorDetail = `The server rejected the file because its size (${formatBytes( file.size )}) exceeds the server's post limit (${ajaxData.max_upload_size_formatted || ''}).`;
					} else if ( res.status === 500 ) {
						errorTitle = 'Server Error (500)';
						errorDetail = 'The server encountered an internal error while processing the spreadsheet. Please verify PHP error logs or contact your server administrator.';
					} else if ( res.status >= 502 && res.status <= 504 ) {
						errorTitle = `Server Unavailable (${res.status})`;
						errorDetail = 'The server or gateway is temporarily unable to complete your request. Please wait a moment and try again.';
					} else if ( ! res.ok ) {
						errorTitle = `HTTP Error ${res.status}`;
						errorDetail = res.statusText || 'An unexpected server error occurred.';
					} else if ( rawText && rawText.length < 200 && ! rawText.includes( '<' ) ) {
						errorDetail = rawText;
					} else {
						errorDetail = ajaxData.i18n?.error || 'An unexpected error occurred while uploading. Please check the browser console for details.';
					}

					console.error( '[RoxyTable] Upload failure details:', { status: res.status, errorTitle, errorDetail, rawText } );
					console.groupEnd();

					showToast( errorDetail.replace( /<[^>]+>/g, '' ), 'error' );
					showUploadAlert( `<strong>${errorTitle}:</strong> ${errorDetail}` );

					if ( submitBtn ) {
						submitBtn.disabled = false;
						submitBtn.textContent = originalText;
						submitBtn.style.opacity = '1';
					}
				}
			} catch ( err ) {
				console.error( '[RoxyTable] Network/runtime error during upload:', err );
				console.groupEnd();

				let networkMsg = '';
				if ( ! navigator.onLine ) {
					networkMsg = 'Network Offline: You appear to be disconnected from the internet. Please check your network and try again.';
				} else if ( err.name === 'AbortError' ) {
					networkMsg = 'Upload Timeout: The server took too long to respond. The file may be too large or the server is busy.';
				} else {
					networkMsg = `Network Error: ${err.message || 'Unable to connect to WordPress server.'}. Please verify your network connection.`;
				}

				showToast( networkMsg, 'error' );
				showUploadAlert( `<strong>Connection Issue:</strong> ${networkMsg}` );

				if ( submitBtn ) {
					submitBtn.disabled = false;
					submitBtn.textContent = originalText;
					submitBtn.style.opacity = '1';
				}
			}
		} );
	}

	// 7. Import Step 3 Confirm Form (#rt-confirm-import-form)
	const confirmImportForm = document.getElementById( 'rt-confirm-import-form' );
	if ( confirmImportForm ) {
		confirmImportForm.addEventListener( 'submit', async ( e ) => {
			e.preventDefault();
			const submitBtn = document.getElementById( 'rt-submit-import' );
			const originalText = submitBtn ? submitBtn.textContent : '';

			if ( submitBtn ) {
				submitBtn.disabled = true;
				submitBtn.textContent = ajaxData.i18n?.saving || 'Saving Table...';
				submitBtn.style.opacity = '0.7';
			}

			console.groupCollapsed( '[RoxyTable] Confirming table import...' );

			try {
				const formData = new FormData( confirmImportForm );
				formData.append( 'action', 'roxytable_confirm_import' );
				formData.append( 'nonce', ajaxData.nonces.confirm_import );

				const res = await fetch( ajaxData.ajax_url, {
					method: 'POST',
					body: formData
				} );

				console.log( 'Confirm import response status:', res.status, res.statusText );

				let rawText = '';
				let json = null;

				try {
					rawText = await res.text();
					json = JSON.parse( rawText );
				} catch ( parseErr ) {
					console.warn( '[RoxyTable] Non-JSON server response on confirm import:', rawText );
				}

				if ( res.ok && json && json.success && json.data?.redirect_url ) {
					console.log( '[RoxyTable] Table import confirmed successfully:', json.data );
					console.groupEnd();
					showToast( json.data?.message || 'Table imported successfully!', 'success' );
					setTimeout( () => {
						window.location.href = json.data.redirect_url;
					}, 400 );
				} else {
					let errorDetail = 'Failed to confirm import.';

					if ( json && json.data && json.data.message ) {
						errorDetail = json.data.message;
					} else if ( res.status === 403 ) {
						errorDetail = ajaxData.i18n?.permission_error || 'Access Denied (403): You do not have permission to import tables, or your session has expired.';
					} else if ( res.status === 500 ) {
						errorDetail = 'Server Error (500): An error occurred while creating the table in the database.';
					} else if ( ! res.ok ) {
						errorDetail = `Server returned HTTP ${res.status}: ${res.statusText || 'Error'}.`;
					}

					console.error( '[RoxyTable] Import confirmation failed:', { status: res.status, errorDetail, rawText } );
					console.groupEnd();

					showToast( errorDetail.replace( /<[^>]+>/g, '' ), 'error' );

					if ( submitBtn ) {
						submitBtn.disabled = false;
						submitBtn.textContent = originalText;
						submitBtn.style.opacity = '1';
					}
				}
			} catch ( err ) {
				console.error( '[RoxyTable] Network/runtime error during confirm import:', err );
				console.groupEnd();

				const networkMsg = ( ! navigator.onLine )
					? 'Network Offline: Please check your internet connection and try again.'
					: `Network Error: ${err.message || 'Unable to connect to server.'}. Please try again.`;

				showToast( networkMsg, 'error' );

				if ( submitBtn ) {
					submitBtn.disabled = false;
					submitBtn.textContent = originalText;
					submitBtn.style.opacity = '1';
				}
			}
		} );
	}

	// 8. Table Image Thumbnail Picker (.rt-table-image-picker)
	document.addEventListener( 'click', ( e ) => {
		const picker = e.target.closest && e.target.closest( '.rt-table-image-picker' );
		if ( ! picker ) return;

		e.preventDefault();

		const tableId = picker.dataset.tableId;
		if ( ! tableId ) return;

		if ( typeof wp === 'undefined' || ! wp.media ) {
			showToast( 'WordPress Media Library is not available.', 'error' );
			return;
		}

		const frame = wp.media({
			title: 'Select Table Image Thumbnail',
			button: { text: 'Use as Table Image' },
			multiple: false
		});

		frame.on( 'select', async () => {
			const selection = frame.state().get('selection');
			const attachment = selection ? selection.first().toJSON() : null;

			if ( attachment && attachment.url ) {
				const imageUrl = attachment.url;

				// Dynamically update DOM image inside the row
				const row = picker.closest( 'tr' );
				if ( row ) {
					const thumbWrap = row.querySelector( '.rt-table-thumb-wrap' );
					if ( thumbWrap ) {
						let img = thumbWrap.querySelector( '.rt-table-thumb-img' );
						if ( ! img ) {
							const defaultSvg = thumbWrap.querySelector( '.rt-table-thumb-default' );
							if ( defaultSvg ) defaultSvg.remove();

							img = document.createElement( 'img' );
							img.className = 'rt-table-thumb-img';
							thumbWrap.insertBefore( img, thumbWrap.firstChild );
						}
						img.src = imageUrl;
					}
				}

				// AJAX update
				try {
					const formData = new FormData();
					formData.append( 'action', 'roxytable_save_table_image' );
					formData.append( 'nonce', ajaxData.nonces.save_table_image );
					formData.append( 'table_id', tableId );
					formData.append( 'image_url', imageUrl );
					if ( attachment && attachment.id ) {
						formData.append( 'attachment_id', attachment.id );
					}

					const res = await fetch( ajaxData.ajax_url, {
						method: 'POST',
						body: formData
					} );

					let json = null;
					try {
						const raw = await res.text();
						json = JSON.parse( raw );
					} catch ( parseErr ) {
						console.warn( '[RoxyTable] Non-JSON response saving table image:', parseErr );
					}

					if ( res.ok && json && json.success ) {
						showToast( json.data?.message || 'Table thumbnail image updated!', 'success' );
					} else {
						showToast( json?.data?.message || 'Failed to update table image.', 'error' );
					}
				} catch ( err ) {
					showToast( 'An error occurred while saving table image.', 'error' );
				}
			}
		});

		frame.open();
	} );
}



