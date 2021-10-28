<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * local pages
 *
 * @package     local_pages
 * @author      Kevin Dibble
 * @copyright   2017 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 *
 * Extend page navigation
 *
 * @param global_navigation $nav
 */
function local_pages_extends_navigation(global_navigation $nav) {
    return local_pages_extend_navigation($nav);
}

/**
 *
 * Get saved files for the page
 *
 * @param mixed $course
 * @param mixed $birecordorcm
 * @param mixed $context
 * @param mixed $filearea
 * @param mixed $args
 * @param bool $forcedownload
 * @param array $options
 */
function local_pages_pluginfile($course, $birecordorcm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    $fs = get_file_storage();

    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    if ($filearea === 'pagecontent') {
        if (!$file = $fs->get_file($context->id, 'local_pages', 'pagecontent', 0, $filepath, $filename) or $file->is_directory()) {
            send_file_not_found();
        }
    } else if ($filearea === 'ogimage') {
        $itemid = array_pop($args);
        $file = $fs->get_file($context->id, 'local_pages', $filearea, $itemid, '/', $filename);
        // Todo: Maybe put in fall back image.
    }

    \core\session\manager::write_close();
    send_stored_file($file, null, 0, $forcedownload, $options);
}

/**
 *
 * Build the menu for the page
 *
 * @param navigation_node $nav
 * @param mixed $parent
 * @param global_navigation $gnav
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function local_pages_build_menu(navigation_node $nav, $parent, global_navigation $gnav) {
    global $DB;
    $today = date('U');
    $records = $DB->get_records_sql("SELECT * FROM {local_pages} WHERE deleted=0 AND onmenu=1 " .
        "AND pagetype='page' AND pageparent=? AND pagedate <=? " .
        "ORDER BY pageorder", array($parent, $today));
    local_pages_process_records($records, $nav, false, $gnav);
}

/**
 *
 * Process records for pages
 *
 * @param mixed $records
 * @param mixed $nav
 * @param bool $parent
 * @param global_navigation $gnav
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function local_pages_process_records($records, $nav, $parent = false, global_navigation $gnav) {
    global $CFG;
    if ($records) {
        foreach ($records as $page) {
            $canaccess = true;
            if (isset($page->accesslevel) && stripos($page->accesslevel, ":") !== false) {
                $canaccess = false;
                $levels = explode(",", $page->accesslevel);
                $context = context_system::instance();
                foreach ($levels as $level) {
                    if ($canaccess != true) {
                        if (stripos($level, "!") !== false) {
                            $level = str_replace("!", "", $level);
                            $canaccess = has_capability(trim($level), $context) ? false : true;
                        } else {
                            $canaccess = has_capability(trim($level), $context) ? true : false;
                        }
                    }
                }
            }
            if ($canaccess) {
                $urllocation = new moodle_url('/local/pages/', array('id' => $page->id));
                if (get_config('local_pages', 'cleanurl_enabled') && trim($page->menuname) != '') {
                    $urllocation = new moodle_url('/local/pages/' . $page->menuname);
                }
                if (!$gnav->get('lpi' . $page->id)) {
                    $child = $nav->add(
                        $page->pagename,
                        $urllocation,
                        navigation_node::TYPE_CONTAINER,
                        null,
                        'lpi' . $page->id,
                        (!empty($page->menuicon)) ? new pix_icon($page->menuicon, '', 'local_pages') : null
                    );
                    $child->nodetype = 0;
                    $child->showinflatnavigation = true;
                    if ($parent) {
                        $parent->nodetype = 1;
                        $child->set_parent($parent);
                    }
                    local_pages_build_menu($child, $page->id, $gnav);
                }
            }
        }
    }
}

/**
 *
 * Extend navigation to show the pages in the navigation block
 *
 * @param global_navigation $nav
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function local_pages_extend_navigation(global_navigation $nav) {
    global $CFG, $DB;
    $context = context_system::instance();
    $pluginname = get_string('pluginname', 'local_pages');
    if (has_capability('local/pages:addpages', $context)) {
        $mainnode = $nav->add(
            get_string('pagesplugin', 'local_pages'),
            new moodle_url("/local/pages/pages.php"),
            navigation_node::TYPE_CONTAINER,
            'local_pages',
            'local_pages',
            new pix_icon('newspaper', $pluginname, 'local_pages')
        );
        $mainnode->nodetype = 0;
        $mainnode->showinflatnavigation = true;
    }
    $today = date('U');
    $records = $DB->get_records_sql("SELECT * FROM {local_pages} WHERE deleted=0 AND onmenu=1 " .
        "AND pagetype='page' AND pageparent=0 AND pagedate <= ? ORDER BY pageorder", array($today));

    local_pages_process_records($records, $nav, false, $nav);
}

/**
 *
 * Generate pix -> fontawesome icon mapping
 *
 * This is direct copy from moodle core mapping,
 * from https://github.com/moodle/moodle/blob/master/lib/classes/output/icon_system_fontawesome.php
 * Unfortunately, moodle core forbids fetching it directly, and forces every plugin to create its own map.
 * This is the only way I found, to display possible icons to user as selectable list
 *
 * @return array
 */
