(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
        var toggle = document.getElementById("taskFormMenuToggle");
        var menu = document.getElementById("taskFormHeaderMenu");
        var topbar = document.querySelector(".page-task-form .topbar-transactional");
        var placeholder = topbar ? topbar.querySelector(".topbar-icon-button.is-passive") : null;

        if (!toggle || !menu) {
            return;
        }

        if (topbar && placeholder) {
            placeholder.replaceWith(toggle.parentNode);
        }

        function closeMenu() {
            toggle.setAttribute("aria-expanded", "false");
            menu.hidden = true;
        }

        function openMenu() {
            toggle.setAttribute("aria-expanded", "true");
            menu.hidden = false;
        }

        toggle.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (menu.hidden) {
                openMenu();
            } else {
                closeMenu();
            }
        });

        document.addEventListener("click", function (event) {
            if (!menu.contains(event.target) && !toggle.contains(event.target)) {
                closeMenu();
            }
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                closeMenu();
            }
        });
    });
}());
