<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_coder_mod_form extends moodleform_mod {
    function definition() {
        $mform = $this->_form;

        // Activiteitsnaam.
        $mform->addElement('text', 'name', 'Activiteitsnaam', array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');

        // Standaard introductie elementen.
        $this->standard_intro_elements();

        // Welkomstbericht voor de gebruiker.
        $mform->addElement('textarea', 'welkomstbericht', 'Bericht aan gebruiker', 'wrap="virtual" rows="5" cols="50"');
        $mform->setType('welkomstbericht', PARAM_RAW);
        $mform->addRule('welkomstbericht', 'Geef een bericht op', 'required');

        // Python code invullen.
        $mform->addElement('textarea', 'pythoncode', 'Python Code', 'wrap="virtual" rows="10" cols="50"');
        $mform->setType('pythoncode', PARAM_RAW);
        $mform->addRule('pythoncode', 'Geef de Python code op', 'required');

        // Checkboxen voor labels.
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
        $mform->addElement('text', 'applicatie_naam', 'Applicatie Naam', array('size' => '64'));
        $mform->setType('applicatie_naam', PARAM_TEXT);
        $mform->addRule('applicatie_naam', null, 'required');
        $mform->setDefault('applicatie_naam', 'Opdracht1');

        // Pagina titel.
        $mform->addElement('text', 'pagetitle', 'Pagina Titel', array('size' => '64'));
        $mform->setType('pagetitle', PARAM_TEXT);
        $mform->addRule('pagetitle', null, 'required');
        $mform->setDefault('pagetitle', 'Forensische ICT Opdracht');

        // Submission URL.
        $mform->addElement('text', 'submissionurl', 'Submission URL', array('size' => '64'));
        $mform->setType('submissionurl', PARAM_URL);
        $mform->addRule('submissionurl', null, 'required');
        $mform->setDefault('submissionurl', 'https://app.codegra.de/');

        // Output-example content (HTML-content voor de voorbeeldweergave).
        $mform->addElement('textarea', 'outputexample', 'Output Example Content', 'wrap="virtual" rows="5" cols="50"');
        $mform->setType('outputexample', PARAM_RAW);
        $mform->addRule('outputexample', 'Geef de content voor output example op', 'required');
        $mform->setDefault('outputexample', '<div>Welk woordje wil je versleutelen? <strong><em>hallo</em></strong><br>Welke sleutel wil je gebruiken? <strong><em>3</em></strong><br>De versleuteling van pizza is kdoor</div>');

        // Standaard coursemodule elementen.
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
