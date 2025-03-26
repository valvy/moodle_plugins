<?php
require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'coder');
require_login($course, true, $cm);

$context = context_module::instance($cm->id);
$PAGE->set_url('/mod/coder/view.php', ['id' => $id]);
$PAGE->set_title($cm->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);
$PAGE->requires->css(new moodle_url('/mod/coder/style.css'));

$instance = $DB->get_record('coder', ['id' => $cm->instance], '*', MUST_EXIST);

$fs = get_file_storage();
$headerimageurl = '';
$file = $fs->get_file($context->id, 'mod_coder', 'headerimage', $instance->id, '/', 'headerimage');
$files = $fs->get_area_files($context->id, 'mod_coder', 'headerimage', $instance->id, false);
foreach ($files as $file) {
    if (!$file->is_directory()) {
        $headerimageurl = moodle_url::make_pluginfile_url(
            $context->id,
            'mod_coder',
            'headerimage',
            $instance->id,
            $file->get_filepath(),
            $file->get_filename()
        );
        break;
    }
}

$bericht = format_text($instance->welkomstbericht, FORMAT_HTML);
$bericht = str_replace('{{naam}}', fullname($USER), $bericht);

echo $OUTPUT->header();
?>
<div id="main-container">
    <div id="header-container">
        <?php if (!empty($headerimageurl)) { ?>
            <img id="header-image" src="<?php echo $headerimageurl; ?>" alt="Header Afbeelding" />
        <?php } ?>
        <div id="header-text">

            <h1><?php echo format_string($instance->pagetitle); ?></h1>
            <div id="difficulty-labels">
                <?php if ($instance->showexpert): ?><span class="label expert">Expert developer</span><?php endif; ?>
                <?php if ($instance->showskilled): ?><span class="label skilled">Skilled developer</span><?php endif; ?>
                <?php if ($instance->showaspiring): ?><span class="label aspiring">Aspiring developer</span><?php endif; ?>
            </div>
            <h3>Opdrachtbeschrijving</h3>
              <p id="assignment-description">
              <?php echo $bericht; ?>
            </p>
            <h3>Console voorbeeld</h3>
            <div class="terminal-container">
                <div id="output-example" class="terminal">
                    <?php echo $instance->outputexample; ?>
                </div>
            </div>
        </div>
    </div>
    <div id="button-container">
        <button class="normal" onclick="toggleTerminal()">Ervaar hoe de applicatie moet werken</button>
        <button class="normal" onclick="openSubmissionModal()">Lever je opdracht in</button>
    </div>
    <div class="terminal-container">
        <div id="toolbar" style="display: none;">
            <button id="runButton" class="icon" style="display: none;" onclick="runPythonCode()">
                <img src="<?php echo new moodle_url('/mod/coder/icon_run.svg'); ?>" alt="Run Icon">
            </button>
            <button id="clearButton" class="icon" style="display: none;" onclick="clearTerminal()">
                <img src="<?php echo new moodle_url('/mod/coder/icon_clear.svg'); ?>" alt="Clear Icon">
            </button>
        </div>
        <div class="terminal" id="terminal"></div>
    </div>
</div>
<div id="inputModal">
    <p id="msg">Voer een waarde in:</p>
    <input type="text" id="userInput">
    <button class="normal" onclick="submitInput()">OK</button>
</div>
<div id="submissionModal">
    <div class="modal-content">
        <button class="cancelButton" onclick="closeSubmissionModal()">Cancel</button>
        <iframe id="submissionIframe" src="<?php echo $instance->submissionurl; ?>"></iframe>
    </div>
</div>
<div id="alertModal">
    <p id="alertMessage"></p>
    <button class="normal" onclick="closeAlertModal()">OK</button>
</div>
<div id="helpModal">
    <h2>Help</h2>
    <p>Gebruik de knoppen om de opdracht uit te voeren en volg de instructies op het scherm.</p>
    <button class="normal" onclick="closeHelpModal()">Sluiten</button>
