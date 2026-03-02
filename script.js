(function () {
  var body = document.body;
  var toggleButton = document.querySelector("[data-theme-toggle]");

  function applyTheme(theme) {
    body.setAttribute("data-theme", theme);

    if (!toggleButton) {
      return;
    }

    if (theme === "dark") {
      toggleButton.textContent = "Light mode";
      toggleButton.setAttribute("aria-label", "Switch to light mode");
    } else {
      toggleButton.textContent = "Dark mode";
      toggleButton.setAttribute("aria-label", "Switch to dark mode");
    }
  }

  var savedTheme = localStorage.getItem("portfolio-theme");
  var initialTheme = savedTheme === "dark" ? "dark" : "light";
  applyTheme(initialTheme);

  if (toggleButton) {
    toggleButton.addEventListener("click", function () {
      var nextTheme = body.getAttribute("data-theme") === "dark" ? "light" : "dark";
      localStorage.setItem("portfolio-theme", nextTheme);
      applyTheme(nextTheme);
    });
  }

  var revealItems = document.querySelectorAll(".reveal");

  if ("IntersectionObserver" in window) {
    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.13 }
    );

    revealItems.forEach(function (item) {
      observer.observe(item);
    });
  } else {
    revealItems.forEach(function (item) {
      item.classList.add("is-visible");
    });
  }

  var lagosNodes = document.querySelectorAll("[data-lagos-time]");

  if (lagosNodes.length) {
    var formatter = new Intl.DateTimeFormat("en-NG", {
      timeZone: "Africa/Lagos",
      weekday: "short",
      year: "numeric",
      month: "short",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit"
    });

    var updateLagosTime = function () {
      var nowText = formatter.format(new Date());
      lagosNodes.forEach(function (node) {
        node.textContent = nowText;
      });
    };

    updateLagosTime();
    setInterval(updateLagosTime, 1000);
  }

  var form = document.querySelector("#contact-form");

  if (form) {
    form.addEventListener("submit", function (event) {
      event.preventDefault();

      var name = form.querySelector("[name='name']").value.trim();
      var email = form.querySelector("[name='email']").value.trim();
      var subjectInput = form.querySelector("[name='subject']").value.trim();
      var message = form.querySelector("[name='message']").value.trim();

      var subject = subjectInput || "Portfolio contact from " + (name || "Visitor");
      var bodyContent = [
        "Name: " + (name || "N/A"),
        "Email: " + (email || "N/A"),
        "",
        "Message:",
        message || "N/A"
      ].join("\n");

      var mailtoHref = "mailto:damola.olopade@gmail.com?subject=" + encodeURIComponent(subject) + "&body=" + encodeURIComponent(bodyContent);
      window.location.href = mailtoHref;
    });
  }
})();
