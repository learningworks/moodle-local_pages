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

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/local/pages/lib.php');

$download = optional_param('download', '', PARAM_ALPHA);
$pageid = optional_param('id', 0, PARAM_INT);
$context = context_system::instance();

global $USER, $PAGE;

// Set PAGE variables.
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/pages/edit.php', array("id" => $pageid));

// Force the user to login/create an account to access this page.
require_login();

if (!has_capability('local/pages:addpages', $context)) {
    require_capability('local/pages:addpages', $context);
}

// Add chosen Javascript to list.
$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/local/pages/js/pages.js'));

$PAGE->set_pagelayout('standard');

// Get the renderer for this page.
$renderer = $PAGE->get_renderer('local_pages');

$pagetoedit = \local_pages\custompage::load($pageid, true);
$renderer->save_page($pagetoedit);
// Print the page header.
$PAGE->set_title(get_string('pagesetup_title', 'local_pages'));
$PAGE->set_heading(get_string('pagesetup_heading', 'local_pages'));

$table = new \local_pages\formhistory('form-history');
$table->is_downloadable(true);
$table->is_downloading($download, 'form-report', 'Pages Form Report');

// Configure the table.
$table->define_baseurl(new moodle_url($CFG->wwwroot . '/local/pages/edit.php', array("id" => $pageid)));

$table->set_attribute('class', 'admintable generaltable history-table');
$table->collapsible(false);
$table->show_download_buttons_at(array(TABLE_P_BOTTOM));

$table->set_sql('*', "{local_pageslogging}", "formname = '$pageid'");

if (!$table->is_downloading()) {

    echo $OUTPUT->header();

    printf('<h1 class="page__title">%s<a style="float:right;font-size:15px" href="' .
        new moodle_url($CFG->wwwroot . '/local/pages/pages.php') . '"> '.
        get_string('backtolist', 'local_pages') .'</a></h1>',
        get_string('custompage_title', 'local_pages'));

    echo $renderer->edit_page($pagetoedit);
}
if (strtolower($pagetoedit->pagetype) == "form") {
    $table->out(25, true);
}

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}