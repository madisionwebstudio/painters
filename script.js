/* Roll n' Brush Painting — script.js (vanilla, no dependencies) */
(function () {
  "use strict";

  /* ---- Season banner (Wisconsin: exterior ~May–Oct, interior year-round) ---- */
  var banner = document.getElementById("season-banner");
  if (banner) {
    var m = new Date().getMonth(); // 0 = Jan
    var cta = ' <a href="#estimate">Get a free estimate</a>';
    var msg;
    if (m >= 4 && m <= 7) msg = "Exterior season is filling up — book your exterior project before the calendar fills.";
    else if (m === 8 || m === 9) msg = "Last weeks of exterior season — reserve a fall slot or lock in your spring exterior.";
    else msg = "Winter is interior season — [PLACEHOLDER: winter interior offer]. Spring exterior dates are open now.";
    banner.innerHTML = msg + cta;
  }

  /* ---- Mobile menu ---- */
  var toggle = document.querySelector(".menu-toggle");
  var mnav = document.getElementById("mobile-nav");
  if (toggle && mnav) {
    toggle.addEventListener("click", function () {
      var open = mnav.classList.toggle("is-open");
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
      toggle.setAttribute("aria-label", open ? "Close menu" : "Open menu");
    });
    mnav.addEventListener("click", function (e) {
      if (e.target.tagName === "A") { mnav.classList.remove("is-open"); toggle.setAttribute("aria-expanded", "false"); }
    });
  }

  /* ---- Before/after sliders (native range = keyboard + touch for free) ---- */
  document.querySelectorAll(".ba").forEach(function (ba) {
    var input = ba.querySelector('input[type="range"]');
    if (!input) return;
    function update() {
      ba.style.setProperty("--pos", input.value + "%");
      input.setAttribute("aria-valuetext", input.value + "% before photo shown");
    }
    input.addEventListener("input", update);
    update();
  });

  /* ---- Gallery filter ---- */
  var gallery = document.querySelector("[data-gallery]");
  if (gallery) {
    var buttons = document.querySelectorAll(".filter-btn");
    var count = document.querySelector("[data-gallery-count]");
    buttons.forEach(function (btn) {
      btn.addEventListener("click", function () {
        var f = btn.getAttribute("data-filter"), shown = 0;
        buttons.forEach(function (b) { b.setAttribute("aria-pressed", b === btn ? "true" : "false"); });
        gallery.querySelectorAll(".project").forEach(function (p) {
          var match = f === "all" || p.getAttribute("data-type") === f;
          p.hidden = !match;
          if (match) shown++;
        });
        if (count) count.textContent = "Showing " + shown + (shown === 1 ? " project" : " projects");
      });
    });
  }

  /* ---- Map facade: load Google Maps only on request (keeps Lighthouse fast) ---- */
  document.querySelectorAll("[data-map-src]").forEach(function (box) {
    var btn = box.querySelector(".map-load");
    if (!btn) return;
    btn.addEventListener("click", function () {
      var f = document.createElement("iframe");
      f.src = box.getAttribute("data-map-src");
      f.title = "Map of Roll n' Brush Painting, 5170 E Cheryl Pkwy, Fitchburg, WI 53711";
      f.loading = "lazy";
      f.referrerPolicy = "no-referrer-when-downgrade";
      f.setAttribute("allowfullscreen", "");
      box.innerHTML = "";
      box.appendChild(f);
    });
  });

  /* ---- Estimate form ---- */
  var MAX_FILES = 3, MAX_BYTES = 8 * 1024 * 1024;
  var OK_TYPES = ["image/jpeg", "image/png", "image/webp", "image/heic", "image/heif", ""];

  function setError(field, msg) {
    var wrap = field.closest(".field");
    if (!wrap) return;
    var el = wrap.querySelector(".field-error");
    wrap.classList.toggle("invalid", !!msg);
    field.setAttribute("aria-invalid", msg ? "true" : "false");
    if (el) el.textContent = msg || "";
  }

  function validate(form) {
    var ok = true, first = null;
    var name = form.elements.name, phone = form.elements.phone, email = form.elements.email;
    var photos = form.querySelector('input[type="file"]');
    function fail(f, msg) { setError(f, msg); ok = false; if (!first) first = f; }

    name.value.trim().length < 2 ? fail(name, "Please enter your name.") : setError(name, "");
    phone.value.replace(/\D/g, "").length < 10 ? fail(phone, "Please enter a phone number we can reach you at.") : setError(phone, "");
    /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email.value.trim()) ? setError(email, "") : fail(email, "Please enter a valid email address.");

    if (photos && photos.files.length) {
      var msg = "";
      if (photos.files.length > MAX_FILES) msg = "Please attach up to " + MAX_FILES + " photos.";
      Array.prototype.forEach.call(photos.files, function (file) {
        if (file.size > MAX_BYTES) msg = file.name + " is larger than 8 MB.";
        else if (OK_TYPES.indexOf(file.type) === -1) msg = file.name + " isn't a supported image type.";
      });
      msg ? fail(photos, msg) : setError(photos, "");
    } else if (photos) setError(photos, "");

    if (first) first.focus();
    return ok;
  }

  document.querySelectorAll("[data-estimate-form]").forEach(function (form) {
    var ts = form.querySelector('input[name="ts"]');
    if (ts) ts.value = String(Date.now());
    var status = form.querySelector(".form-status");
    var submit = form.querySelector('button[type="submit"]');

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      status.className = "form-status";
      status.textContent = "";
      if (!validate(form)) return;
      if (!window.fetch || !window.FormData) { form.submit(); return; }

      var label = submit.textContent;
      submit.disabled = true;
      submit.textContent = "Sending…";

      fetch(form.action, { method: "POST", body: new FormData(form), headers: { "X-Requested-With": "fetch" } })
        .then(function (r) { return r.json().catch(function () { return { ok: false }; }); })
        .then(function (data) {
          if (data && data.ok) {
            form.reset();
            if (ts) ts.value = String(Date.now());
            status.className = "form-status is-success";
            status.textContent = "Thanks! Your request was sent. We'll be in touch soon — or call (608) 440-4428.";
          } else {
            status.className = "form-status is-error";
            status.textContent = (data && data.error) || "Something went wrong. Please call (608) 440-4428.";
          }
        })
        .catch(function () {
          status.className = "form-status is-error";
          status.textContent = "Couldn't send right now. Please call (608) 440-4428.";
        })
        .then(function () { submit.disabled = false; submit.textContent = label; });
    });
  });

  /* ---- Non-JS fallback result (?sent=1 / ?sent=0) ---- */
  var q = new URLSearchParams(location.search).get("sent");
  if (q !== null) {
    var s = document.querySelector("#estimate .form-status");
    if (s) {
      s.className = "form-status " + (q === "1" ? "is-success" : "is-error");
      s.textContent = q === "1" ? "Thanks! Your request was sent." : "Something went wrong. Please call (608) 440-4428.";
    }
  }

  /* ---- Footer year ---- */
  document.querySelectorAll("[data-year]").forEach(function (el) { el.textContent = new Date().getFullYear(); });
})();
