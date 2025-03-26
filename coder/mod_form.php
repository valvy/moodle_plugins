<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_coder_mod_form extends moodleform_mod {
    function definition() {
        $mform = $this->_form;

        // Activiteitsnaam.
        $mform->addElement('text', 'name', 'Activiteitsnaam', ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');

        // Introductie (Moodle standaard).
        $this->standard_intro_elements();

        // Welkomstbericht.
        $mform->addElement('textarea', 'welkomstbericht', 'Bericht aan gebruiker', 'wrap="virtual" rows="5" cols="50"');
        $mform->setType('welkomstbericht', PARAM_RAW);
        $mform->addRule('welkomstbericht', 'Geef een bericht op', 'required');

        // Python code.
        $mform->addElement('textarea', 'pythoncode', 'Python Code', 'wrap="virtual" rows="10" cols="50"');
        $mform->setType('pythoncode', PARAM_RAW);
        $mform->addRule('pythoncode', 'Geef de Python code op', 'required');

        // Labels.
        $mform->addElement('advcheckbox', 'showexpert', 'Toon Expert label');
        $mform->setDefault('showexpert', 0);
        $mform->setType('showexpert', PARAM_INT);

        $mform->addElement('advcheckbox', 'showskilled', 'Toon Skilled label');
        $mform->setDefault('showskilled', 1);
        $mform->setType('showskilled', PARAM_INT);

        $mform->addElement('advcheckbox', 'showaspiring', 'Toon Aspiring label');
        $mform->setDefault('showaspiring', 1);
        $mform->setType('showaspiring', PARAM_INT);

        // Applicatie naam.
        $mform->addElement('text', 'applicatie_naam', 'Applicatie Naam', ['size' => '64']);
        $mform->setType('applicatie_naam', PARAM_TEXT);
        $mform->addRule('applicatie_naam', null, 'required');
        $mform->setDefault('applicatie_naam', 'Opdracht1');

        // Pagina titel.
        $mform->addElement('text', 'pagetitle', 'Pagina Titel', ['size' => '64']);
        $mform->setType('pagetitle', PARAM_TEXT);
        $mform->addRule('pagetitle', null, 'required');
        $mform->setDefault('pagetitle', 'Forensische ICT Opdracht');

        // Submission URL.
        $mform->addElement('text', 'submissionurl', 'Submission URL', ['size' => '64']);
        $mform->setType('submissionurl', PARAM_URL);
        $mform->addRule('submissionurl', null, 'required');
        $mform->setDefault('submissionurl', 'https://app.codegra.de/');

        // Output voorbeeld.
        $mform->addElement('textarea', 'outputexample', 'Output Example Content', 'wrap="virtual" rows="5" cols="50"');
        $mform->setType('outputexample', PARAM_RAW);
        $mform->addRule('outputexample', 'Geef de content voor output example op', 'required');
        $mform->setDefault('outputexample', '<div>Welk woordje wil je versleutelen? <strong><em>hallo</em></strong><br>Welke sleutel wil je gebruiken? <strong><em>3</em></strong><br>De versleuteling van pizza is kdoor</div>');

        // Afbeelding uploaden (optioneel).
        // Nieuwe regel:
        $mform->addElement('filemanager', 'headerimage', 'Header Image', null, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['image']
        ]);
        $mform->setType('headerimage', PARAM_INT);

        // Standaard course module elementen (zoals beschikbaarheid).
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    function data_preprocessing(&$default_values) {
        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('headerimage');
            file_prepare_draft_area(
                $draftitemid,
                $this->context->id,
                'mod_coder',
                'headerimage',
                $this->current->id,
                ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['image']]
            );
            $default_values['headerimage'] = $draftitemid;
        }
    }
}
