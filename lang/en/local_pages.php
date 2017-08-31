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
 * Tag Manager
 *
 * This module provides google tag manager to moodle sites
 *
 * @package    local_tagmanager
 * @copyright  2016 Kevin Dibble, www.learningworks.co.nz
 * @author     Kevin Dibble
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Settings page strings.
$string['pluginname'] = 'Pages';
$string['pluginsettings'] = 'Settings';
$string['pluginsettings_managepages'] = 'Manage pages';

// Other plugin strings.
$string['pages_settings'] = 'Pages Settings';
$string['custompage_title'] = 'Manage Pages';
$string['pagesetup_title'] = 'Page Setup';
$string['pagesetup_heading'] = 'Page heading';
$string['page_content_description'] = 'Add content for the page';
$string['page_content'] = 'Page Content';
$string['page_name'] = 'Page Name';
$string['page_order'] = 'Page Order';
$string['page_date'] = 'Page Date';
$string['page_parent'] = 'Page Parent';
$string['menu_name'] = 'Page URL';
$string['page_onmenu'] = 'Display on menu';
$string['cleanurl_enabled'] = 'Enable Smart URLS';
$string['cleanurl_enabled_description'] = 'Enable Links to use a clean URL';
$string['pages_settings'] = 'Pages Settings';
$string['page_pagetype'] = 'Page Type';
$string['emailto_name'] = 'Form Email address';
$string['pagelayout_name'] = 'Page Template';
$string['form_field_id'] = "ID";
$string['form_field_date'] = "Date";
$string['form_field_content'] = "Form Details";
$string['page_accesslevel'] = "Capability required";
$string['noaccess'] = 'You do not have rights to view this page';
$string['pagecontent_description_help'] = "Use #form# to add a form to the main page (Must choose a page type of 'page'). <br/>If the page type is a form, This area is the thank you message to display. You can use {form field name} to include form values in the thankyou message";
$string['pagedate_description_help'] = 'Select the date to publish this page - a future date will stop this page being accessed until that date';
$string['accesslevel_description_help'] = 'Enter in the capability string, you can comma seperate to add multiple capabilites<br/>If you want everyone BUT that capability to view - put an ! mark before it<br/>Example: mod/folder:managefiles,!mod/quiz:grade';
$string['accesslevel_description'] = "Access Level";
$string['pagedate_description'] = "Page Publish Date";
$string['pagecontent_description'] = "Page Content";
$string['custom_email_description'] = 'Choose to use Moodle Email or php Mail';
$string['custom_email'] = 'Use PHP Mail';
$string['email_headers_description'] = 'Enter the Email headers to send - use {html} to send html messages. use {From} to set a from address and use {Reply-to} to enable a reply to header';
$string['email_headers'] = 'Custom headers for PHP mail';
$string['user_copy'] = "Copy message to person";
$string['user_copy_description'] = "Select if the person filling in the form is to receive a message";
$string['message_copy'] = "Message to go to user";
$string['message_copy_description'] = "Enter {field name} from the form to appear in the message. Use {table} to place the all form fields";
$string['enable_limit'] = "Limit emails to one per session";
$string['enable_limit_description'] = "This stops users sending multiple emails";
$string['cannnot_send'] = "Sorry - You have already sent us an email - please give us time to process it";
$string['page_loggedin'] = "Force users to login";
$string['addpages'] = "Add pages";
$string['pages:addpages'] = "Add Pages";