<?php
require_once('../../config.php');

$id = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'dashboard');
require_login($course, true, $cm);

$PAGE->set_url('/mod/dashboard/view.php', ['id' => $id]);
$PAGE->set_title($cm->name);
$PAGE->set_heading($course->fullname);
$PAGE->requires->css(new moodle_url('/mod/dashboard/style.css'));

echo $OUTPUT->header();

global $DB, $USER;

// Filters
$filter_harder = optional_param('filter_harder', 0, PARAM_BOOL);
$filter_easier = optional_param('filter_easier', 0, PARAM_BOOL);
$filter_open   = optional_param('filter_open', 0, PARAM_BOOL);

// Welkomstbericht
$instance = $DB->get_record('dashboard', ['id' => $cm->instance], '*', MUST_EXIST);
$bericht = format_text($instance->welkomstbericht ?? '', FORMAT_HTML);
$bericht = str_replace('{{naam}}', fullname($USER), $bericht);
echo html_writer::div($bericht, 'welkomstbericht');

// Studentniveau
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
            $niveau = 'expert';
        } elseif (count($labels) >= $quiz->threshold_skilled) {
            $niveau = 'skilled';
        } else {
            $niveau = 'aspiring';
        }
        echo html_writer::div("Jouw niveau: <strong>" . ucfirst($niveau) . " developer</strong>", 'user-level');
    }
}

if (!$niveau) {
    echo html_writer::div('Geen quizresultaat beschikbaar.', 'user-level');
    echo $OUTPUT->footer();
    exit;
}

// Filterformulier
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $PAGE->url]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);
echo html_writer::start_div('dashboard-filters');

$filters = [
    'filter_harder' => ['Toon moeilijkere opdrachten', $filter_harder, !($niveau == 'expert')],
    'filter_easier' => ['Toon makkelijkere opdrachten', $filter_easier, !($niveau == 'aspiring')],
    'filter_open'   => ['Toon alleen niet-voltooide', $filter_open, true]
];

foreach ($filters as $name => [$label, $value, $show]) {
    if ($show) {
        echo html_writer::checkbox($name, 1, $value, $label);
    }
}

echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => 'Filteren']);
echo html_writer::end_div();
echo html_writer::end_tag('form');

$modinfo = get_fast_modinfo($course);
$activiteiten = [];
$niveauvolgorde = ['aspiring' => 1, 'skilled' => 2, 'expert' => 3];

foreach ($modinfo->cms as $mod) {
    if (!$mod->uservisible || (int)$mod->completion === 0 || $mod->modname === 'dashboard') continue;

    $showaspiring = $showskilled = $showexpert = 0;
    $applicatie = ucfirst($mod->modname);
    $matchniveau = true;
    $difficulty = 'normal';

    if ($mod->modname === 'coder') {
        $opdracht = $DB->get_record('coder', ['id' => $mod->instance], '*', IGNORE_MISSING);
        if (!$opdracht) continue;

        $showaspiring = (int)$opdracht->showaspiring;
        $showskilled  = (int)$opdracht->showskilled;
        $showexpert   = (int)$opdracht->showexpert;

        $heeftlabels = $showaspiring || $showskilled || $showexpert;

        $labelniveaus = [];
        if ($showaspiring) $labelniveaus[] = 'aspiring';
        if ($showskilled)  $labelniveaus[] = 'skilled';
        if ($showexpert)   $labelniveaus[] = 'expert';

        $matchniveau = in_array($niveau, $labelniveaus);

        if ($heeftlabels && !$matchniveau) {
            $studentlevel = $niveauvolgorde[$niveau];
            $hoogste = max(array_map(fn($n) => $niveauvolgorde[$n], $labelniveaus));
            $laagste = min(array_map(fn($n) => $niveauvolgorde[$n], $labelniveaus));

            if ($hoogste > $studentlevel && !$filter_harder) continue;
            if ($laagste < $studentlevel && !$filter_easier) continue;

            $difficulty = ($hoogste > $studentlevel) ? 'harder' : 'easier';
        } elseif (!$heeftlabels && !$instance->toonalles) {
            continue;
        }

        $applicatie = $opdracht->applicatie_naam ?? $applicatie;
    } else {
        if (!$instance->toonalles) continue;
    }

    $activiteiten[] = (object)[
        'name' => $mod->name,
        'cmid' => $mod->id,
        'modname' => $mod->modname,
        'applicatie' => $applicatie,
        'matchniveau' => $matchniveau,
        'difficulty' => $difficulty,
        'showaspiring' => $showaspiring,
        'showskilled' => $showskilled,
        'showexpert' => $showexpert
    ];
}

