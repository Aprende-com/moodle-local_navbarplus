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
 * Local plugin "Navbar Plus" - Library
 *
 * @package    local_navbarplus
 * @copyright  2017 Kathrin Osswald, Ulm University <kathrin.osswald@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Allow plugins to provide some content to be rendered in the navbar.
 * The plugin must define a PLUGIN_render_navbar_output function that returns
 * the HTML they wish to add to the navbar.
 *
 * @return string HTML for the navbar
 */
function local_navbarplus_render_navbar_output() {
    global $OUTPUT;

    // Fetch overall config.
    $config = get_config('local_navbarplus');
    // Initialize output.
    $output = '';

    // Make a new array on delimiter "new line".
    if (isset($config->inserticonswithlinks)) {
        // Get the lines from the config.
        $lines = explode("\n", $config->inserticonswithlinks);

        // Parse item settings.
        foreach ($lines as $line) {
            $line = trim($line);
            if (strlen($line) == 0) {
                continue;
            }

            $itemicon = null;
            $iconfaidentifier = null;
            $itemurl = null;
            $itemtitle = null;
            $itemvisible = false;
            $itemopeninnewwindow = false;
            $itemadditionalclasses = null;
            $itemid = null;

            // Make a new array on delimiter "|".
            $settings = explode('|', $line);
            // Check for the mandatory conditions first.
            // If array contains too less or too many settings, do not proceed and therefore do not display the item.
            // Furthermore check it at least the first three mandatory params are not an empty string.
            if (count($settings) >= 3 && count($settings) <= 7 &&
                $settings[0] !== '' && $settings[1] !== '' && $settings[2] !== '') {
                foreach ($settings as $i => $setting) {
                    $setting = trim($setting);
                    if (!empty($setting)) {
                        switch ($i) {
                            // Check for the mandatory first param: icon.
                            case 0:
                                $faiconpattern = '~^fa-[\w\d-]+$~';
                                // Check if it's matching the Font Awesome pattern.
                                if (preg_match($faiconpattern, $setting) > 0) {
                                    $iconfaidentifier = $setting;
                                    $itemvisible = true;
                                }
                                break;
                            // Check for the mandatory second param: URL.
                            case 1:
                                // Get the URL.
                                try {
                                    $itemurl = new moodle_url($setting);
                                    $itemvisible = true;
                                } catch (moodle_exception $exception) {
                                    // We're not actually worried about this, we don't want to mess up the display
                                    // just for a wrongly entered URL. We just hide the icon in this case.
                                    $itemurl = null;
                                    $itemvisible = false;
                                }
                                break;
                            // Check for the mandatory third param: text for title and alt attribute.
                            case 2:
                                $itemtitle = $setting;
                                $itemvisible = true;
                                break;
                            // Check for the optional fourth param: language support.
                            case 3:
                                // Only proceed if something is entered here. This parameter is optional.
                                // If no language is given the icon will be displayed in the navbar by default.
                                $itemlanguages = array_map('trim', explode(',', $setting));
                                $itemvisible &= in_array(current_language(), $itemlanguages);
                                break;
                            // Check for the optional fifth param: the target attribute.
                            case 4:
                                // Only set this value if the item is set to visible so far.
                                // Especially to keep the language check.
                                if ($setting == 'true' && $itemvisible == true) {
                                    $itemopeninnewwindow = true;
                                }
                                break;
                            // Check for optional sixth parameter: additional classes.
                            case 5:
                                $itemadditionalclasses = $setting;
                                break;
                            // Check for optional seventh parameter: additional id.
                            case 6:
                                $itemid = $setting;
                                break;
                        }
                    }
                }
            }
            // Add link with icon as a child to the surrounding div only if it should be displayed.
            // This is if all mandatory params are set and the item matches the optional given language setting.
            if ($itemvisible) {
                // To address accessibility, we need to define the icon here because the title from the next pipe is needed.
                $itemicon = '<i class="icon fa ' . $iconfaidentifier . ' fa-fw" aria-label="' . $itemtitle . '"></i>';
                // Set attributes for title and alt.
                $linkattributes = array('title' => $itemtitle);
                // If optional param for itemopeninnewwindow is set to true add a target=_blank to the link.
                if ($itemopeninnewwindow) {
                    $linkattributes['target'] = '_blank';
                    $linkattributes['rel'] = 'noopener noreferrer';
                }
                // Define classes for all icons.
                $itemclasses = 'localnavbarplus nav-link';
                // Add optional individual classes.
                if (!empty($itemadditionalclasses)) {
                    $itemclasses .= ' ' . $itemadditionalclasses;
                }
                // Initialise attribute array for the div tag.
                $divattributes = [];
                $divattributes['class'] = $itemclasses;
                // Add optional individual id prefixed with plugin name.
                if (!empty($itemid)) {
                    $divattributes['id'] = 'localnavbarplus-' . $itemid;
                }
                // Add the link to the HTML.
                $output .= html_writer::start_tag('div', $divattributes);
                $output .= html_writer::link($itemurl, $itemicon, $linkattributes);
                $output .= html_writer::end_tag('div');
            }
        }
    }
    // If setting resetuseertours is enabled.
    if (isset($config->resetusertours) && $config->resetusertours == true) {
        if (isloggedin() || !isguestuser()) {
            // Get the tour for the current page.
            $tour = \tool_usertours\manager::get_current_tours();
            if (!empty($tour)) {
                // Open div.
                $output .= html_writer::start_tag('div', array('class' => 'localnavbarplus nav-link',
                                                               'id' => 'localnavbarplus-resetusertour'));
                // Use the Font Awesome icon "map".
                $itemicon = '<i class="icon fa fa-map fa-fw"></i>';
                // Use the string for resetting the tour.
                $resetstring = get_string('resettouronpage', 'tool_usertours');
                $resethint = get_string('resetusertours_hint', 'local_navbarplus');
                // Set the alt and title attribute and set the id for resetting the tour as well.
                // Before Moodle 4.0, the click handler for the reset user tours link was registered with a
                // data-action attribute. However, in Moodle 4.0 the click handler is looking for the id
                // of the link. Unfortunately, if we set the id for this link to 'resetpagetour', we have
                // a duplicate id in the document. We are sorry for that, but we accept it as unavoidable.
                $attributes = array('alt' => $resetstring, 'title' => $resetstring . ' ' . $resethint,
                                    'id' => 'resetpagetour');
                // Add the link to the HTML.
                $output .= html_writer::link('#', $itemicon, $attributes);
                // Close div.
                $output .= html_writer::end_tag('div');
            }
        }
    }
    return $output;
}
