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
  if (config.storedResult) {
    renderFinalScreen(config.storedResult.labels, config.storedResult.message);
    return;
  }
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
    const thresholds = config.labelThresholds;
    let level, message;

    if (total >= parseInt(thresholds.expert)) {
      level = "expert";
    } else if (total >= parseInt(thresholds.skilled)) {
      level = "skilled";
    } else {
      level = "aspiring";
    }

    message = config.messages[level].replace(/{{naam}}/g, config.userFirstName);
    const resultData = { labels: [level + " developer"], message, answers };

    saveResultToDB(resultData).then(() => {
      renderFinalScreen(resultData.labels, message);
    });
  }

  function renderFinalScreen(labels, message) {
    navButtons.remove();

    container.innerHTML = `
      <div class="final-content">
        <h2>Aanbeveling</h2>
        <p id="final-message">${message}</p>
        <button id="resetBtn">Opnieuw maken</button>
      </div>`;

    document.getElementById("resetBtn").addEventListener("click", () => {
      fetch("delete_result.php", {
        method: "POST",
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          courseid: config.courseid,
          instanceid: config.instanceid
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          location.reload();
        } else {
          alert("Kon resultaat niet wissen.");
        }
      })
      .catch(err => {
        console.error("Fout bij resetten:", err);
      });
    });
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
        answers: JSON.stringify(resultData.answers)
      })
    }).then(res => res.json());
  }

  updateNavButtons();
});