$total = 0;
$voltooid = 0;
foreach ($activiteiten as $a) {
    if (!$a->matchniveau) continue;
    $completed = $DB->get_field('course_modules_completion', 'completionstate', [
        'coursemoduleid' => $a->cmid,
        'userid' => $USER->id
    ]);
    $total++;
    if ($completed == 1) $voltooid++;
}

$percentage = $total > 0 ? round(($voltooid / $total) * 100) : 0;
$kleur = '#f44336';
if ($percentage >= 100) $kleur = '#4caf50';
elseif ($percentage >= 67) $kleur = '#ffeb3b';
elseif ($percentage >= 34) $kleur = '#ff9800';

echo html_writer::tag('h4', "Voortgang: $voltooid van $total opdrachten voltooid ($percentage%)");
echo html_writer::div(
    html_writer::div('&nbsp;', null, [
        'style' => "width: {$percentage}%; height: 20px; background-color: {$kleur}; border-radius: 3px;"
    ]),
    null,
    ['style' => "width: 100%; background-color: #ddd; border-radius: 3px; margin-bottom: 20px; overflow: hidden;"]
);

echo html_writer::start_div('dashboard-cards', [
    'style' => 'display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;'
]);

foreach ($activiteiten as $a) {
    $completed = $DB->get_field('course_modules_completion', 'completionstate', [
        'coursemoduleid' => $a->cmid,
        'userid' => $USER->id
    ]);

    if ($filter_open && $completed == 1) continue;

    switch ($a->difficulty) {
        case 'harder': $kaartkleur = '#ffe6e6'; break;
        case 'easier': $kaartkleur = '#f5f5f5'; break;
        default:       $kaartkleur = '#ffffff'; break;
    }

    $statusicon = ($completed == 1) ? '✅' : '❌';
    $statuskleur = ($completed == 1) ? 'green' : 'red';
    $statustekst = ($completed == 1) ? 'Voltooid' : 'Niet voltooid';
    $link = new moodle_url("/mod/{$a->modname}/view.php", ['id' => $a->cmid]);

    $card = html_writer::start_div('dashboard-card position-relative', [
        'style' => "border: 1px solid #ccc; border-radius: 8px; padding: 1rem; background-color: $kaartkleur; box-shadow: 2px 2px 5px rgba(0,0,0,0.05); position: relative;"
    ]);

    // Sticky badges
    $tags = [];
    if (!empty($a->showaspiring)) $tags[] = html_writer::span('Aspiring', 'dashboard-tag aspiring');
    if (!empty($a->showskilled))  $tags[] = html_writer::span('Skilled', 'dashboard-tag skilled');
    if (!empty($a->showexpert))   $tags[] = html_writer::span('Expert', 'dashboard-tag expert');
    if (!empty($tags)) {
        $card .= html_writer::div(implode(' ', $tags), 'dashboard-tags');
    }

    $card .= html_writer::tag('h4', format_string($a->name));
    $card .= html_writer::div('📚 ' . format_string($a->applicatie), '', ['style' => 'margin-bottom: 0.5rem;']);
    $card .= html_writer::div(html_writer::link($link, '🔗 Bekijk in Moodle'), '', ['style' => 'margin-bottom: 0.5rem;']);
    $card .= html_writer::div("{$statusicon} <span style='color:{$statuskleur}; font-weight: bold;'>{$statustekst}</span>");

    $card .= html_writer::end_div();
    echo $card;
}

echo html_writer::end_div();
echo $OUTPUT->footer();