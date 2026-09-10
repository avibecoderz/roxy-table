# Changelog

All notable changes to the RoxyTable WordPress plugin will be documented in this file.

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