function local_pages_get_fontawesome_icon_map() {
    return [
        'local_pages:docs' => 'fa-info-circle',
        'local_pages:help' => 'fa-question-circle text-info',
        'local_pages:req' => 'fa-exclamation-circle text-danger',
        'local_pages:a/add_file' => 'fa-file-o',
        'local_pages:a/create_folder' => 'fa-folder-o',
        'local_pages:a/download_all' => 'fa-download',
        'local_pages:a/help' => 'fa-question-circle text-info',
        'local_pages:a/logout' => 'fa-sign-out',
        'local_pages:a/refresh' => 'fa-refresh',
        'local_pages:a/search' => 'fa-search',
        'local_pages:a/setting' => 'fa-cog',
        'local_pages:a/view_icon_active' => 'fa-th',
        'local_pages:a/view_list_active' => 'fa-list',
        'local_pages:a/view_tree_active' => 'fa-folder',
        'local_pages:b/bookmark-new' => 'fa-bookmark',
        'local_pages:b/document-edit' => 'fa-pencil',
        'local_pages:b/document-new' => 'fa-file-o',
        'local_pages:b/document-properties' => 'fa-info',
        'local_pages:b/edit-copy' => 'fa-files-o',
        'local_pages:b/edit-delete' => 'fa-trash',
        'local_pages:e/abbr' => 'fa-comment',
        'local_pages:e/absolute' => 'fa-crosshairs',
        'local_pages:e/accessibility_checker' => 'fa-universal-access',
        'local_pages:e/acronym' => 'fa-comment',
        'local_pages:e/advance_hr' => 'fa-arrows-h',
        'local_pages:e/align_center' => 'fa-align-center',
        'local_pages:e/align_left' => 'fa-align-left',
        'local_pages:e/align_right' => 'fa-align-right',
        'local_pages:e/anchor' => 'fa-chain',
        'local_pages:e/backward' => 'fa-undo',
        'local_pages:e/bold' => 'fa-bold',
        'local_pages:e/bullet_list' => 'fa-list-ul',
        'local_pages:e/cancel' => 'fa-times',
        'local_pages:e/cell_props' => 'fa-info',
        'local_pages:e/cite' => 'fa-quote-right',
        'local_pages:e/cleanup_messy_code' => 'fa-eraser',
        'local_pages:e/clear_formatting' => 'fa-i-cursor',
        'local_pages:e/copy' => 'fa-clone',
        'local_pages:e/cut' => 'fa-scissors',
        'local_pages:e/decrease_indent' => 'fa-outdent',
        'local_pages:e/delete_col' => 'fa-minus',
        'local_pages:e/delete_row' => 'fa-minus',
        'local_pages:e/delete' => 'fa-minus',
        'local_pages:e/delete_table' => 'fa-minus',
        'local_pages:e/document_properties' => 'fa-info',
        'local_pages:e/emoticons' => 'fa-smile-o',
        'local_pages:e/find_replace' => 'fa-search-plus',
        'local_pages:e/file-text' => 'fa-file-text',
        'local_pages:e/forward' => 'fa-arrow-right',
        'local_pages:e/fullpage' => 'fa-arrows-alt',
        'local_pages:e/fullscreen' => 'fa-arrows-alt',
        'local_pages:e/help' => 'fa-question-circle',
        'local_pages:e/increase_indent' => 'fa-indent',
        'local_pages:e/insert_col_after' => 'fa-columns',
        'local_pages:e/insert_col_before' => 'fa-columns',
        'local_pages:e/insert_date' => 'fa-calendar',
        'local_pages:e/insert_edit_image' => 'fa-picture-o',
        'local_pages:e/insert_edit_link' => 'fa-link',
        'local_pages:e/insert_edit_video' => 'fa-file-video-o',
        'local_pages:e/insert_file' => 'fa-file',
        'local_pages:e/insert_horizontal_ruler' => 'fa-arrows-h',
        'local_pages:e/insert_nonbreaking_space' => 'fa-square-o',
        'local_pages:e/insert_page_break' => 'fa-level-down',
        'local_pages:e/insert_row_after' => 'fa-plus',
        'local_pages:e/insert_row_before' => 'fa-plus',
        'local_pages:e/insert' => 'fa-plus',
        'local_pages:e/insert_time' => 'fa-clock-o',
        'local_pages:e/italic' => 'fa-italic',
        'local_pages:e/justify' => 'fa-align-justify',
        'local_pages:e/layers_over' => 'fa-level-up',
        'local_pages:e/layers' => 'fa-window-restore',
        'local_pages:e/layers_under' => 'fa-level-down',
        'local_pages:e/left_to_right' => 'fa-chevron-right',
        'local_pages:e/manage_files' => 'fa-files-o',
        'local_pages:e/math' => 'fa-calculator',
        'local_pages:e/merge_cells' => 'fa-compress',
        'local_pages:e/new_document' => 'fa-file-o',
        'local_pages:e/numbered_list' => 'fa-list-ol',
        'local_pages:e/page_break' => 'fa-level-down',
        'local_pages:e/paste' => 'fa-clipboard',
        'local_pages:e/paste_text' => 'fa-clipboard',
        'local_pages:e/paste_word' => 'fa-clipboard',
        'local_pages:e/prevent_autolink' => 'fa-exclamation',
        'local_pages:e/preview' => 'fa-search-plus',
        'local_pages:e/print' => 'fa-print',
        'local_pages:e/question' => 'fa-question',
        'local_pages:e/redo' => 'fa-repeat',
        'local_pages:e/remove_link' => 'fa-chain-broken',
        'local_pages:e/remove_page_break' => 'fa-remove',
        'local_pages:e/resize' => 'fa-expand',
        'local_pages:e/restore_draft' => 'fa-undo',
        'local_pages:e/restore_last_draft' => 'fa-undo',
        'local_pages:e/right_to_left' => 'fa-chevron-left',
        'local_pages:e/row_props' => 'fa-info',
        'local_pages:e/save' => 'fa-floppy-o',
        'local_pages:e/screenreader_helper' => 'fa-braille',
        'local_pages:e/search' => 'fa-search',
        'local_pages:e/select_all' => 'fa-arrows-h',
        'local_pages:e/show_invisible_characters' => 'fa-eye-slash',
        'local_pages:e/source_code' => 'fa-code',
        'local_pages:e/special_character' => 'fa-pencil-square-o',
        'local_pages:e/spellcheck' => 'fa-check',
        'local_pages:e/split_cells' => 'fa-columns',
        'local_pages:e/strikethrough' => 'fa-strikethrough',
        'local_pages:e/styleparagraph' => 'fa-font',
        'local_pages:e/subscript' => 'fa-subscript',
        'local_pages:e/superscript' => 'fa-superscript',
        'local_pages:e/table_props' => 'fa-table',
        'local_pages:e/table' => 'fa-table',
        'local_pages:e/template' => 'fa-sticky-note',
        'local_pages:e/text_color_picker' => 'fa-paint-brush',
        'local_pages:e/text_color' => 'fa-paint-brush',
        'local_pages:e/text_highlight_picker' => 'fa-lightbulb-o',
        'local_pages:e/text_highlight' => 'fa-lightbulb-o',
        'local_pages:e/tick' => 'fa-check',
        'local_pages:e/toggle_blockquote' => 'fa-quote-left',
        'local_pages:e/underline' => 'fa-underline',
        'local_pages:e/undo' => 'fa-undo',
        'local_pages:e/visual_aid' => 'fa-universal-access',
        'local_pages:e/visual_blocks' => 'fa-audio-description',
        'local_pages:i/addblock' => 'fa-plus-square',
        'local_pages:i/assignroles' => 'fa-user-plus',
        'local_pages:i/backup' => 'fa-file-zip-o',
        'local_pages:i/badge' => 'fa-shield',
        'local_pages:i/calc' => 'fa-calculator',
        'local_pages:i/calendar' => 'fa-calendar',
        'local_pages:i/calendareventdescription' => 'fa-align-left',
        'local_pages:i/calendareventtime' => 'fa-clock-o',
        'local_pages:i/caution' => 'fa-exclamation text-warning',
        'local_pages:i/checked' => 'fa-check',
        'local_pages:i/checkedcircle' => 'fa-check-circle',
        'local_pages:i/checkpermissions' => 'fa-unlock-alt',
        'local_pages:i/cohort' => 'fa-users',
        'local_pages:i/competencies' => 'fa-check-square-o',
        'local_pages:i/completion_self' => 'fa-user-o',
        'local_pages:i/dashboard' => 'fa-tachometer',
        'local_pages:i/lock' => 'fa-lock',
        'local_pages:i/categoryevent' => 'fa-cubes',
        'local_pages:i/course' => 'fa-graduation-cap',
        'local_pages:i/courseevent' => 'fa-graduation-cap',
        'local_pages:i/customfield' => 'fa-hand-o-right',
        'local_pages:i/db' => 'fa-database',
        'local_pages:i/delete' => 'fa-trash',
        'local_pages:i/down' => 'fa-arrow-down',
        'local_pages:i/dragdrop' => 'fa-arrows',
        'local_pages:i/duration' => 'fa-clock-o',
        'local_pages:i/edit' => 'fa-pencil',
        'local_pages:i/email' => 'fa-envelope',
        'local_pages:i/empty' => 'fa-fw',
        'local_pages:i/enrolmentsuspended' => 'fa-pause',
        'local_pages:i/enrolusers' => 'fa-user-plus',
        'local_pages:i/expired' => 'fa-exclamation text-warning',
        'local_pages:i/export' => 'fa-download',
        'local_pages:i/files' => 'fa-file',
        'local_pages:i/filter' => 'fa-filter',
        'local_pages:i/flagged' => 'fa-flag',
        'local_pages:i/folder' => 'fa-folder',
        'local_pages:i/grade_correct' => 'fa-check text-success',
        'local_pages:i/grade_incorrect' => 'fa-remove text-danger',
        'local_pages:i/grade_partiallycorrect' => 'fa-check-square',
        'local_pages:i/grades' => 'fa-table',
        'local_pages:i/groupevent' => 'fa-group',
        'local_pages:i/groupn' => 'fa-user',
        'local_pages:i/group' => 'fa-users',
        'local_pages:i/groups' => 'fa-user-circle',
        'local_pages:i/groupv' => 'fa-user-circle-o',
        'local_pages:i/home' => 'fa-home',
        'local_pages:i/hide' => 'fa-eye',
        'local_pages:i/hierarchylock' => 'fa-lock',
        'local_pages:i/import' => 'fa-level-up',
        'local_pages:i/incorrect' => 'fa-exclamation',
        'local_pages:i/info' => 'fa-info',
        'local_pages:i/invalid' => 'fa-times text-danger',
        'local_pages:i/item' => 'fa-circle',
        'local_pages:i/loading' => 'fa-circle-o-notch fa-spin',
        'local_pages:i/loading_small' => 'fa-circle-o-notch fa-spin',
        'local_pages:i/location' => 'fa-map-marker',
        'local_pages:i/lock' => 'fa-lock',
        'local_pages:i/log' => 'fa-list-alt',
        'local_pages:i/mahara_host' => 'fa-id-badge',
        'local_pages:i/manual_item' => 'fa-square-o',
        'local_pages:i/marked' => 'fa-circle',
        'local_pages:i/marker' => 'fa-circle-o',
        'local_pages:i/mean' => 'fa-calculator',
        'local_pages:i/menu' => 'fa-ellipsis-v',
        'local_pages:i/menubars' => 'fa-bars',
        'local_pages:i/messagecontentaudio' => 'fa-headphones',
        'local_pages:i/messagecontentimage' => 'fa-image',
        'local_pages:i/messagecontentvideo' => 'fa-film',
        'local_pages:i/messagecontentmultimediageneral' => 'fa-file-video-o',
        'local_pages:i/mnethost' => 'fa-external-link',
        'local_pages:i/moodle_host' => 'fa-graduation-cap',
        'local_pages:i/moremenu' => 'fa-ellipsis-h',
        'local_pages:i/move_2d' => 'fa-arrows',
        'local_pages:i/muted' => 'fa-microphone-slash',
        'local_pages:i/navigationitem' => 'fa-fw',
        'local_pages:i/ne_red_mark' => 'fa-remove',
        'local_pages:i/new' => 'fa-bolt',
        'local_pages:i/news' => 'fa-newspaper-o',
        'local_pages:i/next' => 'fa-chevron-right',
        'local_pages:i/nosubcat' => 'fa-plus-square-o',
        'local_pages:i/notifications' => 'fa-bell',
        'local_pages:i/open' => 'fa-folder-open',
        'local_pages:i/outcomes' => 'fa-tasks',
        'local_pages:i/payment' => 'fa-money',
        'local_pages:i/permissionlock' => 'fa-lock',
        'local_pages:i/permissions' => 'fa-pencil-square-o',
        'local_pages:i/persona_sign_in_black' => 'fa-male',
        'local_pages:i/portfolio' => 'fa-id-badge',
        'local_pages:i/preview' => 'fa-search-plus',
        'local_pages:i/previous' => 'fa-chevron-left',
        'local_pages:i/privatefiles' => 'fa-file-o',
        'local_pages:i/progressbar' => 'fa-spinner fa-spin',
        'local_pages:i/publish' => 'fa-share',
        'local_pages:i/questions' => 'fa-question',
        'local_pages:i/reload' => 'fa-refresh',
        'local_pages:i/report' => 'fa-area-chart',
        'local_pages:i/repository' => 'fa-hdd-o',
        'local_pages:i/restore' => 'fa-level-up',
        'local_pages:i/return' => 'fa-arrow-left',
        'local_pages:i/risk_config' => 'fa-exclamation text-muted',
        'local_pages:i/risk_managetrust' => 'fa-exclamation-triangle text-warning',
        'local_pages:i/risk_personal' => 'fa-exclamation-circle text-info',
        'local_pages:i/risk_spam' => 'fa-exclamation text-primary',
        'local_pages:i/risk_xss' => 'fa-exclamation-triangle text-danger',
        'local_pages:i/role' => 'fa-user-md',
        'local_pages:i/rss' => 'fa-rss',
        'local_pages:i/rsssitelogo' => 'fa-graduation-cap',
        'local_pages:i/scales' => 'fa-balance-scale',
        'local_pages:i/scheduled' => 'fa-calendar-check-o',
        'local_pages:i/search' => 'fa-search',
        'local_pages:i/section' => 'fa-folder-o',
        'local_pages:i/sendmessage' => 'fa-paper-plane',
        'local_pages:i/settings' => 'fa-cog',
        'local_pages:i/show' => 'fa-eye-slash',
        'local_pages:i/siteevent' => 'fa-globe',
        'local_pages:i/star' => 'fa-star',
        'local_pages:i/star-rating' => 'fa-star',
        'local_pages:i/stats' => 'fa-line-chart',
        'local_pages:i/switch' => 'fa-exchange',
        'local_pages:i/switchrole' => 'fa-user-secret',
        'local_pages:i/trash' => 'fa-trash',
        'local_pages:i/twoway' => 'fa-arrows-h',
        'local_pages:i/unchecked' => 'fa-square-o',
        'local_pages:i/uncheckedcircle' => 'fa-circle-o',
        'local_pages:i/unflagged' => 'fa-flag-o',
        'local_pages:i/unlock' => 'fa-unlock',
        'local_pages:i/up' => 'fa-arrow-up',
        'local_pages:i/userevent' => 'fa-user',
        'local_pages:i/user' => 'fa-user',
        'local_pages:i/users' => 'fa-users',
        'local_pages:i/valid' => 'fa-check text-success',
        'local_pages:i/warning' => 'fa-exclamation text-warning',
        'local_pages:i/window_close' => 'fa-window-close',
        'local_pages:i/withsubcat' => 'fa-plus-square',
        'local_pages:m/USD' => 'fa-usd',
        'local_pages:t/addcontact' => 'fa-address-card',
        'local_pages:t/add' => 'fa-plus',
        'local_pages:t/approve' => 'fa-thumbs-up',
        'local_pages:t/assignroles' => 'fa-user-circle',
        'local_pages:t/award' => 'fa-trophy',
        'local_pages:t/backpack' => 'fa-shopping-bag',
        'local_pages:t/backup' => 'fa-arrow-circle-down',
        'local_pages:t/block' => 'fa-ban',
        'local_pages:t/block_to_dock_rtl' => 'fa-chevron-right',
        'local_pages:t/block_to_dock' => 'fa-chevron-left',
        'local_pages:t/calc_off' => 'fa-calculator', // TODO: Change to better icon once we have stacked icon support or more icons.
        'local_pages:t/calc' => 'fa-calculator',
        'local_pages:t/check' => 'fa-check',
        'local_pages:t/cohort' => 'fa-users',
        'local_pages:t/collapsed_empty_rtl' => 'fa-caret-square-o-left',
        'local_pages:t/collapsed_empty' => 'fa-caret-square-o-right',
        'local_pages:t/collapsed_rtl' => 'fa-caret-left',
        'local_pages:t/collapsed' => 'fa-caret-right',
        'local_pages:t/collapsedcaret' => 'fa-caret-right',
        'local_pages:t/contextmenu' => 'fa-cog',
        'local_pages:t/copy' => 'fa-copy',
        'local_pages:t/delete' => 'fa-trash',
        'local_pages:t/dockclose' => 'fa-window-close',
        'local_pages:t/dock_to_block_rtl' => 'fa-chevron-right',
        'local_pages:t/dock_to_block' => 'fa-chevron-left',
        'local_pages:t/download' => 'fa-download',
        'local_pages:t/down' => 'fa-arrow-down',
        'local_pages:t/downlong' => 'fa-long-arrow-down',
        'local_pages:t/dropdown' => 'fa-cog',
        'local_pages:t/editinline' => 'fa-pencil',
        'local_pages:t/edit_menu' => 'fa-cog',
        'local_pages:t/editstring' => 'fa-pencil',
        'local_pages:t/edit' => 'fa-cog',
        'local_pages:t/emailno' => 'fa-ban',
        'local_pages:t/email' => 'fa-envelope-o',
        'local_pages:t/emptystar' => 'fa-star-o',
        'local_pages:t/enrolusers' => 'fa-user-plus',
        'local_pages:t/expanded' => 'fa-caret-down',
        'local_pages:t/go' => 'fa-play',
        'local_pages:t/grades' => 'fa-table',
        'local_pages:t/groupn' => 'fa-user',
        'local_pages:t/groups' => 'fa-user-circle',
        'local_pages:t/groupv' => 'fa-user-circle-o',
        'local_pages:t/hide' => 'fa-eye',
        'local_pages:t/left' => 'fa-arrow-left',
        'local_pages:t/less' => 'fa-caret-up',
        'local_pages:t/locked' => 'fa-lock',
        'local_pages:t/lock' => 'fa-unlock',
        'local_pages:t/locktime' => 'fa-lock',
        'local_pages:t/markasread' => 'fa-check',
        'local_pages:t/messages' => 'fa-comments',
        'local_pages:t/message' => 'fa-comment',
        'local_pages:t/more' => 'fa-caret-down',
        'local_pages:t/move' => 'fa-arrows-v',
        'local_pages:t/online' => 'fa-circle',
        'local_pages:t/passwordunmask-edit' => 'fa-pencil',
        'local_pages:t/passwordunmask-reveal' => 'fa-eye',
        'local_pages:t/portfolioadd' => 'fa-plus',
        'local_pages:t/preferences' => 'fa-wrench',
        'local_pages:t/preview' => 'fa-search-plus',
        'local_pages:t/print' => 'fa-print',
        'local_pages:t/removecontact' => 'fa-user-times',
        'local_pages:t/reload' => 'fa-refresh',
        'local_pages:t/reset' => 'fa-repeat',
        'local_pages:t/restore' => 'fa-arrow-circle-up',
        'local_pages:t/right' => 'fa-arrow-right',
        'local_pages:t/sendmessage' => 'fa-paper-plane',
        'local_pages:t/show' => 'fa-eye-slash',
        'local_pages:t/sort_by' => 'fa-sort-amount-asc',
        'local_pages:t/sort_asc' => 'fa-sort-asc',
        'local_pages:t/sort_desc' => 'fa-sort-desc',
        'local_pages:t/sort' => 'fa-sort',
        'local_pages:t/stop' => 'fa-stop',
        'local_pages:t/switch_minus' => 'fa-minus',
        'local_pages:t/switch_plus' => 'fa-plus',
        'local_pages:t/switch_whole' => 'fa-square-o',
        'local_pages:t/tags' => 'fa-tags',
        'local_pages:t/unblock' => 'fa-commenting',
        'local_pages:t/unlocked' => 'fa-unlock-alt',
        'local_pages:t/unlock' => 'fa-lock',
        'local_pages:t/up' => 'fa-arrow-up',
        'local_pages:t/uplong' => 'fa-long-arrow-up',
        'local_pages:t/user' => 'fa-user',
        'local_pages:t/viewdetails' => 'fa-list',
    ];
}

