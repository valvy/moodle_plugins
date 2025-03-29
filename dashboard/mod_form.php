<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_dashboard_mod_form extends moodleform_mod {
    function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'name', 'Activiteitsnaam', ['size'=>'64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');

        $this->standard_intro_elements();

        $mform->addElement('textarea', 'welkomstbericht', 'Bericht aan gebruiker', 'wrap="virtual" rows="5" cols="50"');
        $mform->setType('welkomstbericht', PARAM_RAW);
        $mform->addRule('welkomstbericht', 'Geef een bericht op', 'required');

        // Nieuw veld: verwijzing naar de gekoppelde codequiz-activiteit
        $mform->addElement('text', 'codequizid', 'CodeQuiz ID');
        $mform->setType('codequizid', PARAM_INT);
        $mform->addRule('codequizid', 'Geef de CodeQuiz ID op', 'required');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
