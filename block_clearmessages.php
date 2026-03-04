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
 * Clear messages block class.
 * Block plugin to allow users to delete their messages up to a selected date.
 *
 * @package    block_clearmessages
 * @copyright  2026 Marcelo M. Almeida Jr.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_clearmessages extends block_base {

    /**
     * Initialises the block by setting the title.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_clearmessages');
    }

    /**
     * Returns the block content, including the form, and processes submission to delete messages.
     *
     * @return stdClass|null
     */
    public function get_content() {
        global $OUTPUT, $USER, $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        if (!has_capability('block/clearmessages:clear', context_system::instance())) {
            return null;
        }

        $this->content = new stdClass();
        $this->content->text = '';

        $mform = new \block_clearmessages\form\clear_messages();
        if ($data = $mform->get_data()) {

            // Gets the timestamp for the start of the selected day.
            $startdate = $data->startdate;

            // Creates the timestamp for the end of the selected day (23:59:59).
            $timelimit = strtotime('23:59:59', $startdate);

            // Fetches messages sent by the user up to the limit date.
            $sentmessages = $DB->get_records_select(
                'messages',
                'useridfrom = ? AND timecreated <= ?',
                [$USER->id, $timelimit]
            );

            // Fetches received message actions up to the limit date, except messages already deleted (action=2).
            $receivedactions = $DB->get_records_select(
                'message_user_actions',
                'userid = ? AND action <> 2 AND timecreated <= ?',
                [$USER->id, $timelimit]
            );

            // Unified list of messages to be marked as deleted.
            $allmessageids = [];

            foreach ($sentmessages as $msg) {
                $allmessageids[$msg->id] = true;
            }

            foreach ($receivedactions as $action) {
                $allmessageids[$action->messageid] = true;
            }

            if (empty($allmessageids)) {
                // If no messages are found, shows an error notification.
                $this->content->text .= $OUTPUT->notification(
                    get_string('no_messages_found', 'block_clearmessages'),
                    'notifyerror'
                );
            } else {
                foreach (array_keys($allmessageids) as $messageid) {
                    // Avoids duplicates in the message_user_actions table.
                    if (!$DB->record_exists('message_user_actions', [
                        'userid'     => $USER->id,
                        'messageid'  => $messageid,
                        'action'     => 2,
                    ])) {
                        $record = new stdClass();
                        $record->userid      = $USER->id;
                        $record->messageid   = $messageid;
                        $record->action      = 2; // Deleted.
                        $record->timecreated = time();

                        $DB->insert_record('message_user_actions', $record);
                    }
                }

                // Shows a success notification after deleting messages.
                $this->content->text .= $OUTPUT->notification(
                    get_string('messages_cleared', 'block_clearmessages'),
                    'notifysuccess'
                );
            }
        }

        // Renders the form in the block output.
        $this->content->text .= $mform->render();
        return $this->content;
    }

    /**
     * Defines the formats where the block can be used.
     *
     * @return array
     */
    public function applicable_formats() {
        $systemcontext = context_system::instance();
        if (has_capability('moodle/site:config', $systemcontext)) {
            return ['all' => true];
        }
        return [];
    }
}
