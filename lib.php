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
 * @package     local
 * @subpackage  local_pages
 * @author      Kevin Dibble
 * @copyright   2016 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

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

function local_pages_extends_navigation(global_navigation $nav) {
    return local_pages_extend_navigation($nav);
}

function local_pages_build_menu(navigation_node $nav, $parent) {
    global $CFG, $DB;
    $records = $DB->get_records_sql("SELECT * FROM {local_pages} WHERE deleted=0 AND onmenu=1 " .
        "AND pagetype='page' AND pageparent=? AND pagedate <= UNIX_TIMESTAMP(CURDATE()) " .
        "ORDER BY pageorder", array($parent));
    if ($records) {
        foreach ($records as $page) {
            $canaccess = true;
            if (isset($page->accesslevel) && stripos($page->accesslevel, ":") !== false) {
                $canaccess = false;
                $levels = explode(",", $page->accesslevel);
                foreach ($levels as $key => $level) {
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
                $child = $nav->add(
                    $page->pagename,
                    $urllocation,
                    navigation_node::TYPE_CONTAINER
                );
                $child->set_parent($nav);
                local_pages_build_menu($child, $page->id);
            }
        }
    }
}

function local_pages_extend_navigation(global_navigation $nav) {
    global $CFG, $DB;
    if (!is_siteadmin()) {
        $context = context_system::instance();
        if (has_capability('local/pages:addpages', $context)) {
            $nav->add(
                get_string('pluginname', 'local_pages'),
                new moodle_url($CFG->wwwroot . "/local/pages/pages/pages.php"),
                navigation_node::TYPE_CONTAINER
            );
        }
    }
    $records = $DB->get_records_sql("SELECT * FROM {local_pages} WHERE deleted=0 AND onmenu=1 " .
        "AND pagetype='page' AND pageparent=0 AND pagedate <= UNIX_TIMESTAMP(CURDATE()) ORDER BY pageorder");

    foreach ($records as $page) {
        $canaccess = true;
        if (isset($page->accesslevel) && stripos($page->accesslevel, ":") !== false) {
            $canaccess = false;
            $levels = explode(",", $page->accesslevel);
            foreach ($levels as $key => $level) {
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
            $parent = $nav->add(
                $page->pagename,
                $urllocation,
                navigation_node::TYPE_CONTAINER
            );
            local_pages_build_menu($parent, $page->id);
        }
    }
}