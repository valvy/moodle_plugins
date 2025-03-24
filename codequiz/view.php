<?php
require_once('../../config.php');

$id = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'codequiz');
require_login($course, true, $cm);

$PAGE->set_url('/mod/codequiz/view.php', ['id' => $id]);
$PAGE->set_title($cm->name);
$PAGE->set_heading($course->fullname);
// Gebruik een layout die de Moodle toolbars en navigatie toont:
$PAGE->set_pagelayout('incourse');

global $DB, $USER;
$instance = $DB->get_record('codequiz', ['id' => $cm->instance], '*', MUST_EXIST);
$bericht = format_text($instance->welkomstbericht, FORMAT_HTML);
$bericht = str_replace('{{naam}}', fullname($USER), $bericht);

echo $OUTPUT->header();
echo $bericht;
?>

<div class="codequiz-wrapper">
  <style>
    /* Scope de aangepaste quiz-stijlen binnen de wrapper */
    .codequiz-wrapper {
      background-color: #111;
      color: #e0e0e0;
      font-family: 'Courier New', Courier, monospace;
      font-size: 18px;
      padding: 20px;
      border-radius: 5px;
      margin: 20px auto;
      max-width: 1200px;
    }
    .codequiz-wrapper .quiz-container {
      position: relative;
      width: 100%;
      height: calc(100vh - 200px);
      margin-top: 20px;
    }
    .codequiz-wrapper .screen {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      transition: transform 0.5s ease;
    }
    .codequiz-wrapper .content-wrapper {
      display: flex;
      width: 100%;
      height: 100%;
    }
    .codequiz-wrapper .question-container {
      width: 50%;
      padding: 20px;
      box-sizing: border-box;
      padding-top: 25vh;
    }
    .codequiz-wrapper .question-container h2,
    .codequiz-wrapper .question-container p,
    .codequiz-wrapper .options-container,
    .codequiz-wrapper .option label {
      animation: fadeIn 0.5s ease-in;
      font-size: 1.2em;
    }
    .codequiz-wrapper .media-container {
      width: 50%;
      padding: 20px;
      box-sizing: border-box;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .codequiz-wrapper .media-container img {
      height: 100%;
      width: auto;
      object-fit: cover;
      display: block;
      margin: 0 auto;
    }
    .codequiz-wrapper .media-container.no-crop img {
      height: auto;
      max-height: 100%;
      width: auto;
      object-fit: contain;
    }
    .codequiz-wrapper .media-container.no-crop.last img {
      max-height: 20%;
    }
    .codequiz-wrapper .video-container {
      position: relative;
      width: 100%;
      height: 0;
      padding-bottom: 56.25%; /* 16:9 ratio */
      overflow: hidden;
    }
    .codequiz-wrapper .video-container iframe {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      border: 0;
    }
    .codequiz-wrapper .options-container {
      margin-top: 20px;
    }
    .codequiz-wrapper .option {
      margin-bottom: 10px;
    }
    .codequiz-wrapper .final-screen {
      position: relative;
      width: 100%;
      height: 100%;
      overflow: hidden;
    }
    .codequiz-wrapper #matrix {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 0;
      background-color: #000;
    }
    .codequiz-wrapper .final-content {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 80%;
      height: 80%;
      padding: 20px;
      background-color: #000;
      text-align: center;
      z-index: 1;
      font-size: 1.3em;
      overflow-y: auto;
    }
    /* Nav-knoppen binnen de quiz-container positioneren */
    .codequiz-wrapper .nav-buttons {
      position: absolute;
      bottom: 20px;
      width: 100%;
      text-align: center;
      z-index: 2;
    }
    .codequiz-wrapper .nav-buttons button {
      background-color: #333;
      color: #e0e0e0;
      border: none;
      padding: 10px 20px;
      margin: 0 10px;
      cursor: pointer;
      font-size: 18px;
      transition: background-color 0.3s;
    }
    .codequiz-wrapper .nav-buttons button:hover:not(:disabled) {
      background-color: #555;
    }
    .codequiz-wrapper .nav-buttons button:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
  </style>

  <div class="quiz-container" id="quiz-container">
    <!-- Dynamisch opgebouwde schermen komen hier -->
    <?php
    // De nav-buttons worden hier als laatste element binnen de quiz-container ingevoegd.
    // Dit zorgt ervoor dat alle schermen (inclusief het scorescherm) vóór de knoppen komen.
    ?>
    <div class="nav-buttons">
      <button id="prevBtn">Vorige</button>
      <button id="nextBtn" disabled>Volgende</button>
    </div>
  </div>

  <script>
    class Scherm {
      constructor(vraag, mediaHTML, opties, crop = true) {
        this.vraag = vraag;
        this.mediaHTML = mediaHTML;
        this.opties = opties;
        this.crop = crop;
      }
    }

    const vraagSchermen = [
      new Scherm(
        "Hoe moeilijk vond je de vorige studietaken van probleem oplossend programmeren?",
        `<img src="https://www.metronieuws.nl/wp-content/uploads/2019/05/3de9ad0635a374f1914f1af1c7c37843.jpg" alt="Afbeelding 1">`,
        [
          { text: "Makkelijk", value: 2 },
          { text: "Niet makkelijk/moeilijk", value: 1 },
          { text: "Moeilijk", value: 0 }
        ]
      ),
      new Scherm(
        "Heb je voor de vorige opdrachten veel gebruik moeten maken van LLM's zoals chatGPT?",
        `<div class="video-container">
           <iframe src="https://www.youtube.com/embed/BBaJttSjAHk" allowfullscreen></iframe>
         </div>`,
        [
          { text: "Veel", value: -2 },
          { text: "Af en toe", value: 0 },
          { text: "weinig", value: 1 }
        ]
      ),
      new Scherm(
        "Heb je al eerder met lussen (zoals for-loops, while) gewerkt op een eerdere opleiding?",
        `<img src="https://www.lotus-qa.com/wp-content/uploads/2020/02/testing.jpg" alt="Afbeelding 3">`,
        [
          { text: "nee,", value: 0 },
          { text: "een beetje", value: 1 },
          { text: "Ik kan lussen al gebruiken", value: 2 }
        ]
      ),
      new Scherm(
        "Begrijp je de volgende code zonder gebruik te maken van een LLM (zoals chatgpt)?",
        `<img src="https://allinpython.com/wp-content/uploads/2022/07/for-loop-syntax-1024x409.png" alt="For loop syntax">`,
        [
          { text: "Nee,", value: 0 },
          { text: "Een beetje", value: 1 },
          { text: "Ja", value: 2 }
        ],
        false
      )
    ];

    // Maak het scorescherm (finalScreen)
    const finalScreen = document.createElement('div');
    finalScreen.classList.add('screen', 'final-screen');
    finalScreen.style.display = 'none';
    finalScreen.innerHTML = `
      <canvas id="matrix"></canvas>
      <div class="final-content">
        <h2>Aanbeveling</h2>
        <p id="result-text">Uit de test blijkt dat de opdrachten met de volgende labels het best bij je passen:</p>
        <div id="labels-container"></div>
        <p id="final-message"></p>
      </div>`;

    // Voeg de vraag-schermen toe vóór de nav-buttons
    const quizContainer = document.getElementById('quiz-container');
    vraagSchermen.forEach((scherm, index) => {
      const div = document.createElement('div');
      div.classList.add('screen');
      div.style.display = index === 0 ? 'block' : 'none';
      div.style.transform = index === 0 ? 'translateX(0)' : '';

      let optiesHTML = `<div class="options-container">`;
      scherm.opties.forEach(optie => {
        optiesHTML += `
          <div class="option">
            <input type="radio" id="q${index}-option${optie.value}" name="question${index}" value="${optie.value}">
            <label for="q${index}-option${optie.value}">${optie.text}</label>
          </div>`;
      });
      optiesHTML += `</div>`;

      const mediaClass = scherm.crop
        ? 'media-container'
        : 'media-container no-crop' + (index === vraagSchermen.length - 1 ? ' last' : '');

      div.innerHTML = `
        <div class="content-wrapper">
          <div class="question-container">
            <h2>Vraag ${index + 1}</h2>
            <p>${scherm.vraag}</p>
            ${optiesHTML}
          </div>
          <div class="${mediaClass}">
            ${scherm.mediaHTML}
          </div>
        </div>`;
      quizContainer.insertBefore(div, quizContainer.querySelector('.nav-buttons'));
    });

    // Voeg nu het scorescherm ook vóór de nav-buttons toe
    quizContainer.insertBefore(finalScreen, quizContainer.querySelector('.nav-buttons'));

    const screens = document.querySelectorAll('.screen');
    let currentScreen = 0;
    const nextBtn = document.getElementById('nextBtn');
    const prevBtn = document.getElementById('prevBtn');

    function updateNavButtons() {
      if (currentScreen === 0) {
        prevBtn.style.display = 'none';
      } else {
        prevBtn.style.display = 'inline-block';
      }
      if (currentScreen === screens.length - 1) {
        prevBtn.textContent = "maak de quiz opnieuw";
        prevBtn.style.backgroundColor = 'red';
        nextBtn.style.display = 'none';
      } else {
        prevBtn.textContent = "Vorige";
        prevBtn.style.backgroundColor = '#333';
        nextBtn.style.display = 'inline-block';
      }
    }

    function updateNextButtonState() {
      if (currentScreen >= vraagSchermen.length) {
        nextBtn.disabled = true;
        return;
      }
      const selected = document.querySelector(`input[name="question${currentScreen}"]:checked`);
      nextBtn.disabled = !selected;
    }

    document.addEventListener('change', (e) => {
      if (e.target.matches('input[type="radio"]')) {
        updateNextButtonState();
      }
    });

    function updateScore() {
      let totalScore = 0;
      for (let i = 0; i < vraagSchermen.length; i++) {
        const selected = document.querySelector(`input[name="question${i}"]:checked`);
        if (selected) {
          totalScore += parseInt(selected.value, 10);
        }
      }

      const labelsContainer = document.getElementById('labels-container');
      labelsContainer.innerHTML = '';

      let hasExpert = false, hasSkilled = false, hasAspiring = false;
      if (totalScore > 5) hasExpert = true;
      if (totalScore >= 3 && totalScore <= 6) hasSkilled = true;
      if (totalScore >= -100 && totalScore <= 4) hasAspiring = true;

      const labels = [];
      if (hasExpert) {
        labels.push('expert developer');
        labelsContainer.innerHTML += `<span style="background:red;color:white;padding:5px 10px;margin-right:5px;border-radius:5px;">expert developer</span>`;
      }
      if (hasSkilled) {
        labels.push('skilled developer');
        labelsContainer.innerHTML += `<span style="background:green;color:white;padding:5px 10px;margin-right:5px;border-radius:5px;">skilled developer</span>`;
      }
      if (hasAspiring) {
        labels.push('aspiring developer');
        labelsContainer.innerHTML += `<span style="background:blue;color:white;padding:5px 10px;margin-right:5px;border-radius:5px;">aspiring developer</span>`;
      }

      const resultTextElement = document.getElementById('result-text');
      resultTextElement.textContent = labels.length === 1
        ? "Uit de test blijkt dat de opdrachten met de volgende label het best bij je past:"
        : "Uit de test blijkt dat de opdrachten met de volgende labels het best bij je passen:";

      let finalMessageText = "";
      if (hasAspiring && !hasSkilled && !hasExpert) {
        finalMessageText = "Aangezien je profiel de kenmerken van een aspiring developer toont, wordt aanbevolen om de aspiring developer opdrachten te maken. Deze opdrachten zijn uitdagend maar speciaal bedoeld voor beginnende programmeurs. Expert developer opdrachten wordt afgeraden.";
      } else if (hasSkilled && !hasAspiring && !hasExpert) {
        finalMessageText = "Je resultaat duidt op vaardigheden die passen bij een skilled developer. Je kunt beginnen met de opdrachten voor een skilled developer. Je mag eventueel de expert developer opdrachten bekijken, maar investeer hier niet te veel tijd in.";
      } else if (hasExpert && !hasSkilled && !hasAspiring) {
        finalMessageText = "Je profiel laat zien dat je een expert developer bent. We adviseren je daarom om uitsluitend de meest uitdagende, expert-level opdrachten te doen.";
      } else if (hasSkilled && hasExpert && !hasAspiring) {
        finalMessageText = "Je resultaat toont zowel de solide basis van een skilled developer als de complexiteit van een expert developer. Begin met de opdrachten voor skilled developers om je fundament te versterken, en als je merkt dat je deze beheerst, kun je geleidelijk de meest uitdagende expert-level opdrachten oppakken. Zorg dat je de basis eerst goed op orde hebt voordat je overgaat op de hoogste uitdagingen.";
      } else {
        finalMessageText = "Je resultaat toont een genuanceerd profiel met meerdere aspecten. ";
        if (hasAspiring) {
          finalMessageText += "De aspiring developer opdrachten bieden een goede basis voor beginnende programmeurs, ";
        }
        if (hasSkilled) {
          finalMessageText += "terwijl de skilled developer opdrachten je vaardigheden verder ontwikkelen. ";
        }
        if (hasExpert) {
          finalMessageText += "Expert developer opdrachten zijn zeer uitdagend; doe deze alleen als je zeker bent van je vaardigheden. ";
        }
        finalMessageText += "Kijk welke opdrachten het beste bij jouw combinatie passen en werk stap voor stap aan je ontwikkeling.";
      }

      document.getElementById('final-message').textContent = finalMessageText;

      // Opslaan in localStorage
      const resultData = {
        labels: labels,
        message: finalMessageText
      };
      localStorage.setItem('quizResult', JSON.stringify(resultData));
    }

    function animateTransition(newIndex, direction) {
      const current = screens[currentScreen];
      const target = screens[newIndex];
      target.style.display = 'block';

      if (direction === 'next') {
        target.style.transform = 'translateX(100%)';
        target.offsetWidth;
        current.style.transform = 'translateX(-100%)';
        target.style.transform = 'translateX(0)';
      } else if (direction === 'prev') {
        target.style.transform = 'translateX(-100%)';
        target.offsetWidth;
        current.style.transform = 'translateX(100%)';
        target.style.transform = 'translateX(0)';
      }

      current.addEventListener('transitionend', function handler(event) {
        if (event.propertyName !== 'transform') return;
        current.style.display = 'none';
        current.style.transform = '';
        target.style.transform = '';
        current.removeEventListener('transitionend', handler);
      });

      currentScreen = newIndex;
      if (currentScreen === screens.length - 1) {
        updateScore();
        startMatrix();
      }
      updateNextButtonState();
      updateNavButtons();
    }

    function resetQuiz() {
      localStorage.removeItem('quizResult');
      const inputs = document.querySelectorAll('input[type="radio"]');
      inputs.forEach(input => input.checked = false);
      screens[currentScreen].style.display = 'none';
      currentScreen = 0;
      screens[0].style.display = 'block';
      screens[0].style.transform = 'translateX(0)';
      updateNextButtonState();
      updateNavButtons();
    }

    nextBtn.addEventListener('click', () => {
      if (currentScreen < vraagSchermen.length) {
        const selected = document.querySelector(`input[name="question${currentScreen}"]:checked`);
        if (!selected) return;
      }
      if (currentScreen < screens.length - 1) {
        animateTransition(currentScreen + 1, 'next');
      }
    });

    prevBtn.addEventListener('click', () => {
      if (currentScreen === screens.length - 1) {
        resetQuiz();
      } else if (currentScreen > 0) {
        animateTransition(currentScreen - 1, 'prev');
      }
    });

    // Check voor eerder opgeslagen resultaat (bij herladen)
    function checkStoredResult() {
      const stored = localStorage.getItem('quizResult');
      if (stored) {
        const data = JSON.parse(stored);
        const labelsContainer = document.getElementById('labels-container');
        const resultTextElement = document.getElementById('result-text');
        const finalMessage = document.getElementById('final-message');

        labelsContainer.innerHTML = '';
        data.labels.forEach(label => {
          const span = document.createElement('span');
          span.textContent = label;
          span.style.backgroundColor = label.includes('expert') ? 'red' : label.includes('skilled') ? 'green' : 'blue';
          span.style.color = 'white';
          span.style.padding = '5px 10px';
          span.style.marginRight = '5px';
          span.style.borderRadius = '5px';
          labelsContainer.appendChild(span);
        });

        resultTextElement.textContent = data.labels.length === 1
          ? "Uit de test blijkt dat de opdrachten met de volgende label het best bij je past:"
          : "Uit de test blijkt dat de opdrachten met de volgende labels het best bij je passen:";

        finalMessage.textContent = data.message;

        screens.forEach(s => s.style.display = 'none');
        currentScreen = screens.length - 1;
        screens[currentScreen].style.display = 'block';
        startMatrix();
        updateNavButtons();
      }
    }

    function startMatrix() {
      const canvas = document.getElementById("matrix");
      const ctx = canvas.getContext("2d");
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;
      const words = ["print", "python", "for", "if", "class", "input", "import", "return"];
      const fontSize = 16;
      const columnWidth = 45;
      const columns = Math.floor(canvas.width / columnWidth);
      const drops = Array.from({ length: columns }, () => Math.floor(Math.random() * canvas.height / fontSize));

      function draw() {
        ctx.fillStyle = "rgba(0, 0, 0, 0.05)";
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = "#0F0";
        ctx.font = fontSize + "px monospace";
        for (let i = 0; i < drops.length; i++) {
          const text = words[Math.floor(Math.random() * words.length)];
          ctx.fillText(text, i * columnWidth, drops[i] * fontSize);
          if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
            drops[i] = 0;
          }
          drops[i]++;
        }
      }
      setInterval(draw, 66);
    }

    updateNextButtonState();
    updateNavButtons();
    checkStoredResult();
  </script>
</div>

<?php
echo $OUTPUT->footer();
?>
