(function () {
    "use strict";

    if (!window.MitchieApp || window.MitchieApp.APP.page !== "settings") {
        return;
    }

    var app = window.MitchieApp;
    var APP = app.APP;
    var deferredInstallPrompt = null;
    var categoryState = {
        editingId: null,
        authenticated: false,
        customCount: 0,
        customLimit: 10
    };

    function escapeHtml(value) {
        return app.escapeHtml(value || "");
    }

    function setText(id, value) {
        var node = document.getElementById(id);
        if (node) {
            node.textContent = value;
        }
    }

    function renderCategoryRow(category) {
        if (category.is_custom) {
            return [
                '<article class="category-row" data-category-id="' + category.id + '" data-category-name="' + escapeHtml(category.name) + '">',
                '  <strong class="category-row-name">' + escapeHtml(category.name) + "</strong>",
                '  <div class="inline-actions category-row-actions">',
                '    <button class="button button-secondary" type="button" data-category-edit="' + category.id + '">編集</button>',
                '    <button class="button button-secondary" type="button" data-category-delete="' + category.id + '">削除</button>',
                "  </div>",
                "</article>"
            ].join("");
        }

        return [
            '<article class="category-row">',
            '  <strong class="category-row-name">' + escapeHtml(category.name) + "</strong>",
            '  <span class="category-row-fixed">固定</span>',
            "</article>"
        ].join("");
    }

    function renderCategorySection(title, note, categories, emptyMessage) {
        return [
            '<section class="category-section-block">',
            '  <div class="category-section-head">',
            "    <strong>" + escapeHtml(title) + "</strong>",
            note ? ("    <span>" + escapeHtml(note) + "</span>") : "",
            "  </div>",
            categories.length
                ? ('  <div class="category-section-list">' + categories.map(renderCategoryRow).join("") + "</div>")
                : ('  <p class="field-note category-empty-note">' + escapeHtml(emptyMessage) + "</p>"),
            "</section>"
        ].join("");
    }

    function updateCategoryMeta(customCount, customLimit, authenticated) {
        var meta = document.getElementById("categoryLimitMeta");
        var form = document.getElementById("categoryForm");
        var submitButton = document.getElementById("categorySubmitButton");

        categoryState.customCount = customCount;
        categoryState.customLimit = customLimit;

        if (meta) {
            meta.textContent = authenticated ? "（ " + customCount + " / " + customLimit + " ）" : "";
        }

        if (form) {
            form.hidden = !authenticated;
        }

        if (submitButton) {
            submitButton.disabled = authenticated && !categoryState.editingId && customCount >= customLimit;
        }
    }

    function renderCategories(data) {
        var root = document.getElementById("categoryList");
        if (!root) {
            return;
        }

        var defaultCategories = Array.isArray(data.default_categories) ? data.default_categories : [];
        var userCategories = Array.isArray(data.user_categories) ? data.user_categories : [];
        var customLimit = Number(data.custom_limit || 10);

        updateCategoryMeta(userCategories.length, customLimit, categoryState.authenticated);

        root.innerHTML = [
            renderCategorySection(
                "デフォルトカテゴリ",
                "編集・削除はできません",
                defaultCategories,
                "デフォルトカテゴリはありません。"
            ),
            renderCategorySection(
                "ユーザーカテゴリ",
                userCategories.length ? (userCategories.length + " / " + customLimit) : "",
                userCategories,
                categoryState.authenticated ? "まだ追加されていません。" : "ログインすると追加できます。"
            )
        ].join("");
    }

    function updateAuthUI(data) {
        var status = document.getElementById("settingsAuthStatus");
        var detail = document.getElementById("settingsAuthDetail");
        var logoutButtons = document.querySelectorAll("#logoutButton");
        var magicLinkForm = document.getElementById("magicLinkForm");
        var usernameInput = document.getElementById("usernameInput");
        var currentUsername = document.getElementById("settingsCurrentUsername");
        var categoryNote = document.getElementById("categoryPanelNote");
        var categoryForm = document.getElementById("categoryForm");

        categoryState.authenticated = Boolean(data.authenticated && data.user);

        if (categoryState.authenticated) {
            if (status) {
                status.textContent = "ログイン中です";
            }
            if (detail) {
                detail.textContent = (data.user.email || "") + " で利用中です。";
            }
            logoutButtons.forEach(function (button) {
                button.hidden = false;
            });
            if (magicLinkForm) {
                magicLinkForm.hidden = true;
            }
            if (usernameInput) {
                usernameInput.value = data.user.username || "";
            }
            if (currentUsername) {
                currentUsername.textContent = data.user.username || "未設定";
            }
            if (categoryNote) {
                categoryNote.textContent = "デフォルトカテゴリは固定です。ユーザーカテゴリは10件まで追加できます。";
            }
            if (categoryForm) {
                categoryForm.hidden = false;
            }
            return;
        }

        if (status) {
            status.textContent = "ログインすると複数端末でも続きから使えます";
        }
        if (detail) {
            detail.textContent = "メールアドレスだけで、あとからログインできます。";
        }
        logoutButtons.forEach(function (button) {
            button.hidden = true;
        });
        if (magicLinkForm) {
            magicLinkForm.hidden = false;
        }
        if (categoryNote) {
            categoryNote.textContent = "デフォルトカテゴリは固定です。ユーザーカテゴリを使うにはログインしてください。";
        }
        if (categoryForm) {
            categoryForm.hidden = true;
        }
    }

    async function loadCategories() {
        var data = await app.apiFetch(APP.routes.categories);
        renderCategories(data);
    }

    function resetCategoryForm() {
        var editInput = document.getElementById("categoryEditId");
        var nameInput = document.getElementById("categoryNameInput");
        var submitButton = document.getElementById("categorySubmitButton");
        var cancelButton = document.getElementById("categoryCancelButton");

        categoryState.editingId = null;

        if (editInput) {
            editInput.value = "";
        }
        if (nameInput) {
            nameInput.value = "";
        }
        if (submitButton) {
            submitButton.textContent = "追加";
            submitButton.disabled = categoryState.authenticated && categoryState.customCount >= categoryState.customLimit;
        }
        if (cancelButton) {
            cancelButton.hidden = true;
        }
    }

    function startCategoryEdit(button) {
        var row = button.closest("[data-category-id]");
        var editInput = document.getElementById("categoryEditId");
        var nameInput = document.getElementById("categoryNameInput");
        var submitButton = document.getElementById("categorySubmitButton");
        var cancelButton = document.getElementById("categoryCancelButton");

        if (!row || !editInput || !nameInput || !submitButton || !cancelButton) {
            return;
        }

        categoryState.editingId = row.getAttribute("data-category-id");
        editInput.value = categoryState.editingId;
        nameInput.value = row.getAttribute("data-category-name") || "";
        submitButton.textContent = "更新";
        submitButton.disabled = false;
        cancelButton.hidden = false;
        nameInput.focus();
    }

    async function submitCategoryForm() {
        if (!categoryState.authenticated) {
            app.showToast("カテゴリを追加するにはログインが必要です。");
            return;
        }

        var name = document.getElementById("categoryNameInput").value.trim();
        var editingId = document.getElementById("categoryEditId").value.trim();
        var method = editingId ? "PUT" : "POST";
        var url = APP.routes.categories + (editingId ? ("?id=" + encodeURIComponent(editingId)) : "");
        var data = await app.apiFetch(url, {
            method: method,
            body: { name: name }
        });

        renderCategories(data);
        resetCategoryForm();
        app.showToast(data.message || (editingId ? "カテゴリを更新しました。" : "カテゴリを追加しました。"));
    }

    async function deleteCategory(id) {
        if (!categoryState.authenticated) {
            app.showToast("カテゴリを削除するにはログインが必要です。");
            return;
        }

        var data = await app.apiFetch(APP.routes.categories + "?id=" + encodeURIComponent(id), {
            method: "DELETE"
        });

        renderCategories(data);
        if (String(categoryState.editingId || "") === String(id)) {
            resetCategoryForm();
        }
        app.showToast(data.message || "カテゴリを削除しました。");
    }

    async function submitMagicLink(form) {
        var emailInput = document.getElementById("emailInput");
        var sentBox = document.getElementById("magicLinkSent");
        var email = emailInput ? emailInput.value.trim() : "";

        await app.apiFetch(APP.routes.requestMagicLink, {
            method: "POST",
            body: { email: email }
        });

        if (sentBox) {
            sentBox.hidden = false;
        }
        form.reset();
        app.showToast("ログイン用リンクを送信しました。");
    }

    async function submitUsername() {
        var usernameInput = document.getElementById("usernameInput");
        var username = usernameInput ? usernameInput.value.trim() : "";
        var data = await app.apiFetch(APP.routes.updateUsername, {
            method: "POST",
            body: { username: username }
        });

        setText("settingsCurrentUsername", data.user.username || "未設定");
        app.showToast(data.message || "ユーザー名を更新しました。");
    }

    function isIos() {
        var ua = window.navigator.userAgent || "";
        return /iPad|iPhone|iPod/.test(ua) || (navigator.platform === "MacIntel" && navigator.maxTouchPoints > 1);
    }

    function isStandaloneMode() {
        return window.matchMedia("(display-mode: standalone)").matches || window.navigator.standalone === true;
    }

    function showInstallDialog(html) {
        var dialog = document.getElementById("install-dialog");
        var body = document.getElementById("install-dialog-body");
        if (!dialog || !body) {
            return;
        }

        body.innerHTML = html;
        dialog.hidden = false;
        dialog.classList.add("is-open");
    }

    function closeInstallDialog() {
        var dialog = document.getElementById("install-dialog");
        if (!dialog) {
            return;
        }
        dialog.classList.remove("is-open");
        dialog.hidden = true;
    }

    function handleInstall() {
        if (isStandaloneMode()) {
            showInstallDialog("<p>すでにホーム画面から使える状態です。</p>");
            return;
        }

        if (deferredInstallPrompt) {
            deferredInstallPrompt.prompt();
            Promise.resolve(deferredInstallPrompt.userChoice).finally(function () {
                deferredInstallPrompt = null;
            });
            return;
        }

        if (isIos()) {
            showInstallDialog([
                "<ol>",
                "<li>ブラウザ(Safari/Chromeなど)の共有ボタンをタップ</li>",
                "<li>「ホーム画面に追加」を選ぶ（ない場合は「もっと見る」を押してみて）</li>",
                "</ol>"
            ].join(""));
            return;
        }

        showInstallDialog([
            "<p>このブラウザでは直接インストールを出せませんでした。</p>",
            "<p>ブラウザのメニューから「ホーム画面に追加」や「アプリをインストール」を探してみてください。</p>"
        ].join(""));
    }

    document.addEventListener("DOMContentLoaded", function () {
        var form = document.getElementById("magicLinkForm");
        var logoutButtons = document.querySelectorAll("#logoutButton");
        var usernameForm = document.getElementById("usernameForm");
        var categoryForm = document.getElementById("categoryForm");
        var categoryList = document.getElementById("categoryList");
        var categoryCancelButton = document.getElementById("categoryCancelButton");
        var installButton = document.getElementById("install-button");
        var installDialog = document.getElementById("install-dialog");
        var installDialogClose = document.getElementById("install-dialog-close");

        document.querySelectorAll("[data-theme-option]").forEach(function (button) {
            button.addEventListener("click", function () {
                app.setTheme(button.getAttribute("data-theme-option"));
            });
        });

        window.addEventListener("beforeinstallprompt", function (event) {
            event.preventDefault();
            deferredInstallPrompt = event;
        });

        window.addEventListener("appinstalled", function () {
            deferredInstallPrompt = null;
            app.showToast("ホーム画面に追加されました。");
        });

        app.loadMe().then(function (data) {
            updateAuthUI(data);
            return loadCategories();
        }).catch(function (error) {
            console.error(error);
            app.showToast(error.message || "設定の読み込みに失敗しました。");
        });

        if (form) {
            form.addEventListener("submit", function (event) {
                event.preventDefault();
                submitMagicLink(event.currentTarget).catch(function (error) {
                    console.error(error);
                    app.showToast(error.message || "メール送信に失敗しました。");
                });
            });
        }

        if (usernameForm) {
            usernameForm.addEventListener("submit", function (event) {
                event.preventDefault();
                submitUsername().catch(function (error) {
                    console.error(error);
                    app.showToast(error.message || "ユーザー名の更新に失敗しました。");
                });
            });
        }

        if (categoryForm) {
            categoryForm.addEventListener("submit", function (event) {
                event.preventDefault();
                submitCategoryForm().catch(function (error) {
                    console.error(error);
                    app.showToast(error.message || "カテゴリの保存に失敗しました。");
                });
            });
        }

        if (categoryCancelButton) {
            categoryCancelButton.addEventListener("click", resetCategoryForm);
        }

        if (categoryList) {
            categoryList.addEventListener("click", function (event) {
                var editButton = event.target.closest("[data-category-edit]");
                var deleteButton = event.target.closest("[data-category-delete]");

                if (editButton) {
                    startCategoryEdit(editButton);
                    return;
                }

                if (deleteButton) {
                    deleteCategory(deleteButton.getAttribute("data-category-delete")).catch(function (error) {
                        console.error(error);
                        app.showToast(error.message || "カテゴリの削除に失敗しました。");
                    });
                }
            });
        }

        logoutButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                app.apiFetch(APP.routes.logout, {
                    method: "POST"
                }).then(function () {
                    window.location.href = APP.pages.settings;
                }).catch(function (error) {
                    console.error(error);
                    app.showToast(error.message || "ログアウトに失敗しました。");
                });
            });
        });

        if (installButton) {
            installButton.addEventListener("click", function (event) {
                event.preventDefault();
                handleInstall();
            });
        }

        if (installDialogClose) {
            installDialogClose.addEventListener("click", closeInstallDialog);
        }

        if (installDialog) {
            installDialog.addEventListener("click", function (event) {
                if (event.target === installDialog) {
                    closeInstallDialog();
                }
            });
        }
    });
}());