</div>
<script src="https://cdn.jsdelivr.net/pyodide/v0.23.4/full/pyodide.js"></script>
<script>
let inputResolver, pyodide;
const terminal = document.getElementById("terminal");
const msg_modal = document.getElementById("msg");
const inputModal = document.getElementById("inputModal");
const userInputEl = document.getElementById("userInput");
const runButton = document.getElementById("runButton");
const clearButton = document.getElementById("clearButton");
const toolbar = document.getElementById("toolbar");

function preprocess_code(code) {
    if (typeof code !== 'string' || !code) return "";
    return code.replace('input(', ' await input(').replace('(input(', ' (await input(');
}

function clearTerminal() {
    terminal.innerHTML = "";
}

async function loadPyodideAndSetup() {
    pyodide = await loadPyodide();
    terminal.innerHTML += `C:/<?php echo $instance->applicatie_naam; ?>/main.py\n`;
    pyodide.globals.set('input', s => getInputFromUser(s));
    pyodide.globals.set('print', s => {
        terminal.innerHTML += `${s}\n`;
    });
}

async function runPythonCode() {
    terminal.innerHTML += `python C:/<?php echo $instance->applicatie_naam; ?>/main.py\n`;
    if (!pyodide) {
        terminal.innerHTML += ` <?php echo $instance->applicatie_naam; ?> is nog niet geladen...\n`;
        return;
    }
    try {
        await pyodide.runPythonAsync(preprocess_code(<?php echo json_encode($instance->pythoncode); ?>));
    } catch (error) {
        terminal.innerHTML += "Fout: " + error + "\n";
    }
}

function getInputFromUser(promptText) {
    terminal.innerHTML += `> ${promptText}`;
    msg_modal.innerHTML = promptText;
    inputModal.style.display = "block";
    document.getElementById("overlay").style.display = "block";
    return new Promise((resolve) => {
        inputResolver = resolve;
        userInputEl.focus();
    });
}

function submitInput() {
    inputModal.style.display = "none";
    document.getElementById("overlay").style.display = "none";
    const inputValue = userInputEl.value;
    terminal.innerHTML += `<i>${inputValue}\n</i>`;
    userInputEl.value = "";
    inputResolver(inputValue);
}

userInputEl.addEventListener("keydown", (event) => {
    if (event.key === "Enter") {
        submitInput();
    }
});

function toggleTerminal() {
    const isVisible = window.getComputedStyle(terminal).display !== "none";
    terminal.style.display = isVisible ? "none" : "block";
    toolbar.style.display = isVisible ? "none" : "flex";
    runButton.style.display = isVisible ? "none" : "inline-block";
    clearButton.style.display = isVisible ? "none" : "inline-block";
}

function openSubmissionModal() {
    document.getElementById("submissionModal").style.display = "flex";
    document.getElementById("overlay").style.display = "block";
}

function closeSubmissionModal() {
    document.getElementById("submissionModal").style.display = "none";
    document.getElementById("overlay").style.display = "none";
}

function openAlertModal(message) {
    document.getElementById("alertMessage").innerText = message;
    document.getElementById("alertModal").style.display = "block";
    document.getElementById("overlay").style.display = "block";
}

function closeAlertModal() {
    document.getElementById("alertModal").style.display = "none";
    document.getElementById("overlay").style.display = "none";
}

function openHelpModal() {
    document.getElementById("helpModal").style.display = "block";
    document.getElementById("overlay").style.display = "block";
}

function closeHelpModal() {
    document.getElementById("helpModal").style.display = "none";
    document.getElementById("overlay").style.display = "none";
}

window.onload = () => {
    console.log("runButton found:", !!document.getElementById("runButton"));
    console.log("clearButton found:", !!document.getElementById("clearButton"));
    console.log("toolbar found:", !!document.getElementById("toolbar"));

    loadPyodideAndSetup().then(() => {
        document.getElementById("main-container").style.display = "block";
    });
};
</script>
<?php
echo $OUTPUT->footer();
