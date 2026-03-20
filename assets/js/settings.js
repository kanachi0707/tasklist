(function () {
    "use strict";

    if (!window.MitchieApp || window.MitchieApp.APP.page !== "settings") {
        return;
    }

    var app = window.MitchieApp;
    var APP = app.APP;

    function updateAuthUI(data) {
        var status = document.getElementById("settingsAuthStatus");
        var detail = document.getElementById("settingsAuthDetail");
        var logoutButton = document.getElementById("logoutButton");
        var form = document.getElementById("magicLinkForm");
        var usernameInput = document.getElementById("usernameInput");
        var currentUsername = document.getElementById("settingsCurrentUsername");

        if (data.authenticated && data.user) {
            if (status) {
                status.textContent = "ログイン済みです";
            }
            if (detail) {
                detail.textContent = data.user.email + " で利用中です。";
            }
            if (logoutButton) {
                logoutButton.hidden = false;
            }
            if (form) {
                form.hidden = true;
            }
            if (usernameInput) {
                usernameInput.value = data.user.username || "";
            }
            if (currentUsername) {
                currentUsername.textContent = data.user.username || "未設定";
            }
            return;
        }

        if (status) {
            status.textContent = "ゲスト利用中です";
        }
        if (detail) {
            detail.textContent = "ログインすると他端末でも続きから使えます。";
        }
        if (logoutButton) {
            logoutButton.hidden = true;
        }
        if (form) {
            form.hidden = false;
        }
    }

    async function submitMagicLink(form) {
        var email = document.getElementById("emailInput").value.trim();
        var sentBox = document.getElementById("magicLinkSent");
        var debugLink = document.getElementById("debugMagicLink");
        var data = await app.apiFetch(APP.routes.requestMagicLink, {
            method: "POST",
            body: { email: email }
        });

        sentBox.hidden = false;
        if (data.debug_link) {
            debugLink.hidden = false;
            debugLink.href = data.debug_link;
        } else {
            debugLink.hidden = true;
        }
        form.reset();
        app.showToast("ログイン用リンクを送信しました。");
    }

    async function submitUsername(form) {
        var username = document.getElementById("usernameInput").value.trim();
        var data = await app.apiFetch(APP.routes.updateUsername, {
            method: "POST",
            body: { username: username }
        });

        document.getElementById("settingsCurrentUsername").textContent = data.user.username;
        app.showToast(data.message || "ユーザー名を保存しました。");
    }

    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll("[data-theme-option]").forEach(function (button) {
            button.addEventListener("click", function () {
                app.setTheme(button.getAttribute("data-theme-option"));
            });
        });

        var form = document.getElementById("magicLinkForm");
        var logoutButton = document.getElementById("logoutButton");
        var usernameForm = document.getElementById("usernameForm");

        app.loadMe().then(updateAuthUI).catch(function (error) {
            console.error(error);
            app.showToast(error.message);
        });

        if (form) {
            form.addEventListener("submit", function (event) {
                event.preventDefault();
                submitMagicLink(event.currentTarget).catch(function (error) {
                    console.error(error);
                    app.showToast(error.message);
                });
            });
        }

        if (usernameForm) {
            usernameForm.addEventListener("submit", function (event) {
                event.preventDefault();
                submitUsername(event.currentTarget).catch(function (error) {
                    console.error(error);
                    app.showToast(error.message);
                });
            });
        }

        if (logoutButton) {
            logoutButton.addEventListener("click", function () {
                app.apiFetch(APP.routes.logout, {
                    method: "POST"
                }).then(function () {
                    window.location.href = APP.pages.settings;
                }).catch(function (error) {
                    console.error(error);
                    app.showToast(error.message);
                });
            });
        }
    });
}());
