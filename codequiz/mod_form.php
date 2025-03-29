<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->libdir . '/filelib.php');

class mod_codequiz_mod_form extends moodleform_mod {

    public function definition() {
        $mform = $this->_form;

        // Activiteitsnaam
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        // Introductie
        $this->standard_intro_elements();

        // Instellingen voor labels
        $mform->addElement('header', 'labelthresholds', 'Instellingen voor labels');

        $mform->addElement('text', 'threshold_aspiring', 'Aspiring developer vanaf punten:', ['size' => 5]);
        $mform->setType('threshold_aspiring', PARAM_INT);

        $mform->addElement('text', 'threshold_skilled', 'Skilled developer vanaf punten:', ['size' => 5]);
        $mform->setType('threshold_skilled', PARAM_INT);

        $mform->addElement('text', 'threshold_expert', 'Expert developer vanaf punten:', ['size' => 5]);
        $mform->setType('threshold_expert', PARAM_INT);

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
            1,
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

    public function data_preprocessing(&$defaultvalues) {
        global $DB;

        if ($this->current && !empty($this->current->id)) {
            $instance = $DB->get_record('codequiz', ['id' => $this->current->id]);

            $defaultvalues['threshold_aspiring'] = $instance->threshold_aspiring;
            $defaultvalues['threshold_skilled'] = $instance->threshold_skilled;
            $defaultvalues['threshold_expert'] = $instance->threshold_expert;

            $questions = $DB->get_records('codequiz_questions', ['codequizid' => $this->current->id], 'sortorder ASC');

            $vraagtext = [];
            $mediahtml = [];
            $mediaupload = [];
            $crop = [];
            $optiesjson = [];

            $i = 0;
            foreach ($questions as $question) {
                $vraagtext[$i] = $question->vraag ?? '';
                $mediahtml[$i] = [
                    'text' => $question->mediahtml ?? '',
                    'format' => FORMAT_HTML
                ];
                $crop[$i] = isset($question->crop) ? (int)$question->crop : 1;
                $optiesjson[$i] = $question->opties;

                $draftid = file_get_submitted_draft_itemid("mediaupload[{$i}]");
                file_prepare_draft_area(
                    $draftid,
                    $this->context->id,
                    'mod_codequiz',
                    'mediaupload',
                    $this->current->id * 100 + $i
                );
                $mediaupload[$i] = $draftid;

                $i++;
            }

            $defaultvalues['vragen_repeats'] = $i;
            $defaultvalues['vraagtext'] = $vraagtext;
            $defaultvalues['mediahtml'] = $mediahtml;
            $defaultvalues['crop'] = $crop;
            $defaultvalues['optiesjson'] = $optiesjson;
            $defaultvalues['mediaupload'] = $mediaupload;
        }
    }
}
