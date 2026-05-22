(function () {
  var body = document.body;
  var toggleButton = document.querySelector("[data-theme-toggle]");
  var topbarInner = document.querySelector(".topbar-inner");
  var mainNav = document.querySelector(".main-nav");
  var topbarActions = document.querySelector(".topbar-actions");
  var navDrawer = null;
  var navToggle = null;
  var navOverlay = null;
  var lastFocusedElement = null;
  var mobileNavQuery = window.matchMedia("(max-width: 1020px)");

  if (topbarInner && mainNav && topbarActions) {
    navDrawer = document.createElement("div");
    navDrawer.className = "nav-drawer";
    navDrawer.id = "site-drawer";

    topbarInner.insertBefore(navDrawer, mainNav);
    navDrawer.appendChild(mainNav);
    navDrawer.appendChild(topbarActions);

    navToggle = document.createElement("button");
    navToggle.className = "nav-toggle";
    navToggle.type = "button";
    navToggle.setAttribute("aria-controls", "site-drawer");
    navToggle.setAttribute("aria-expanded", "false");
    navToggle.setAttribute("aria-label", "Open navigation menu");
    navToggle.innerHTML = "<span></span><span></span><span></span>";
    topbarInner.insertBefore(navToggle, navDrawer);

    navOverlay = document.createElement("button");
    navOverlay.className = "nav-overlay";
    navOverlay.type = "button";
    navOverlay.setAttribute("aria-label", "Close navigation menu");
    navOverlay.setAttribute("aria-hidden", "true");
    document.body.appendChild(navOverlay);
  }

  function getDrawerFocusTarget() {
    if (!navDrawer) {
      return null;
    }

    return navDrawer.querySelector("a, button, [href], input, select, textarea, [tabindex]:not([tabindex='-1'])");
  }

  function setNavState(isOpen) {
    if (!navToggle || !navOverlay || !navDrawer) {
      return;
    }

    var mobileView = mobileNavQuery.matches;
    var shouldOpen = mobileView && isOpen;

    body.classList.toggle("nav-open", shouldOpen);
    navToggle.setAttribute("aria-expanded", String(shouldOpen));
    navToggle.setAttribute("aria-label", shouldOpen ? "Close navigation menu" : "Open navigation menu");
    navDrawer.setAttribute("aria-hidden", String(mobileView && !shouldOpen));
    navOverlay.setAttribute("aria-hidden", String(!shouldOpen));

    if (mobileView && !shouldOpen) {
      navDrawer.setAttribute("inert", "");
    } else {
      navDrawer.removeAttribute("inert");
    }

    if (shouldOpen) {
      lastFocusedElement = document.activeElement;
      window.setTimeout(function () {
        var focusTarget = getDrawerFocusTarget();
        if (focusTarget) {
          focusTarget.focus();
        }
      }, 40);
    } else if (lastFocusedElement && typeof lastFocusedElement.focus === "function" && document.contains(lastFocusedElement)) {
      lastFocusedElement.focus();
      lastFocusedElement = null;
    }
  }

  function syncNavForViewport() {
    if (!mobileNavQuery.matches) {
      setNavState(false);
    }
  }

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

  var savedTheme = localStorage.getItem("portfolio-theme-v2");
  var initialTheme = savedTheme === "light" ? "light" : "dark";
  applyTheme(initialTheme);

  if (toggleButton) {
    toggleButton.addEventListener("click", function () {
      var nextTheme = body.getAttribute("data-theme") === "dark" ? "light" : "dark";
      localStorage.setItem("portfolio-theme-v2", nextTheme);
      applyTheme(nextTheme);
    });
  }

  if (navToggle && navOverlay) {
    navToggle.addEventListener("click", function () {
      if (!mobileNavQuery.matches) {
        return;
      }

      setNavState(!body.classList.contains("nav-open"));
    });

    navOverlay.addEventListener("click", function () {
      setNavState(false);
    });

    mainNav.querySelectorAll("a").forEach(function (link) {
      link.addEventListener("click", function () {
        setNavState(false);
      });
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        setNavState(false);
      }
    });

    if (typeof mobileNavQuery.addEventListener === "function") {
      mobileNavQuery.addEventListener("change", syncNavForViewport);
    } else if (typeof mobileNavQuery.addListener === "function") {
      mobileNavQuery.addListener(syncNavForViewport);
    }

    syncNavForViewport();
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
