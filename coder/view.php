<?php
require_once('../../config.php');

$id = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'coder');
require_login($course, true, $cm);

$PAGE->set_url('/mod/coder/view.php', ['id' => $id]);
$PAGE->set_title($cm->name);
$PAGE->set_heading($course->fullname);

// Laad de externe CSS file
$PAGE->requires->css(new moodle_url('/mod/coder/style.css'));

echo $OUTPUT->header();

global $DB, $USER;
$instance = $DB->get_record('coder', ['id' => $cm->instance], '*', MUST_EXIST);

$bericht = format_text($instance->welkomstbericht, FORMAT_HTML);
$bericht = str_replace('{{naam}}', fullname($USER), $bericht);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title><?php echo s($instance->pagetitle); ?></title>
</head>
<body>
  <div id="overlay"></div>
  <div id="loader">
    <div class="spinner"></div>
  </div>

  <div id="main-container">
    <div id="header-container">
      <img id="header-image" src="https://cdn.nos.nl/image/2020/12/23/701694/2560x1440a.jpg" alt="Header Afbeelding"/>
      <div id="header-text">
        <h1 id="page-title"></h1>
        <div id="difficulty-labels">
          <span class="label expert" id="labelExpert">Expert developer</span>
          <span class="label skilled" id="labelSkilled">Skilled developer</span>
          <span class="label aspiring" id="labelAspiring">Aspiring developer</span>
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

    <!-- Knoppen -->
    <div id="button-container">
      <button class="normal" onclick="toggleTerminal()" title="Toon of verberg de terminal">Ervaar hoe de applicatie moet werken</button>
      <button class="normal" onclick="openSubmissionModal()" title="Lever je opdracht in">Lever je opdracht in codegrade</button>
    </div>
    <div class="terminal-container">
      <div id="toolbar" style="display: none;">
       <button id="runButton" class="icon" style="display: none;" onclick="runPythonCode()" title="Voer code uit">
          <img src="<?php echo new moodle_url('/mod/coder/icon_run.svg'); ?>" alt="Run Icon">
       </button>
       <button id="clearButton" class="icon" style="display: none;" onclick="clearTerminal()" title="Maak de terminal leeg">
          <img src="<?php echo new moodle_url('/mod/coder/icon_clear.svg'); ?>" alt="Clear Icon">
       </button>
      </div>
      <div class="terminal" id="terminal"></div>
    </div>
  </div>

  <!-- Input Modal -->
  <div id="inputModal">
    <p id="msg">Voer een waarde in:</p>
    <input type="text" id="userInput">
    <button class="normal" onclick="submitInput()">OK</button>
  </div>

  <!-- Submission Modal -->
  <div id="submissionModal">
    <div class="modal-content">
      <button class="cancelButton" onclick="closeSubmissionModal()">Cancel</button>
      <iframe id="submissionIframe" src="<?php echo $instance->submissionurl; ?>"></iframe>
    </div>
  </div>

  <!-- Alert Modal -->
  <div id="alertModal">
    <p id="alertMessage"></p>
    <button class="normal" onclick="closeAlertModal()">OK</button>
  </div>

  <!-- Help Modal -->
  <div id="helpModal">
    <h2>Help</h2>
    <p>Hier vind je informatie over hoe de applicatie werkt. Gebruik de knoppen om de opdracht uit te voeren en volg de instructies op het scherm.</p>
    <button class="normal" onclick="closeHelpModal()">Sluiten</button>
  </div>

  <script src="https://cdn.jsdelivr.net/pyodide/v0.23.4/full/pyodide.js"></script>
  <script>
    // Variabelen vanuit de database
    const pythonCode    = <?php echo json_encode($instance->pythoncode ?: ""); ?>;
    const showExpert    = <?php echo json_encode((bool)$instance->showexpert); ?>;
    const showSkilled   = <?php echo json_encode((bool)$instance->showskilled); ?>;
    const showAspiring  = <?php echo json_encode((bool)$instance->showaspiring); ?>;
    const applicatieNaam= <?php echo json_encode($instance->applicatie_naam); ?>;
    const pageTitle     = <?php echo json_encode($instance->pagetitle); ?>;
    const SUBMISSION_URL= <?php echo json_encode($instance->submissionurl); ?>;

    // Stel de pagina titel en de URL van de submission iframe in
    document.getElementById("page-title").innerText = pageTitle;
    document.getElementById("submissionIframe").src = SUBMISSION_URL;

    // Update de zichtbaarheid van de labels
    function updateLabels() {
      document.getElementById("labelExpert").style.display = showExpert ? "inline-block" : "none";
      document.getElementById("labelSkilled").style.display = showSkilled ? "inline-block" : "none";
      document.getElementById("labelAspiring").style.display = showAspiring ? "inline-block" : "none";
    }

    let inputResolver, pyodide;
    const terminal = document.getElementById("terminal");
    const msg_modal = document.getElementById("msg");
    const inputModal = document.getElementById("inputModal");
    const userInputEl = document.getElementById("userInput");
    const runButton = document.getElementById("runButton");
    const clearButton = document.getElementById("clearButton");
    const toolbar = document.getElementById("toolbar");

    function preprocess_code(code) {
      if (typeof code !== 'string' || !code) {
        return "";
      }
      return code.replace('input(', ' await input(').replace('(input(', ' (await input(');
    }

    function clearTerminal() {
      terminal.innerHTML = "";
    }

    async function loadPyodideAndSetup() {
      pyodide = await loadPyodide();
      terminal.innerHTML += `C:/${applicatieNaam}/main.py\n`;
      pyodide.globals.set('input', s => getInputFromUser(s));
      pyodide.globals.set('print', s => {
        terminal.innerHTML += `${s}\n`;
      });
    }

    async function runPythonCode() {
      terminal.innerHTML += `python C:/${applicatieNaam}/main.py\n`;
      if (!pyodide) {
        terminal.innerHTML += ` ${applicatieNaam} is nog niet geladen...\n`;
        return;
      }
      try {
        await pyodide.runPythonAsync(preprocess_code(pythonCode));
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
      if (isVisible) {
        terminal.style.display = "none";
        toolbar.style.display = "none";
        runButton.style.display = "none";
        clearButton.style.display = "none";
      } else {
        terminal.style.display = "block";
        toolbar.style.display = "flex";
        runButton.style.display = "inline-block";
        clearButton.style.display = "inline-block";
      }
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
      loadPyodideAndSetup().then(() => {
        document.getElementById("loader").style.display = "none";
        document.getElementById("main-container").style.display = "block";
        updateLabels();

        try {
          const raw = localStorage.getItem("quizResult");
          if (!raw) {
            openAlertModal("Je hebt de quiz niet gemaakt. Maak eerst de quiz voordat je deze oefening probeert.");
            return;
          }
          const parsed = JSON.parse(raw);
          if (!parsed.labels || !Array.isArray(parsed.labels)) {
            console.warn("quizResult bestaat, maar 'labels' ontbreekt of is geen array:", parsed);
            return;
          }
          const userLabels = parsed.labels.map(label => label.toLowerCase().trim());
          const visibleLabels = [];
          if (showExpert) visibleLabels.push("expert developer");
          if (showSkilled) visibleLabels.push("skilled developer");
          if (showAspiring) visibleLabels.push("aspiring developer");

          const getDifficultyLevel = (labels) => {
            let level = 0;
            for (let label of labels) {
              if (label === "aspiring developer") level = Math.max(level, 1);
              if (label === "skilled developer") level = Math.max(level, 2);
              if (label === "expert developer") level = Math.max(level, 3);
            }
            return level;
          };
          const levelToString = (level) => {
            if (level === 1) return "aspiring developer";
            if (level === 2) return "skilled developer";
            if (level === 3) return "expert developer";
            return "";
          };

          const userLevel = getDifficultyLevel(userLabels);
          const exerciseLevel = getDifficultyLevel(visibleLabels);
          if (userLevel > exerciseLevel) {
            openAlertModal("Op basis van de eerder gemaakte quiz (uitkomst " + levelToString(userLevel) + ") is deze oefening waarschijnlijk te makkelijk voor jou.");
          } else if (userLevel < exerciseLevel) {
            openAlertModal("Op basis van jouw profiel (uitkomst " + levelToString(userLevel) + ") is deze oefening waarschijnlijk te uitdagend voor jou.");
          }
        } catch (err) {
          console.error("Fout bij het verwerken van quizResult:", err);
        }
      });
    };
  </script>
  <?php
  echo $OUTPUT->footer();
  ?>
</body>
</html>