function local_pages_before_standard_html_head() {
    global $CFG, $DB, $PAGE, $SITE;

    if ($PAGE->pagetype !== 'local-pages-index') {
        return;
    }

    $pageid = optional_param('id', 0, PARAM_INT);
    $custompage     = \local_pages\custompage::load($pageid);
    $output = get_config('local_pages', 'additionalhead') ? $custompage->meta : '';

    $query = "SELECT * FROM {files}
              WHERE component = 'local_pages'
              AND filearea = 'ogimage'
              AND itemid = ?
              AND filesize > 0";
    if ($filerecord = $DB->get_record_sql($query, [$custompage->id])) {
        $src = $CFG->wwwroot . '/pluginfile.php/1/local_pages/ogimage/' . $custompage->id . '/' . $filerecord->filename;
        $output .= "\n" . '    <meta property="og:image" content="' . $src . '" />';
    }

    $url = new moodle_url($PAGE->url);
    $url->remove_all_params();

    if(get_config('local_pages', 'cleanurl_enabled') && $pageid === 0){
        $url = str_replace('index.php', '', $url->out());
        $url .= $custompage->menuname;
    } else {
        $url = $url->out() . '?id='. $custompage->id;
    }

    $output .= "\n" . '    <meta property="og:site_name" content="' . $SITE->fullname . '" />';
    $output .= "\n" . '    <meta property="og:type" content="website" />';
    $output .= "\n" . '    <meta property="og:title" content="' . $PAGE->title . '" />';
    $output .= "\n" . '    <meta property="og:url" content="' . $url . '" />';

    return $output;

}
