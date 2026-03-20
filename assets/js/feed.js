(function () {
    "use strict";

    if (!window.MitchieApp || window.MitchieApp.APP.page !== "feed") {
        return;
    }

    var app = window.MitchieApp;
    var APP = app.APP;
    var state = {
        tab: "public",
        status: null,
        publicOffset: 0,
        historyOffset: 0,
        publicHasMore: false,
        historyHasMore: false,
        limit: 20,
        selectedTemplates: [],
        selectedIconKey: null
    };

    function escape(value) {
        return app.escapeHtml(value || "");
    }

    function renderPost(post, isHistory) {
        var templates = (post.template_lines || []).map(function (line) {
            return '<span class="feed-post-template">' + escape(line) + "</span>";
        }).join("");

        var deleteButton = isHistory
            ? '<button class="button button-secondary feed-post-delete" type="button" data-feed-delete="' + post.id + '">削除</button>'
            : "";

        return [
            '<article class="feed-post-card" data-feed-post-id="' + post.id + '">',
            '  <div class="feed-post-head">',
            '    <div class="feed-post-icon"><img src="' + escape(post.icon_url) + '" alt=""></div>',
            '    <div class="feed-post-user">',
            '      <strong>' + escape(post.username || "user") + "</strong>",
            '      <span class="feed-post-meta">' + escape(post.created_at) + "</span>",
            "    </div>",
            "  </div>",
            '  <div class="feed-post-body">',
            '    <p>' + escape(post.auto_summary) + "</p>",
            post.category_summary ? '<p class="feed-post-meta">' + escape(post.category_summary) + "</p>" : "",
            templates ? '<div class="feed-post-templates">' + templates + "</div>" : "",
            "  </div>",
            '  <div class="feed-post-foot">',
            '    <span class="feed-post-meta">完了 ' + escape(String(post.completed_count)) + '件</span>',
            deleteButton,
            "  </div>",
            "</article>"
        ].join("");
    }

    function updateTabUI() {
        document.querySelectorAll("[data-feed-tab]").forEach(function (button) {
            button.classList.toggle("is-active", button.getAttribute("data-feed-tab") === state.tab);
        });
        document.querySelectorAll("[data-feed-panel]").forEach(function (panel) {
            panel.hidden = panel.getAttribute("data-feed-panel") !== state.tab;
        });
    }

    function renderComposeState(data) {
        var box = document.getElementById("feedComposeState");
        var form = document.getElementById("feedComposeForm");
        var summary = document.getElementById("feedAutoSummary");

        state.status = data;
        box.innerHTML = "";
        form.hidden = true;

        if (summary) {
            summary.textContent = data.auto_summary || "今日は一歩だけ前に進めました";
        }

        if (!data.authenticated) {
            box.innerHTML = '<p>投稿するにはログインが必要です。</p><a class="button button-secondary" href="' + APP.pages.settings + '">ログインする</a>';
            return;
        }

        if (data.reason === "username_required") {
            box.innerHTML = '<p>投稿する前にユーザー名を設定してください。</p><a class="button button-secondary" href="' + APP.pages.settings + '">ユーザー名を設定</a>';
            return;
        }

        if (data.posted_today && data.post) {
            box.innerHTML = '<p>今日は投稿済みです。</p><p>' + escape(data.post.auto_summary) + "</p>";
            return;
        }

        if (!data.can_post) {
            box.innerHTML = '<p>今日はまだ投稿していません。完了タスクが1件以上ある日に共有できます。</p>';
            return;
        }

        form.hidden = false;
    }

    function renderIcons(icons) {
        var root = document.getElementById("feedIconGrid");
        if (!root) {
            return;
        }

        if (!state.selectedIconKey && icons[0]) {
            state.selectedIconKey = icons[0].key;
        }

        root.innerHTML = icons.map(function (icon) {
            var active = state.selectedIconKey === icon.key ? " is-active" : "";
            return [
                '<label class="feed-icon-option' + active + '">',
                '  <input type="radio" name="feedIcon" value="' + escape(icon.key) + '"' + (active ? " checked" : "") + '>',
                '  <img src="' + escape(icon.url) + '" alt="">',
                '  <span>' + escape(icon.label) + "</span>",
                "</label>"
            ].join("");
        }).join("");
    }

    function renderTemplates(templates) {
        var root = document.getElementById("feedTemplateGrid");
        if (!root) {
            return;
        }

        root.innerHTML = templates.map(function (template) {
            var active = state.selectedTemplates.indexOf(template.text) !== -1 ? " is-active" : "";
            return '<button class="feed-template-chip' + active + '" type="button" data-template-text="' + escape(template.text) + '">' + escape(template.text) + "</button>";
        }).join("");
    }

    async function loadStatus() {
        var data = await app.apiFetch(APP.routes.feedStatus);
        renderComposeState(data);
        renderIcons(data.icons || []);
        renderTemplates(data.templates || []);
    }

    async function loadPublic(reset) {
        if (reset) {
            state.publicOffset = 0;
        }

        var data = await app.apiFetch(APP.routes.feedList + "?limit=" + state.limit + "&offset=" + state.publicOffset);
        var list = document.getElementById("feedPublicList");
        var empty = document.getElementById("feedPublicEmpty");
        var more = document.getElementById("feedPublicMore");

        if (reset) {
            list.innerHTML = "";
        }

        list.insertAdjacentHTML("beforeend", (data.items || []).map(function (post) {
            return renderPost(post, false);
        }).join(""));

        state.publicOffset += (data.items || []).length;
        state.publicHasMore = Boolean(data.paging && data.paging.has_more);
        empty.hidden = (data.items || []).length > 0 || state.publicOffset > 0;
        more.hidden = !state.publicHasMore;
    }

    function renderHistorySummary(data) {
        var root = document.getElementById("feedHistorySummary");
        if (!root) {
            return;
        }

        root.innerHTML = [
            '<article class="feed-history-stat"><span class="feed-post-meta">投稿回数</span><strong>' + escape(String(data.total_posts || 0)) + "</strong></article>",
            '<article class="feed-history-stat"><span class="feed-post-meta">直近7日の完了</span><strong>' + escape(String(data.completed_7d || 0)) + "</strong></article>",
            '<article class="feed-history-stat"><span class="feed-post-meta">直近30日の完了</span><strong>' + escape(String(data.completed_30d || 0)) + "</strong></article>",
            '<article class="feed-history-stat"><span class="feed-post-meta">連続日数</span><strong>' + escape(String(data.streak_days || 0)) + "</strong></article>"
        ].join("");
    }

    async function loadHistorySummary() {
        try {
            var data = await app.apiFetch(APP.routes.historySummary);
            renderHistorySummary(data);
        } catch (error) {
            var root = document.getElementById("feedHistorySummary");
            if (root) {
                root.innerHTML = '<article class="feed-history-stat"><span class="feed-post-meta">履歴を見るにはログインが必要です。</span><strong>--</strong></article>';
            }
        }
    }

    async function loadHistory(reset) {
        var list = document.getElementById("feedHistoryList");
        var empty = document.getElementById("feedHistoryEmpty");
        var more = document.getElementById("feedHistoryMore");

        if (reset) {
            state.historyOffset = 0;
            list.innerHTML = "";
        }

        try {
            var data = await app.apiFetch(APP.routes.historyList + "?limit=" + state.limit + "&offset=" + state.historyOffset);
            list.insertAdjacentHTML("beforeend", (data.items || []).map(function (post) {
                return renderPost(post, true);
            }).join(""));
            state.historyOffset += (data.items || []).length;
            state.historyHasMore = Boolean(data.paging && data.paging.has_more);
            empty.hidden = (data.items || []).length > 0 || state.historyOffset > 0;
            more.hidden = !state.historyHasMore;
        } catch (error) {
            empty.hidden = false;
            empty.querySelector("p").textContent = "履歴を見るにはログインが必要です。";
            more.hidden = true;
        }
    }

    async function submitPost() {
        var data = await app.apiFetch(APP.routes.feedList, {
            method: "POST",
            body: {
                template_lines: state.selectedTemplates,
                icon_key: state.selectedIconKey
            }
        });

        app.showToast(data.message || "共有しました。");
        state.selectedTemplates = [];
        await loadStatus();
        await loadPublic(true);
        await loadHistorySummary();
        await loadHistory(true);
    }

    async function deletePost(id) {
        var data = await app.apiFetch(APP.routes.feedItem + "?id=" + encodeURIComponent(id), {
            method: "DELETE"
        });
        app.showToast(data.message || "削除しました。");
        await loadStatus();
        await loadPublic(true);
        await loadHistorySummary();
        await loadHistory(true);
    }

    document.addEventListener("DOMContentLoaded", function () {
        updateTabUI();

        document.getElementById("feedComposeForm").addEventListener("submit", function (event) {
            event.preventDefault();
            submitPost().catch(function (error) {
                console.error(error);
                app.showToast(error.message);
            });
        });

        document.getElementById("feedModeTabs").addEventListener("click", function (event) {
            var button = event.target.closest("[data-feed-tab]");
            if (!button) {
                return;
            }
            state.tab = button.getAttribute("data-feed-tab");
            updateTabUI();
        });

        document.getElementById("feedIconGrid").addEventListener("click", function (event) {
            var option = event.target.closest(".feed-icon-option");
            if (!option) {
                return;
            }
            state.selectedIconKey = option.querySelector("input").value;
            renderIcons(state.status ? state.status.icons || [] : []);
        });

        document.getElementById("feedTemplateGrid").addEventListener("click", function (event) {
            var chip = event.target.closest("[data-template-text]");
            if (!chip) {
                return;
            }

            var text = chip.getAttribute("data-template-text");
            var index = state.selectedTemplates.indexOf(text);

            if (index !== -1) {
                state.selectedTemplates.splice(index, 1);
            } else if (state.selectedTemplates.length < 3) {
                state.selectedTemplates.push(text);
            } else {
                app.showToast("定型文は3つまで選択できます。");
            }

            renderTemplates(state.status ? state.status.templates || [] : []);
        });

        document.getElementById("feedPublicMore").addEventListener("click", function () {
            loadPublic(false).catch(function (error) {
                console.error(error);
                app.showToast(error.message);
            });
        });

        document.getElementById("feedHistoryMore").addEventListener("click", function () {
            loadHistory(false).catch(function (error) {
                console.error(error);
                app.showToast(error.message);
            });
        });

        document.getElementById("feedHistoryList").addEventListener("click", function (event) {
            var button = event.target.closest("[data-feed-delete]");
            if (!button) {
                return;
            }
            deletePost(button.getAttribute("data-feed-delete")).catch(function (error) {
                console.error(error);
                app.showToast(error.message);
            });
        });

        Promise.all([
            loadStatus(),
            loadPublic(true),
            loadHistorySummary(),
            loadHistory(true)
        ]).catch(function (error) {
            console.error(error);
            app.showToast(error.message);
        });
    });
}());
