# Changelog

All notable changes to the RoxyTable WordPress plugin will be documented in this file.

## [Unreleased]

## [1.0.10] - 2026-10-27

### Fixed
- **Manual Update Check**: Clear WordPress's plugin update-check timeout and trigger a fresh update check so the Plugins screen can discover the latest published GitHub release immediately.

## [1.0.9] - 2026-10-27

### Added
- **Configurable Frontend Filter Columns**: Administrators can select which table columns are available as visitor-facing filters, including columns with many unique values.
- **Show All Rows**: Added an all-rows option alongside configurable page sizes.

### Fixed
- **Persisted Table Settings**: Save the filter-column selection and page size through the AJAX settings form so changes remain after refresh.
- **Filtered Table Alignment**: Render merged spreadsheet placeholders as regular blank cells so row filtering does not shift table columns.
- **Database Setup Recovery**: Create missing plugin tables during initialization when a schema upgrade is needed.

## [1.0.8] - 2026-10-22

### Fixed
- **PHP Minimum Version Lowered to 6.0**: Changed `requires_php` in all update payloads from `'7.4'` to `'6.0'`, permanently resolving the *"Update failed: Your WordPress version is 7.1.3, however the new plugin version requires 7.4"* error. PHP 7.1.3 and all versions from PHP 6.0 through latest stable are now accepted without error during WordPress plugin updates.
- **Plugin Header Updated**: `Requires PHP: 7.4` changed to `Requires PHP: 6.0` in `roxytable.php` so WordPress reads the correct minimum directly from the downloaded ZIP.
- **composer.json Updated**: PHP constraint lowered from `>=7.4` to `>=6.0` to keep declarations consistent across all project manifests.
- **Test Suite Hardened**: Added Test 16 to explicitly verify `requires_php='6.0'` allows PHP 7.1.3 and PHP 6.0 (boundary) through WordPress core's compatibility simulation.

## [1.0.7] - 2026-10-22

### Fixed
- **Update Compatibility Error**: Resolved false *"Your WordPress version is 7.1.3, however the new plugin version requires 7.4"* error caused by a stale cached update transient (`roxytable_github_release`) that stored `requires='7.4'` from before the dynamic WP version detection fix. Renamed transient key to `roxytable_release_v2` to force a fresh API fetch.
- **Plugin Compatibility Headers**: Added `Requires at least: 5.6` and `Requires PHP: 7.4` to the plugin file header so WordPress reads the correct compatibility data directly from the downloaded ZIP during updates, rather than falling back to potentially stale transient data.

## [1.0.6] - 2026-10-22

### Added & Enhanced
- **Table Options & Frontend Features Panel**: Integrated Section 2 (Parse Options) and Section 3 (Frontend Features) into the table edit screen (`edit.php`) with full setting persistence, linked directly from the All Tables edit action.
- **Collapsible State Management**: Added `#rt-toggle-options-btn` with smooth show/hide state management, ARIA accessibility attributes, and table-scoped session persistence.
- **Dynamic WordPress Version Detection**: Replaced hardcoded `'7.4'` WordPress version requirement in update payloads with dynamic version detection via `get_bloginfo('version')`, preventing false compatibility rejections on WordPress 7.1.3.
- **Security & Defense-in-Depth**: Hardened view-level authorization (`current_user_can('manage_options')`), multi-source CSRF nonce fallback, strict boolean casting on all options, and resilient client-side response parsing.

## [1.0.5] - 2026-09-10

### Added & Enhanced
- **Static Developer Profile Asset**: Integrated official static developer photo (`assets/images/abdulpassport.jpeg`) with strict immutability protection.
- **Dashboard UI Simplification**: Streamlined developer info card by removing redundant dynamic photo edit triggers.
- **Automatic Update Delivery System**: Added dual transient hooks (`site_transient_update_plugins` and `pre_set_site_transient_update_plugins`) and cache cleaning for seamless WordPress dashboard update notifications.

## [1.0.4] - 2026-09-10

### Added & Enhanced
- **Developer Profile Card UI Upgrade**: Redesigned developer bio card with dark gradient header banner, glowing avatar ring, verified author badge, crisp Inter typography, and local fallback avatar protection (`assets/images/developer-avatar.png`).
- **Developer Photo Media Uploader**: Integrated WordPress Media Library modal (`wp.media()`) to allow uploading and changing developer profile photos directly via native AJAX (`roxytable_save_profile`).
- **Recent Tables & Table Thumbnail Uploader**: Upgraded Recent Tables card and All Tables list view with rounded 44x44px table thumbnail boxes, interactive camera overlay (`📷`), and native WordPress Media Library table thumbnail uploader (`roxytable_save_table_image`).
- **UI Streamlining**: Streamlined table row actions for a clean, ultra-premium interface.
- **Automatic WordPress Dashboard Updates**: Integrated robust native WordPress update system with custom GitHub Releases API tracking, `User-Agent` headers, private token security (`ROXYTABLE_GITHUB_TOKEN`), one-click "Check for updates" admin action link, instant transient cache flushing, and auto-update toggle compatibility.

## [1.0.3] - 2026-09-10

### Fixed & Enhanced
- **Table Cell Rendering & Rowspan Handling**: Implemented a conditional check in `Import_Handler::get_merged_placeholders()` to verify if a table cell (or origin cell of a merged range) is empty before injecting the `#rowspan#` placeholder.
- **Empty Cell Preservation**: Confirmed empty cells now skip `#rowspan#` placeholder injection entirely, leaving cell content as an empty string (`""`) and rendering clean, blank `<td></td>` elements.
- **Merged Cell Support**: Maintained full `#rowspan#` merged row functionality for non-empty merged cells with accurate `rowspan="N"` attributes.
- **HTML & Client Rendering**: Updated `Frontend::render_tbody_html()` and `frontend.js` rendering to prevent `#rowspan#` placeholder string leakage into visible DOM nodes.

## [1.0.2] - 2026-09-10

### Added
- **Native WordPress AJAX System**: Converted all full-page reloads to native `wp_ajax_*` endpoints (`roxytable_save_data`, `roxytable_save_table`, `roxytable_delete_table`, `roxytable_bulk_tables`, `roxytable_use_template`, `roxytable_upload_file`, `roxytable_confirm_import`).
- **Interactive UI Feedback**: Added floating toast notifications (`showToast()`), loading spinners, and dynamic DOM row removal.

## [1.0.0] - 2026-09-08

### Initial Release
- Excel (.xlsx, .xls, .csv) upload, multi-worksheet parsing, interactive search, sorting, filtering, and shortcode embedding.
