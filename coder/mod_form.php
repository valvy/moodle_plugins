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

        // Standaard introductie elementen.
        $this->standard_intro_elements();

        // Welkomstbericht voor de gebruiker.
        $mform->addElement('textarea', 'welkomstbericht', 'Bericht aan gebruiker', 'wrap="virtual" rows="5" cols="50"');
        $mform->setType('welkomstbericht', PARAM_RAW);
        $mform->addRule('welkomstbericht', 'Geef een bericht op', 'required');

        // **Nieuw**: Python Code instelling toevoegen.
        // Hiermee kun je de Python code configureren die in de module gebruikt wordt.
        $mform->addElement('textarea', 'pythoncode', 'Python Code', 'wrap="virtual" rows="10" cols="50"');
        $mform->setType('pythoncode', PARAM_RAW);
        $mform->addRule('pythoncode', 'Geef de Python code op', 'required');

        // Standaard coursemodule elementen.
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
