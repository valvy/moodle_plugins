<?php
require_once('../../config.php');

$courseid   = required_param('courseid', PARAM_INT);
$instanceid = required_param('instanceid', PARAM_INT);

if (!$cm = get_coursemodule_from_instance('codequiz', $instanceid, $courseid)) {
    print_error('invalidcoursemodule');
}
$course = get_course($courseid);
require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/codequiz:managedashboard', $context);

$PAGE->set_url('/mod/codequiz/dashboard.php', ['courseid' => $courseid, 'instanceid' => $instanceid]);
$PAGE->set_title('Code Quiz Dashboard');
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

// Resultaten ophalen
$results = $DB->get_records('codequiz_results', ['codequizid' => $instanceid]);
$totalSubmissions = count($results);

// Vragen ophalen
$questions = $DB->get_records('codequiz_questions', ['codequizid' => $instanceid], 'sortorder ASC');

$optionMap = [];
foreach ($questions as $question) {
    $sortindex = (int)$question->sortorder;
    $opties = json_decode($question->opties, true);
    foreach ($opties as $optie) {
        $optionMap[$sortindex][(string)$optie['value']] = $optie['text'];
    }
}

// Wordcloud data + gebruikersmapping
$answerTextCounts = [];
$answerUserMap = [];
foreach ($results as $result) {
    $answers = json_decode($result->answers ?? '', true);
    $user = $DB->get_record('user', ['id' => $result->userid]);
    $fullname = fullname($user);

    if (is_array($answers)) {
        foreach ($answers as $i => $value) {
            $text = $optionMap[$i][(string)$value] ?? null;
            if ($text) {
                $answerTextCounts[$text] = ($answerTextCounts[$text] ?? 0) + 1;
                $answerUserMap[$text][] = $fullname;
            }
        }
    }
}

// Labels tellen
$labelCounts = [];
foreach ($results as $result) {
    $decodedLabels = json_decode($result->labels, true);
    if (is_array($decodedLabels)) {
        foreach ($decodedLabels as $label) {
            $labelCounts[$label] = ($labelCounts[$label] ?? 0) + 1;
        }
    }
}

$chartLabels = array_keys($labelCounts);
$chartData = array_values($labelCounts);

echo $OUTPUT->header();
?>

<!-- Chart.js & WordCloud2 -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/wordcloud2.js/1.1.1/wordcloud2.min.js"></script>

<style>
.dashboard-wrapper {
    padding: 20px;
}
.dashboard-table {
    width: 100%;
    border-collapse: collapse;
}
.dashboard-table th, .dashboard-table td {
    border: 1px solid #ccc;
    padding: 8px;
    text-align: left;
}
.dashboard-table th {
    background-color: #f5f5f5;
}
#wordcloud {
    width: 100%;
    height: 400px;
    border: 1px solid #ccc;
    margin-top: 30px;
}

/* Modal styling */
#modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 999;
    opacity: 0;
    transition: opacity 0.3s ease;
}

#modal-overlay.show {
    display: flex;
    opacity: 1;
}

.modal {
    background: white;
    color: #333;
    padding: 30px;
    border-radius: 10px;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 0 20px rgba(0,0,0,0.4);
    position: relative;
    z-index: 1000;
    display: block;
    max-height: 80vh;
    overflow-y: auto;
}
.modal h3 {
    margin-top: 0;
}
.modal ul {
    padding-left: 20px;
}
.modal-close {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 24px;
    cursor: pointer;
}
</style>

<div class="dashboard-wrapper">
    <h2>Code Quiz Dashboard</h2>
    <p>Totaal aantal inzendingen: <?php echo $totalSubmissions; ?></p>

    <!-- Pie Chart -->
    <div style="width: 400px; margin-bottom: 20px;">
        <canvas id="labelsPieChart" width="400" height="400"></canvas>
    </div>

    <!-- Wordcloud -->
    <h3>Antwoord Wordcloud</h3>
    <div id="wordcloud"></div>

    <?php if ($totalSubmissions > 0): ?>
        <table class="dashboard-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Gebruikersnaam</th>
                    <th>Label</th>
                    <th>Antwoorden</th>
                    <th>Tijd van inzending</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $result):
                    $user = $DB->get_record('user', ['id' => $result->userid]);
                    $fullname = fullname($user);

                    $decodedLabel = json_decode($result->labels, true);
                    $labelText = is_array($decodedLabel) ? implode(", ", $decodedLabel) : $result->labels;

                    $decodedAnswers = json_decode($result->answers ?? '', true);
                    $answerTextList = [];
                    foreach ($decodedAnswers ?? [] as $i => $v) {
                        $answerTextList[] = $optionMap[$i][(string)$v] ?? $v;
                    }
                    $answerText = implode(", ", $answerTextList);
                ?>
                    <tr>
                        <td><?php echo $result->id; ?></td>
                        <td><?php echo $fullname; ?></td>
                        <td><?php echo $labelText; ?></td>
                        <td><?php echo $answerText; ?></td>
                        <td><?php echo date('Y-m-d H:i:s', $result->timecreated); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Geen inzendingen gevonden.</p>
    <?php endif; ?>
</div>

<!-- Modal -->
<div id="modal-overlay">
  <div class="modal">
    <span class="modal-close" onclick="closeModal()">&times;</span>
    <h3 id="modal-title"></h3>
    <ul id="modal-content"></ul>
  </div>
</div>

<script>
const answerUserMap = <?php echo json_encode($answerUserMap); ?>;

document.addEventListener('DOMContentLoaded', function() {
    // Pie chart
    const ctx = document.getElementById('labelsPieChart').getContext('2d');
    const chartLabels = <?php echo json_encode($chartLabels); ?>;
    const chartData = <?php echo json_encode($chartData); ?>;

    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: chartLabels,
            datasets: [{
                data: chartData,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(255, 206, 86, 0.6)',
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(153, 102, 255, 0.6)',
                    'rgba(255, 159, 64, 0.6)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                title: { display: true, text: 'Verdeelde labels' }
            }
        }
    });

    // Wordcloud
    const wordCounts = <?php echo json_encode($answerTextCounts); ?>;
    const wordList = Object.entries(wordCounts);

    WordCloud(document.getElementById('wordcloud'), {
        list: wordList,
        gridSize: Math.round(16 * document.getElementById('wordcloud').offsetWidth / 1024),
        weightFactor: function (size) { return size * 3; },
        fontFamily: 'monospace',
        color: 'random-dark',
        backgroundColor: '#fff',
        click: function (item) {
            const woord = item[0];
            const gebruikers = answerUserMap[woord] || [];
            openModal(woord, gebruikers);
        }
    });
});

function openModal(title, users) {
    const overlay = document.getElementById('modal-overlay');
    const modalTitle = document.getElementById('modal-title');
    const modalContent = document.getElementById('modal-content');

    modalTitle.textContent = `Antwoord: "${title}"`;
    modalContent.innerHTML = '';

    if (users.length > 0) {
        users.forEach(name => {
            const li = document.createElement('li');
            li.textContent = name;
            modalContent.appendChild(li);
        });
    } else {
        const li = document.createElement('li');
        li.textContent = 'Geen gebruikers gevonden.';
        modalContent.appendChild(li);
    }

    overlay.classList.add('show');
}

function closeModal() {
    const overlay = document.getElementById('modal-overlay');
    overlay.classList.remove('show');
}
</script>

<?php
echo $OUTPUT->footer();
