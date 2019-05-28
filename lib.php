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

    if (!$file = $fs->get_file($context->id, 'local_pages', 'pagecontent', 0, $filepath, $filename) or $file->is_directory()) {
        send_file_not_found();
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
                $urllocation = new moodle_url($CFG->wwwroot . '/local/pages/', array('id' => $page->id));
                if (get_config('local_pages', 'cleanurl_enabled') && trim($page->menuname) != '') {
                    $urllocation = new moodle_url($CFG->wwwroot . '/local/pages/' . $page->menuname);
                }
                if (!$gnav->get('lpi' . $page->id)) {
                    $child = $nav->add(
                        $page->pagename,
                        $urllocation,
                        navigation_node::TYPE_CONTAINER,
                        null,
                        'lpi' . $page->id
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
 */
function local_pages_extend_navigation(global_navigation $nav) {
    global $CFG, $DB;
    $context = context_system::instance();
    $pluginname = get_string('pluginname', 'local_pages');
    if (has_capability('local/pages:addpages', $context)) {
        $mainnode = $nav->add(
            get_string('pagesplugin', 'local_pages'),
            new moodle_url($CFG->wwwroot . "/local/pages/pages.php"),
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