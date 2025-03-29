<?php
require_once('../../config.php');

$id = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'dashboard');
require_login($course, true, $cm);

$PAGE->set_url('/mod/dashboard/view.php', ['id' => $id]);
$PAGE->set_title($cm->name);
$PAGE->set_heading($course->fullname);

// Optional: laad CSS
$PAGE->requires->css(new moodle_url('/mod/dashboard/style.css'));

echo $OUTPUT->header();

global $DB, $USER;

// Haal dashboard instance op
$instance = $DB->get_record('dashboard', ['id' => $cm->instance], '*', MUST_EXIST);

// Welkomstbericht met naam
$bericht = format_text($instance->welkomstbericht ?? '', FORMAT_HTML);
$bericht = str_replace('{{naam}}', fullname($USER), $bericht);
echo '<div class="welkomstbericht">' . $bericht . '</div>';

// ---------------------------------------------
// Haal quizresultaten op en bepaal niveau
// ---------------------------------------------
$niveau = null;

if (!empty($instance->codequizid)) {
    $result = $DB->get_record('codequiz_results', [
        'codequizid' => $instance->codequizid,
        'userid' => $USER->id
    ]);

    $quiz = $DB->get_record('codequiz', ['id' => $instance->codequizid]);

    if ($result && $quiz) {
        $labels = array_map('trim', explode(',', $result->labels));

        if (count($labels) >= $quiz->threshold_expert) {
            $niveau = 'Expert Developer';
        } else if (count($labels) >= $quiz->threshold_skilled) {
            $niveau = 'Skilled Developer';
        } else {
            $niveau = 'Aspiring Developer';
        }

        echo html_writer::div("Jouw niveau: <strong>$niveau</strong>", 'user-level');
    } else {
        echo html_writer::div('Geen resultaat gevonden voor de gekoppelde quiz.', 'user-level');
    }
}

// ---------------------------------------------
// Toon opdrachten van type 'coder' per niveau
// ---------------------------------------------
if (isset($niveau)) {
    $visiblefield = '';
    switch (strtolower($niveau)) {
        case 'aspiring developer':
            $visiblefield = 'showaspiring';
            break;
        case 'skilled developer':
            $visiblefield = 'showskilled';
            break;
        case 'expert developer':
            $visiblefield = 'showexpert';
            break;
    }

    if ($visiblefield !== '') {
        $coders = $DB->get_records('coder', ['course' => $course->id]);

        $zichtbare_opdrachten = array_filter($coders, function($c) use ($visiblefield) {
            return isset($c->$visiblefield) && $c->$visiblefield == 1;
        });

        echo html_writer::tag('h3', 'Beschikbare opdrachten voor jouw niveau:');

        if (empty($zichtbare_opdrachten)) {
            echo html_writer::div('Er zijn geen opdrachten beschikbaar voor jouw niveau.');
        } else {
            // ---------------------------------------------
            // ✅ Progress bar
            // ---------------------------------------------
            $total = count($zichtbare_opdrachten);
            $voltooid = 0;

            foreach ($zichtbare_opdrachten as $opdracht) {
                $moduleid = $DB->get_field('modules', 'id', ['name' => 'coder']);
                $cmid = $DB->get_field('course_modules', 'id', [
                    'instance' => $opdracht->id,
                    'module' => $moduleid,
                    'course' => $course->id
                ]);

                $completed = $DB->get_field('course_modules_completion', 'completionstate', [
                    'coursemoduleid' => $cmid,
                    'userid' => $USER->id
                ]);

                if ($completed == 1) {
                    $voltooid++;
                }
            }

            $percentage = $total > 0 ? round(($voltooid / $total) * 100) : 0;

            echo html_writer::tag('h4', "Voortgang: $voltooid van $total opdrachten voltooid ($percentage%)");

            // Dynamische kleur bepalen
            $kleur = '#f44336'; // rood
            if ($percentage >= 100) {
                $kleur = '#4caf50'; // groen
            } else if ($percentage >= 67) {
                $kleur = '#ffeb3b'; // geel
            } else if ($percentage >= 34) {
                $kleur = '#ff9800'; // oranje
            }

            $progressbar = html_writer::div(
                html_writer::div('&nbsp;', null, [
                    'style' => "width: {$percentage}%; height: 20px; background-color: {$kleur}; border-radius: 3px;"
                ]),
                null,
                [
                    'style' => "width: 100%; background-color: #ddd; border-radius: 3px; margin-bottom: 20px; overflow: hidden;"
                ]
            );

            echo $progressbar;

            // ---------------------------------------------
            // ✅ Opdrachten-tabel
            // ---------------------------------------------
            $table = new html_table();
            $table->head = ['Naam', 'Applicatie', 'Inleverlink', 'Status'];
            $table->attributes['class'] = 'generaltable mod-dashboard-table';
            $table->headspan = [1, 1, 1, 1];
            $table->colclasses = ['leftalign', 'leftalign', 'centeralign', 'centeralign'];
            $table->data = [];

            $rownum = 0;

            foreach ($zichtbare_opdrachten as $opdracht) {
                $moduleid = $DB->get_field('modules', 'id', ['name' => 'coder']);
                $cmid = $DB->get_field('course_modules', 'id', [
                    'instance' => $opdracht->id,
                    'module' => $moduleid,
                    'course' => $course->id
                ]);

                $completed = $DB->get_field('course_modules_completion', 'completionstate', [
                    'coursemoduleid' => $cmid,
                    'userid' => $USER->id
                ]);

                $statusicon = ($completed == 1)
                    ? '<span style="color:green;">✅ Voltooid</span>'
                    : '<span style="color:red;">❌ Niet voltooid</span>';

                $row = new html_table_row([
                    new html_table_cell(format_string($opdracht->name)),
                    new html_table_cell(format_string($opdracht->applicatie_naam)),
                    new html_table_cell(html_writer::link(
                        new moodle_url('/mod/coder/view.php', ['id' => $cmid]),
                        'Bekijk in Moodle',
                        ['target' => '_blank']
                    )),
                    new html_table_cell($statusicon)
                ]);

                $row->attributes['class'] = ($rownum++ % 2 == 0) ? 'even' : 'odd';
                $table->data[] = $row;
            }

            echo html_writer::table($table);
        }
    }
}

echo $OUTPUT->footer();
