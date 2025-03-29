<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->libdir . '/filelib.php');

class mod_codequiz_mod_form extends moodleform_mod {

    public function definition() {
        global $DB;
        $mform = $this->_form;

        // Activiteitsnaam
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        // Introductie
        $this->standard_intro_elements();

        // Hoofdheader voor labels en berichten
        $mform->addElement('header', 'labels_settings', 'Instellingen Labels');

        // Subheader voor thresholds
        $mform->addElement('static', 'label_vanaf', '', '<strong>Label vanaf</strong>');

        $mform->addElement('text', 'threshold_aspiring', 'Aspiring developer vanaf punten:', ['size' => 5]);
        $mform->setType('threshold_aspiring', PARAM_INT);

        $mform->addElement('text', 'threshold_skilled', 'Skilled developer vanaf punten:', ['size' => 5]);
        $mform->setType('threshold_skilled', PARAM_INT);

        $mform->addElement('text', 'threshold_expert', 'Expert developer vanaf punten:', ['size' => 5]);
        $mform->setType('threshold_expert', PARAM_INT);

        // Subheader voor berichten
        $mform->addElement('static', 'label_berichten', '', '<strong>Berichten op eindschermen</strong>');

        $mform->addElement('editor', 'message_aspiring', 'Bericht voor Aspiring developer');
        $mform->setType('message_aspiring', PARAM_RAW);

        $mform->addElement('editor', 'message_skilled', 'Bericht voor Skilled developer');
        $mform->setType('message_skilled', PARAM_RAW);

        $mform->addElement('editor', 'message_expert', 'Bericht voor Expert developer');
        $mform->setType('message_expert', PARAM_RAW);

        // Hoofdheader voor vragen
        $mform->addElement('header', 'vragen_settings', 'Vragen');

        // Bepaal aantal vragen
        $vraagcount = 1;
        if ($this->current && !empty($this->current->id)) {
            $vraagcount = $DB->count_records('codequiz_questions', ['codequizid' => $this->current->id]);
            $vraagcount = $vraagcount > 0 ? $vraagcount : 1;
        }

        // Herhaalbare vragen
        $repeatarray = [];

        $repeatarray[] = $mform->createElement('text', 'vraagtext', 'Vraagtekst');
        $repeatarray[] = $mform->createElement('filemanager', 'mediaupload', 'Afbeelding (upload)', null, [
            'subdirs' => 0,
            'maxbytes' => 10485760,
            'maxfiles' => 1,
            'accepted_types' => ['image'],
            'return_types' => FILE_INTERNAL
        ]);
        $repeatarray[] = $mform->createElement('editor', 'mediahtml', 'Media (HTML, optioneel)');
        $repeatarray[] = $mform->createElement('selectyesno', 'crop', 'Media croppen?');
        $repeatarray[] = $mform->createElement('textarea', 'optiesjson', 'Opties (JSON)');

        $repeateloptions = [
            'vraagtext' => ['type' => PARAM_TEXT],
            'mediahtml' => ['type' => PARAM_RAW],
            'crop' => ['type' => PARAM_INT],
            'optiesjson' => ['type' => PARAM_RAW],
        ];

        $this->repeat_elements(
            $repeatarray,
            $vraagcount,
            $repeateloptions,
            'vragen_repeats',
            'vragen_add_fields',
            1,
            get_string('addquestion', 'codequiz'),
            true
        );

        $this->standard_coursemodule_elements();
        $this->add_completion_rules();
        $this->add_action_buttons();
    }

    public function add_completion_rules() {
        $mform = $this->_form;

        $mform->addElement('checkbox', 'completionpass',
            get_string('completionpass_label', 'codequiz'),
            get_string('completionpass', 'codequiz')
        );
        $mform->addHelpButton('completionpass', 'completionpass', 'codequiz');

        return ['completionpass'];
    }

    public function completion_rule_enabled($data) {
        return !empty($data['completionpass']);
    }

    public function data_preprocessing(&$defaultvalues) {
        global $DB;

        if ($this->current && !empty($this->current->id)) {
            $instance = $DB->get_record('codequiz', ['id' => $this->current->id]);

            $defaultvalues['threshold_aspiring'] = $instance->threshold_aspiring;
            $defaultvalues['threshold_skilled'] = $instance->threshold_skilled;
            $defaultvalues['threshold_expert'] = $instance->threshold_expert;

            $defaultvalues['message_aspiring'] = ['text' => $instance->message_aspiring, 'format' => FORMAT_HTML];
            $defaultvalues['message_skilled'] = ['text' => $instance->message_skilled, 'format' => FORMAT_HTML];
            $defaultvalues['message_expert'] = ['text' => $instance->message_expert, 'format' => FORMAT_HTML];

            $defaultvalues['completionpass'] = $instance->completionpass;

            $questions = $DB->get_records('codequiz_questions', ['codequizid' => $this->current->id], 'sortorder ASC');

            $i = 0;
            foreach ($questions as $question) {
                $defaultvalues['vraagtext'][$i] = $question->vraag;
                $defaultvalues['mediahtml'][$i] = ['text' => $question->mediahtml, 'format' => FORMAT_HTML];
                $defaultvalues['crop'][$i] = $question->crop;
                $defaultvalues['optiesjson'][$i] = $question->opties;

                $draftid = file_get_submitted_draft_itemid("mediaupload[{$i}]");
                file_prepare_draft_area($draftid, $this->context->id, 'mod_codequiz', 'mediaupload', $this->current->id * 100 + $i);
                $defaultvalues['mediaupload'][$i] = $draftid;
                $i++;
            }

            $defaultvalues['vragen_repeats'] = $i;
        }
    }
}