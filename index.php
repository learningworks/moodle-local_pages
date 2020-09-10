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
 * Pages main view page.
 *
 * @package         local_pages
 * @author          Kevin Dibble <kevin.dibble@learningworks.co.nz>.
 * @copyright       2017 LearningWorks Ltd.
 * @license         http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

// Get the id of the page to be displayed.
$pageid = optional_param('id', 0, PARAM_INT);

// Setup the page.
$PAGE->set_context(\context_system::instance());
$PAGE->set_url("{$CFG->wwwroot}/local/pages/index.php", ['id' => $pageid]);

require_once("{$CFG->dirroot}/local/pages/lib.php");

// Set the page layout.
$custompage     = \local_pages\custompage::load($pageid);

// Check if the page has an access level requirement.
$accesslevel    = $custompage->accesslevel;
if ($accesslevel != '') {
    require_login();
}

$templatename   = trim($custompage->pagelayout) != '' ? $custompage->pagelayout : 'standard';
$PAGE->set_pagelayout($templatename);

// Now, get the page renderer.
$renderer = $PAGE->get_renderer('local_pages');

// More page setup.
$PAGE->set_title($custompage->pagename);
$PAGE->set_heading($custompage->pagename);

// Add a class to the body that identifies this page.
if ($pageid) {
    if ($pagedata = $DB->get_record('local_pages', ['id' => $pageid])) {
        // Make the page name lowercase.
        $pagedata->pagename = \core_text::strtolower($pagedata->pagename);

        // Now join the page name if we need to.
        $pagedata->pagename = implode('-', explode(' ', $pagedata->pagename));

        // Generate the class name with the following naming convention {pagetype}-local-pages-{pagename}-{pageid}.
        $classname = "{$pagedata->pagetype}-local-pages-{$pagedata->pagename}-{$pageid}";

        // Now add that class name to the body of this page :).
        $PAGE->add_body_class($classname);
    }
}

// Output the header.
echo $OUTPUT->header();

// Output the page content.
echo $renderer->showpage($custompage);

// Now output the footer.
echo $OUTPUT->footer();