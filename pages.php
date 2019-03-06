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
 * Local Pages Edit Page
 *
 * @package     local_pages
 * @author      Kevin Dibble
 * @copyright   2017 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/local/pages/lib.php');

$deletepage = optional_param('pagedel', 0, PARAM_INT);
$context = context_system::instance();

global $USER, $PAGE;

// Set PAGE variables.
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/pages/pages.php');

// Force the user to login/create an account to access this page.
require_login();

require_capability('local/pages:addpages', $context);

if ($deletepage !== 0) {
    if (confirm_sesskey()) {
        global $DB;
        $options = new stdClass();
        $options->id = $deletepage;
        $options->deleted = 1;
        $DB->update_record('local_pages', $options);

        // Get pages under this page and update their parent.
        $DB->set_field('local_pages', 'pageparent', 0, ['pageparent' => $deletepage]);
    }
}

$PAGE->set_pagelayout('standard');

// Get the renderer for this page.
$renderer = $PAGE->get_renderer('local_pages');

// Only print headers if not asked to download data.
// Print the page header.
$PAGE->set_title(get_string('pagesetup_title', 'local_pages'));
$PAGE->set_heading(get_string('pagesetup_heading', 'local_pages'));
$PAGE->requires->jquery();

// Set the admin navigation tree to Plugins > Local Plugins > Pages > Manage Pages for users that have site config.
if (has_capability('moodle/site:config', $context)) {
    require_once($CFG->libdir . '/adminlib.php');
    admin_externalpage_setup('Manage Pages');
}

echo $OUTPUT->header();

printf('<h1 class="page__title">%s</h1>', get_string('custompage_title', 'local_pages'));

echo $renderer->list_pages();

echo $OUTPUT->footer();