(function () {
    "use strict";

    var APP = window.APP || {};
    var state = {
        me: null
    };

    function getCookie(name) {
        var pattern = document.cookie.split("; ").find(function (item) {
            return item.indexOf(name + "=") === 0;
        });
        return pattern ? decodeURIComponent(pattern.split("=").slice(1).join("=")) : "";
    }

    async function apiFetch(url, options) {
        var config = options || {};
        var headers = Object.assign({
            Accept: "application/json"
        }, config.headers || {});

        if (config.body && !(config.body instanceof FormData) && !headers["Content-Type"]) {
            headers["Content-Type"] = "application/json";
            config.body = JSON.stringify(config.body);
        }

        if (config.method && config.method.toUpperCase() !== "GET") {
            headers["X-CSRF-Token"] = APP.csrfToken || getCookie("csrf_token");
        }

        var response = await fetch(url, Object.assign({}, config, {
            headers: headers,
            credentials: "same-origin"
        }));
        var payload = await response.json().catch(function () {
            return {};
        });

        if (!response.ok || !payload.ok) {
            var message = payload && payload.error ? payload.error : "通信に失敗しました。";
            var error = new Error(message);
            error.payload = payload;
            error.status = response.status;
            throw error;
        }

        return payload.data;
    }

    function escapeHtml(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    function showToast(message, options) {
        var toast = document.getElementById("toast");
        if (!toast) {
            return;
        }

        var config = options || {};
        toast.classList.toggle("has-icon", Boolean(config.icon));

        if (config.icon) {
            toast.innerHTML = '<img class="toast-icon" src="' + escapeHtml(config.icon) + '" alt=""><span class="toast-label">' + escapeHtml(message) + "</span>";
        } else {
            toast.textContent = message;
        }

        toast.hidden = false;
        clearTimeout(showToast.timer);
        showToast.timer = setTimeout(function () {
            toast.hidden = true;
            toast.classList.remove("has-icon");
        }, 2800);
    }

    function formatDateJP(value) {
        if (!value) {
            return "期限未設定";
        }
        var date = new Date(value + "T00:00:00");
        if (Number.isNaN(date.getTime())) {
            return value;
        }
        return new Intl.DateTimeFormat("ja-JP", {
            month: "numeric",
            day: "numeric",
            weekday: "short"
        }).format(date);
    }

    function setTheme(theme) {
        var next = theme === "dark" ? "dark" : "light";
        document.documentElement.setAttribute("data-theme", next);
        try {
            localStorage.setItem("todo-theme", next);
        } catch (error) {
            console.warn(error);
        }

        var cookie = APP.themeCookieName + "=" + encodeURIComponent(next) + "; path=/; SameSite=Lax; max-age=31536000";
        if (location.protocol === "https:" && APP.cookieSecure) {
            cookie += "; Secure";
        }
        document.cookie = cookie;

        document.querySelectorAll("[data-theme-option]").forEach(function (button) {
            button.classList.toggle("is-active", button.getAttribute("data-theme-option") === next);
        });
    }

    async function loadMe() {
        if (state.me) {
            return state.me;
        }

        var data = await apiFetch(APP.routes.me);
        state.me = data;
        updateAuthBadge(data);
        document.dispatchEvent(new CustomEvent("mitchie:auth", { detail: data }));
        return data;
    }

    function updateAuthBadge(data) {
        var label = document.querySelector("[data-auth-label]");
        if (!label) {
            return;
        }

        label.textContent = data.authenticated && data.user
            ? (data.user.username || data.user.email)
            : "ゲスト利用中";
    }

    function priorityClass(priority) {
        if (priority === "high") {
            return "priority-high";
        }
        if (priority === "low") {
            return "priority-low";
        }
        return "priority-medium";
    }

    function dueBadge(todo) {
        if (!todo.due_date) {
            return "";
        }
        return '<span class="meta-badge secondary"><span class="material-symbols-outlined">calendar_today</span>' + escapeHtml(formatDateJP(todo.due_date)) + "</span>";
    }

    function initAppSplash() {
        var splash = document.getElementById("appSplash");
        if (!splash || !window.__SHOW_APP_SPLASH__) {
            document.documentElement.classList.remove("has-app-splash");
            return;
        }

        var prefersReducedMotion = false;
        try {
            prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        } catch (error) {
            prefersReducedMotion = false;
        }

        var holdDuration = prefersReducedMotion ? 0 : 1750;
        var leaveDuration = prefersReducedMotion ? 180 : 1000;
        var duration = holdDuration + leaveDuration;
        var finished = false;

        function finishSplash() {
            if (finished) {
                return;
            }
            finished = true;
            document.documentElement.classList.remove("has-app-splash");
            document.documentElement.classList.remove("is-splash-revealing");
            if (APP.redirect_after_splash) {
                window.location.href = APP.redirect_after_splash;
                return;
            }
            if (splash.parentNode) {
                splash.parentNode.removeChild(splash);
            }
        }

        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(function () {
                window.setTimeout(function () {
                    if (finished) {
                        return;
                    }
                    document.documentElement.classList.add("is-splash-revealing");
                    splash.classList.add("is-leaving");
                }, holdDuration);
            });
        });

        splash.addEventListener("click", finishSplash, { once: true });
        window.setTimeout(finishSplash, duration);
    }

    function initTopbarMenu() {
        var passiveButton = document.querySelector(".topbar-default .topbar-left .topbar-icon-button.is-passive");
        if (!passiveButton || !APP.pages) {
            return;
        }

        var wrapper = document.createElement("div");
        wrapper.className = "topbar-menu";

        var toggle = passiveButton.cloneNode(true);
        toggle.classList.remove("is-passive");
        toggle.setAttribute("aria-label", "メニュー");
        toggle.setAttribute("aria-expanded", "false");
        toggle.removeAttribute("disabled");
        toggle.style.pointerEvents = "";

        var menu = document.createElement("div");
        menu.className = "topbar-dropdown";
        menu.hidden = true;

        [
            { href: APP.pages.home, icon: "checklist", label: "タスク" },
            { href: APP.pages.calendar, icon: "calendar_month", label: "カレンダー" },
            { href: APP.pages.stats, icon: "bar_chart", label: "統計" },
            { href: APP.pages.feed, icon: "rss_feed", label: "FEED" },
            { href: APP.pages.settings, icon: "settings", label: "設定" }
        ].forEach(function (item) {
            if (!item.href) {
                return;
            }

            var link = document.createElement("a");
            link.className = "topbar-dropdown-link";
            link.href = item.href;
            link.innerHTML = '<span class="material-symbols-outlined">' + escapeHtml(item.icon) + '</span><span>' + escapeHtml(item.label) + '</span>';
            menu.appendChild(link);
        });

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
            if (!wrapper.contains(event.target)) {
                closeMenu();
            }
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                closeMenu();
            }
        });

        wrapper.appendChild(toggle);
        wrapper.appendChild(menu);
        passiveButton.replaceWith(wrapper);
    }

    function initTransactionalTopbarMenu() {
        if (!APP.pages || document.querySelector(".task-form-header-menu")) {
            return;
        }

        var passiveButton = document.querySelector(".topbar-transactional .topbar-icon-button.is-passive");
        if (!passiveButton) {
            return;
        }

        var wrapper = document.createElement("div");
        wrapper.className = "topbar-menu is-transactional-menu";

        var toggle = passiveButton.cloneNode(true);
        toggle.classList.remove("is-passive");
        toggle.setAttribute("aria-label", "メニュー");
        toggle.setAttribute("aria-expanded", "false");
        toggle.removeAttribute("disabled");
        toggle.style.pointerEvents = "";

        var menu = document.createElement("div");
        menu.className = "topbar-dropdown";
        menu.hidden = true;

        [
            { href: APP.pages.home, icon: "checklist", label: "タスク" },
            { href: APP.pages.calendar, icon: "calendar_month", label: "カレンダー" },
            { href: APP.pages.stats, icon: "bar_chart", label: "統計" },
            { href: APP.pages.feed, icon: "rss_feed", label: "FEED" },
            { href: APP.pages.settings, icon: "settings", label: "設定" }
        ].forEach(function (item) {
            if (!item.href) {
                return;
            }

            var link = document.createElement("a");
            link.className = "topbar-dropdown-link";
            link.href = item.href;
            link.innerHTML = '<span class="material-symbols-outlined">' + escapeHtml(item.icon) + '</span><span>' + escapeHtml(item.label) + '</span>';
            menu.appendChild(link);
        });

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
            if (!wrapper.contains(event.target)) {
                closeMenu();
            }
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                closeMenu();
            }
        });

        wrapper.appendChild(toggle);
        wrapper.appendChild(menu);
        passiveButton.replaceWith(wrapper);
    }

    window.MitchieApp = {
        APP: APP,
        apiFetch: apiFetch,
        escapeHtml: escapeHtml,
        showToast: showToast,
        formatDateJP: formatDateJP,
        setTheme: setTheme,
        loadMe: loadMe,
        priorityClass: priorityClass,
        dueBadge: dueBadge,
        state: state
    };

    document.addEventListener("DOMContentLoaded", function () {
        var currentTheme = document.documentElement.getAttribute("data-theme") || "light";
        setTheme(currentTheme);
        initTopbarMenu();
        initTransactionalTopbarMenu();
        initAppSplash();
    });
}());
