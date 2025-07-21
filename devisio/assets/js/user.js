// JavaScript pour les pages utilisateur

document.addEventListener("DOMContentLoaded", function () {
  // Auto-hide des messages flash après 5 secondes
  setTimeout(function () {
    const alerts = document.querySelectorAll(".alert");
    alerts.forEach(function (alert) {
      alert.style.transition = "opacity 0.5s ease-out";
      alert.style.opacity = "0";
      setTimeout(function () {
        if (alert.parentNode) {
          alert.parentNode.removeChild(alert);
        }
      }, 500);
    });
  }, 5000);

  // Animation des barres de progression
  const progressBars = document.querySelectorAll(".progress-bar");
  progressBars.forEach(function (bar) {
    const width = bar.style.width;
    bar.style.width = "0%";
    setTimeout(function () {
      bar.style.width = width;
    }, 100);
  });

  // Confirmation pour les actions de suppression
  const deleteButtons = document.querySelectorAll("[data-confirm]");
  deleteButtons.forEach(function (button) {
    button.addEventListener("click", function (e) {
      const message = button.getAttribute("data-confirm");
      if (!confirm(message)) {
        e.preventDefault();
      }
    });
  });

  // Toggle pour afficher/masquer le mot de passe
  const passwordToggles = document.querySelectorAll("[data-toggle-password]");
  passwordToggles.forEach(function (toggle) {
    toggle.addEventListener("click", function () {
      const target = document.querySelector(
        toggle.getAttribute("data-toggle-password")
      );
      if (target) {
        const type =
          target.getAttribute("type") === "password" ? "text" : "password";
        target.setAttribute("type", type);

        const icon = toggle.querySelector("i");
        if (icon) {
          icon.classList.toggle("fa-eye");
          icon.classList.toggle("fa-eye-slash");
        }
      }
    });
  });

  // Validation en temps réel des formulaires
  const forms = document.querySelectorAll("form[data-validate]");
  forms.forEach(function (form) {
    const inputs = form.querySelectorAll("input, textarea, select");

    inputs.forEach(function (input) {
      input.addEventListener("blur", function () {
        validateField(input);
      });

      input.addEventListener("input", function () {
        if (input.classList.contains("is-invalid")) {
          validateField(input);
        }
      });
    });

    form.addEventListener("submit", function (e) {
      let isValid = true;
      inputs.forEach(function (input) {
        if (!validateField(input)) {
          isValid = false;
        }
      });

      if (!isValid) {
        e.preventDefault();
      }
    });
  });

  function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = "";

    // Validation email
    if (field.type === "email" && value) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(value)) {
        isValid = false;
        errorMessage = "Format d'email invalide";
      }
    }

    // Validation mot de passe
    if (field.type === "password" && value) {
      if (value.length < 6) {
        isValid = false;
        errorMessage = "Le mot de passe doit contenir au moins 6 caractères";
      }
    }

    // Validation champs requis
    if (field.hasAttribute("required") && !value) {
      isValid = false;
      errorMessage = "Ce champ est requis";
    }

    // Appliquer les styles de validation
    if (isValid) {
      field.classList.remove("is-invalid");
      field.classList.add("is-valid");
    } else {
      field.classList.remove("is-valid");
      field.classList.add("is-invalid");
    }

    // Afficher/masquer le message d'erreur
    let errorElement = field.parentNode.querySelector(".form-error");
    if (errorMessage) {
      if (!errorElement) {
        errorElement = document.createElement("div");
        errorElement.className = "form-error";
        field.parentNode.appendChild(errorElement);
      }
      errorElement.textContent = errorMessage;
    } else if (errorElement) {
      errorElement.remove();
    }

    return isValid;
  }

  // Tooltips simples
  const tooltipElements = document.querySelectorAll("[data-tooltip]");
  tooltipElements.forEach(function (element) {
    element.addEventListener("mouseenter", function () {
      const tooltip = document.createElement("div");
      tooltip.className = "tooltip-popup";
      tooltip.textContent = element.getAttribute("data-tooltip");
      document.body.appendChild(tooltip);

      const rect = element.getBoundingClientRect();
      tooltip.style.position = "absolute";
      tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + "px";
      tooltip.style.left =
        rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + "px";
      tooltip.style.background = "#374151";
      tooltip.style.color = "white";
      tooltip.style.padding = "0.5rem";
      tooltip.style.borderRadius = "0.25rem";
      tooltip.style.fontSize = "0.75rem";
      tooltip.style.zIndex = "1000";
      tooltip.style.opacity = "0";
      tooltip.style.transition = "opacity 0.2s";

      setTimeout(function () {
        tooltip.style.opacity = "1";
      }, 10);

      element._tooltip = tooltip;
    });

    element.addEventListener("mouseleave", function () {
      if (element._tooltip) {
        element._tooltip.remove();
        element._tooltip = null;
      }
    });
  });

  // Animation smooth scroll pour les ancres
  document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener("click", function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute("href"));
      if (target) {
        target.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      }
    });
  });

  // Copie vers le presse-papier
  const copyButtons = document.querySelectorAll("[data-copy]");
  copyButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      const text = button.getAttribute("data-copy");
      navigator.clipboard.writeText(text).then(function () {
        // Feedback visuel
        const originalText = button.textContent;
        button.textContent = "Copié !";
        button.classList.add("btn-success");

        setTimeout(function () {
          button.textContent = originalText;
          button.classList.remove("btn-success");
        }, 2000);
      });
    });
  });

  // Gestion des onglets
  const tabButtons = document.querySelectorAll("[data-tab]");
  const tabContents = document.querySelectorAll("[data-tab-content]");

  tabButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      const tabId = button.getAttribute("data-tab");

      // Désactiver tous les onglets
      tabButtons.forEach(function (btn) {
        btn.classList.remove("active");
      });

      tabContents.forEach(function (content) {
        content.classList.add("hidden");
      });

      // Activer l'onglet cliqué
      button.classList.add("active");
      const targetContent = document.querySelector(
        '[data-tab-content="' + tabId + '"]'
      );
      if (targetContent) {
        targetContent.classList.remove("hidden");
      }
    });
  });

  // Auto-refresh des statistiques (toutes les 5 minutes)
  const statsContainers = document.querySelectorAll("[data-auto-refresh]");
  if (statsContainers.length > 0) {
    setInterval(function () {
      // Ici vous pourriez faire un appel AJAX pour mettre à jour les stats
      console.log("Auto-refresh des statistiques...");
    }, 5 * 60 * 1000); // 5 minutes
  }

  // Sauvegarde automatique des brouillons (si applicable)
  const autoSaveForms = document.querySelectorAll("[data-auto-save]");
  autoSaveForms.forEach(function (form) {
    const inputs = form.querySelectorAll("input, textarea, select");

    inputs.forEach(function (input) {
      input.addEventListener(
        "input",
        debounce(function () {
          saveFormData(form);
        }, 2000)
      );
    });
  });

  function saveFormData(form) {
    const formData = new FormData(form);
    const data = {};
    formData.forEach(function (value, key) {
      data[key] = value;
    });

    // Sauvegarder en localStorage
    const formId = form.getAttribute("data-auto-save");
    localStorage.setItem("form_" + formId, JSON.stringify(data));

    // Afficher un indicateur de sauvegarde
    showSaveIndicator();
  }

  function showSaveIndicator() {
    const indicator = document.getElementById("save-indicator");
    if (indicator) {
      indicator.style.opacity = "1";
      setTimeout(function () {
        indicator.style.opacity = "0";
      }, 2000);
    }
  }

  // Fonction helper pour debounce
  function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = function () {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  // Initialisation des graphiques (si Chart.js est disponible)
  if (typeof Chart !== "undefined") {
    const chartElements = document.querySelectorAll("[data-chart]");
    chartElements.forEach(function (element) {
      const chartType = element.getAttribute("data-chart");
      const chartData = JSON.parse(
        element.getAttribute("data-chart-data") || "{}"
      );

      new Chart(element, {
        type: chartType,
        data: chartData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          animation: {
            duration: 1000,
            easing: "easeOutQuart",
          },
        },
      });
    });
  }

  console.log("Scripts utilisateur initialisés");
});
