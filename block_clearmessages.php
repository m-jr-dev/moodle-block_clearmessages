<?php

/**
 * Classe do Bloco clearmessages.
 * Plugin de Bloco para permitir que usuários apaguem suas mensagens até uma data selecionada.
 *
 * @package    block_clearmessages
 */
class block_clearmessages extends block_base {
	

    /**
     * Inicializa o bloco definindo o título.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_clearmessages');
    }

    /**
     * Retorna o conteúdo do bloco, incluindo o formulário e processa o envio para apagar mensagens.
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
			
            // Obtém timestamp do início do dia selecionado.
            $startdate = $data->startdate;

            // Cria timestamp para o final do dia selecionado (23:59:59)
            $time_limit = strtotime('23:59:59', $startdate);

            /// Busca mensagens enviadas pelo usuário até a data limite.
            $sentmessages = $DB->get_records_select('messages',
                'useridfrom = ? AND timecreated <= ?',
                [$USER->id, $time_limit]
            );

            // Busca ações de mensagens recebidas pelo usuário até a data limite, exceto mensagens já deletadas (action=2).
            $receivedactions = $DB->get_records_select('message_user_actions',
                'userid = ? AND action <> 2 AND timecreated <= ?',
                [$USER->id, $time_limit]
            );

            // Lista unificada das mensagens a serem marcadas como apagadas.
            $allmessageids = [];

            foreach ($sentmessages as $msg) {
                $allmessageids[$msg->id] = true;
            }

            foreach ($receivedactions as $action) {
                $allmessageids[$action->messageid] = true;
            }

            if (empty($allmessageids)) {
				 // Caso não encontre mensagens, exibe notificação de erro.
                $this->content->text .= $OUTPUT->notification(
                    get_string('no_messages_found', 'block_clearmessages'),
                    'notifyerror'
                );
            } else {
                foreach (array_keys($allmessageids) as $messageid) {
                    // Evitar duplicidade na tabela message_user_actions
                    if (!$DB->record_exists('message_user_actions', [
                        'userid'     => $USER->id,
                        'messageid'  => $messageid,
                        'action'     => 2
                    ])) {
                        $record = new stdClass();
                        $record->userid      = $USER->id;
                        $record->messageid   = $messageid;
                        $record->action      = 2; // Deleted
                        $record->timecreated = time();

                        $DB->insert_record('message_user_actions', $record);
                    }
                }

                // Exibe notificação de sucesso após apagar mensagens.
                $this->content->text .= $OUTPUT->notification(
                    get_string('messages_cleared', 'block_clearmessages'),
                    'notifysuccess'
                );
            }
        }

        // Renderiza o formulário na saída do bloco.
        $this->content->text .= $mform->render();
        return $this->content;
    }

    /**
     * Define os formatos onde o bloco pode ser usado.
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
