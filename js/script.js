document.addEventListener("DOMContentLoaded", function () {
  var tabs = document.querySelectorAll(
    ".wp_mv_metaboxs .wp_mv_metaboxs__sidebar .wp_mv_metaboxs__sidebar__item",
  );
  var contents = document.querySelectorAll(
    ".wp_mv_metaboxs .wp_mv_metaboxs__content > div",
  );

  tabs.forEach(function (tab) {
    tab.addEventListener("click", function (e) {
      e.preventDefault();

      contents.forEach(function (content) {
        content.style.display = "none";
      });

      tabs.forEach(function (tab) {
        tab.classList.remove("active");
      });

      var target = document.querySelector(tab.getAttribute("data-tab"));
      target.style.display = "";

      tab.classList.add("active");
    });
  });

  tabs[0].click();
});

// Telefone

document.addEventListener("DOMContentLoaded", function () {
  function applyPhoneMask(input) {
    input.addEventListener("input", function () {
      let value = input.value.replace(/\D/g, "");

      if (value.length > 2 && value.length <= 7) {
        value = `(${value.slice(0, 2)}) ${value.slice(2)}`;
      } else if (value.length > 7) {
        value = `(${value.slice(0, 2)}) ${value.slice(2, 7)}-${value.slice(7, 11)}`;
      }

      input.value = value;
    });

    input.addEventListener("blur", function () {
      if (input.value.length < 14) {
        input.value = "";
      }
    });
  }

  const phoneInputs = document.querySelectorAll('input[aria-mask="phone"]');

  phoneInputs.forEach(function (input) {
    applyPhoneMask(input);
  });
});
