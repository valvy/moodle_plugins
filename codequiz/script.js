class Scherm {
  constructor(vraag, mediaHTML, opties, crop = true) {
    this.vraag = vraag;
    this.mediaHTML = mediaHTML;
    this.opties = opties;
    this.crop = crop;
  }
}

document.addEventListener("DOMContentLoaded", function () {
  const config = window.codequizConfig;
  const schermen = config.vraagSchermen.map(v => new Scherm(v.vraag, v.mediaHTML, v.opties, v.crop));

  const container = document.getElementById("quiz-container");
  const navButtons = document.querySelector(".nav-buttons");
  const prevBtn = document.getElementById("prevBtn");
  const nextBtn = document.getElementById("nextBtn");

  const screens = [];
  let currentScreen = 0;
  const answers = [];

  schermen.forEach((scherm, index) => {
    const screen = document.createElement("div");
    screen.classList.add("screen");
    if (index === 0) screen.classList.add("active");

    const wrapper = document.createElement("div");
    wrapper.classList.add("content-wrapper");

    const vraagDiv = document.createElement("div");
    vraagDiv.classList.add("question-container");
    vraagDiv.innerHTML = `<h2>Vraag ${index + 1}</h2><p>${scherm.vraag}</p>`;

    let optiesHTML = '<div class="options-container">';
    scherm.opties.forEach(optie => {
      optiesHTML += `
        <div class="option">
          <input type="radio" id="q${index}-opt${optie.value}" name="vraag${index}" value="${optie.value}">
          <label for="q${index}-opt${optie.value}">${optie.text}</label>
        </div>`;
    });
    optiesHTML += '</div>';
    vraagDiv.innerHTML += optiesHTML;

    const mediaDiv = document.createElement("div");
    mediaDiv.classList.add("media-container");
    if (!scherm.crop) mediaDiv.classList.add("no-crop");
    mediaDiv.innerHTML = scherm.mediaHTML;

    wrapper.appendChild(vraagDiv);
    wrapper.appendChild(mediaDiv);
    screen.appendChild(wrapper);
    container.insertBefore(screen, navButtons);
    screens.push(screen);
  });

  function showScreen(index, direction = 'next') {
    const outgoing = screens[currentScreen];
    const incoming = screens[index];

    incoming.style.transition = "none";
    incoming.style.transform = direction === 'next' ? "translateX(100%)" : "translateX(-100%)";
    incoming.style.opacity = "0";
    incoming.style.display = "block";

    requestAnimationFrame(() => {
      incoming.style.transition = "transform 0.4s ease, opacity 0.4s ease";
      outgoing.style.transition = "transform 0.4s ease, opacity 0.4s ease";

      incoming.classList.add("active");
      incoming.style.transform = "translateX(0)";
      incoming.style.opacity = "1";

      outgoing.style.transform = direction === 'next' ? "translateX(-100%)" : "translateX(100%)";
      outgoing.style.opacity = "0";

      setTimeout(() => {
        outgoing.classList.remove("active");
        outgoing.style.display = "none";
        outgoing.style.transform = "translateX(100%)";
      }, 400);
    });

    currentScreen = index;
  }

  function updateNavButtons() {
    prevBtn.style.display = currentScreen === 0 ? "none" : "inline-block";
    nextBtn.disabled = !document.querySelector(`input[name="vraag${currentScreen}"]:checked`);
    nextBtn.textContent = currentScreen === screens.length - 1 ? "Afronden" : "Volgende";
  }

  document.addEventListener("change", () => updateNavButtons());

  nextBtn.addEventListener("click", () => {
    const selected = document.querySelector(`input[name="vraag${currentScreen}"]:checked`);
    if (!selected) return;

    answers[currentScreen] = parseInt(selected.value);

    if (currentScreen < screens.length - 1) {
      showScreen(currentScreen + 1, 'next');
      updateNavButtons();
    } else {
      finishQuiz();
    }
  });

  prevBtn.addEventListener("click", () => {
    if (currentScreen > 0) {
      showScreen(currentScreen - 1, 'prev');
      updateNavButtons();
    }
  });

  function finishQuiz() {
  if (answers.length < schermen.length || answers.includes(undefined)) {
    console.error("Niet alle vragen zijn beantwoord.");
    return;
  }

  const total = answers.reduce((a, b) => a + b, 0);
  const labels = [];
  let message = "";

  const thresholds = window.codequizConfig.labelThresholds;

  if (total >= thresholds.expert) {
    labels.push("expert developer");
    message = "Je bent een expert developer. Ga aan de slag met de moeilijkste opdrachten.";
  } else if (total >= thresholds.skilled) {
    labels.push("skilled developer");
    message = "Je bent een skilled developer. Kies gemiddeld moeilijke opdrachten.";
  } else if (total >= thresholds.aspiring) {
    labels.push("aspiring developer");
    message = "Je bent een aspiring developer. Begin met de basistaken.";
  }

  // Stuur nu ook antwoorden mee
  const resultData = { labels, message, answers };

  saveResultToDB(resultData).then(() => {
    renderFinalScreen(labels, message);
  });
}


  function renderFinalScreen(labels, message) {
    navButtons.remove();

    container.innerHTML = `
      <canvas id="matrix"></canvas>
      <div class="final-content">
        <h2>Aanbeveling</h2>
        <p id="result-text"></p>
        <div id="labels-container"></div>
        <p id="final-message">${message}</p>
        <button id="resetBtn">Opnieuw maken</button>
      </div>`;

    const labelContainer = document.getElementById("labels-container");
    labels.forEach(label => {
      const span = document.createElement("span");
      span.textContent = label;
      span.style.backgroundColor = label.includes("expert") ? "red" : label.includes("skilled") ? "green" : "blue";
      span.style.color = "white";
      span.style.padding = "5px 10px";
      span.style.marginRight = "5px";
      span.style.borderRadius = "5px";
      labelContainer.appendChild(span);
    });

    document.getElementById("result-text").textContent = labels.length === 1
      ? "Uit de test blijkt dat de opdrachten met de volgende label het best bij je past:"
      : "Uit de test blijkt dat de opdrachten met de volgende labels het best bij je passen:";

    document.getElementById("resetBtn").addEventListener("click", () => {
      fetch("delete_result.php", {
        method: "POST",
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          courseid: config.courseid,
          instanceid: config.instanceid
        })
      }).then(res => res.json()).then(data => {
        if (data.status === 'success') {
          location.reload();
        } else {
          alert("Kon resultaat niet wissen.");
        }
      }).catch(err => {
        console.error("Fout bij resetten:", err);
      });
    });

    startMatrix();
  }

  function saveResultToDB(resultData) {
    return fetch('submit_result.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        courseid: config.courseid,
        instanceid: config.instanceid,
        labels: JSON.stringify(resultData.labels),
        message: resultData.message,
        answers: JSON.stringify(resultData.answers) // Nieuw toegevoegd
      })
    })
      .then(res => res.json())
      .then(data => {
        if (!data || data.status !== 'success') {
          console.error('Fout bij opslaan:', data);
        } else {
          console.log('Resultaat opgeslagen:', data);
        }
      })
      .catch(error => {
        console.error('Netwerkfout bij opslaan van resultaat:', error);
      });
  }

  function startMatrix() {
    const canvas = document.getElementById("matrix");
    const ctx = canvas.getContext("2d");
    canvas.width = container.offsetWidth;
    canvas.height = container.offsetHeight;
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

  updateNavButtons();

  if (config.storedResult) {
    renderFinalScreen(config.storedResult.labels, config.storedResult.message);
  }
});
