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

namespace block_clearmessages\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for selecting the cutoff date to delete messages.
 *
 * @package    block_clearmessages
 * @copyright  2026 Marcelo M. Almeida Jr.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clear_messages extends \moodleform {

    /**
     * Defines the form elements.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('date_selector', 'startdate', get_string('startdate', 'block_clearmessages'));
        $mform->addElement('submit', 'submitbutton', get_string('clearbutton', 'block_clearmessages'));
    }
}
