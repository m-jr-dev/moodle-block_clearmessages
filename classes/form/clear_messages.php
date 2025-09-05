<?php

namespace block_clearmessages\form;

/**
 * Formulário para seleção da data limite para apagar mensagens.
 *
 * @package    block_clearmessages
 */
class clear_messages extends \moodleform {
	
    /**
     * Define os elementos do formulário.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('date_selector', 'startdate', get_string('startdate', 'block_clearmessages'));
        $mform->addElement('submit', 'submitbutton', get_string('clearbutton', 'block_clearmessages'));
    }
}
