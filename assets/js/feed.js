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
        limit: 5,
        selectedTemplates: [],
        selectedIconKey: null,
        selectedMessageVariant: "default",
        featuredTemplateIds: []
    };

    function escape(value) {
        return app.escapeHtml(value || "");
    }

    function shuffle(items) {
        var copy = items.slice();
        for (var index = copy.length - 1; index > 0; index -= 1) {
            var swapIndex = Math.floor(Math.random() * (index + 1));
            var temp = copy[index];
            copy[index] = copy[swapIndex];
            copy[swapIndex] = temp;
        }
        return copy;
    }

    function pickFeaturedTemplateIds(templates) {
        var excluded = {
            read_book: true,
            watched_movie: true
        };
        var pool = templates.filter(function (template) {
            return !excluded[template.id];
        });
        return shuffle(pool).slice(0, 3).map(function (template) {
            return template.id;
        });
    }

    function splitTemplates(templates) {
        var featured = [];
        var extra = [];

        templates.forEach(function (template) {
            if (state.featuredTemplateIds.indexOf(template.id) !== -1) {
                featured.push(template);
            } else {
                extra.push(template);
            }
        });

        return { featured: featured, extra: extra };
    }

    function renderPost(post, isHistory, entering) {
        var templates = (post.template_lines || []).map(function (line) {
            return '<span class="feed-post-template">' + escape(line) + "</span>";
        }).join("");

        var deleteButton = isHistory
            ? '<button class="button button-secondary feed-post-delete" type="button" data-feed-delete="' + post.id + '">削除</button>'
            : "";

        return [
            '<article class="feed-post-card' + (entering ? " is-entering" : "") + '" data-feed-post-id="' + post.id + '">',
            '  <div class="feed-post-head">',
            '    <div class="feed-post-icon"><img src="' + escape(post.icon_url) + '" alt=""></div>',
            '    <div class="feed-post-user">',
            '      <strong>' + escape(post.username || "user") + "</strong>",
            '      <span class="feed-post-meta">' + escape(post.created_at) + "</span>",
            "    </div>",
            "  </div>",
            '  <div class="feed-post-body">',
            '    <p>' + escape(post.auto_summary) + "</p>",
            templates ? ('<div class="feed-post-templates">' + templates + "</div>") : "",
            "  </div>",
            '  <div class="feed-post-foot">',
            '    <div class="feed-post-actions">',
            '      <button class="feed-like-button' + (post.liked_by_viewer ? " is-active" : "") + '" type="button" data-feed-like="' + post.id + '">',
            '        <span class="material-symbols-outlined">thumb_up</span>',
            '        <span>' + escape(String(post.likes_count || 0)) + "</span>",
            "      </button>",
            "    </div>",
            deleteButton,
            "  </div>",
            "</article>"
        ].join("");
    }

    function appendPosts(list, items, isHistory, entering) {
        if (!items.length) {
            return;
        }

        list.insertAdjacentHTML("beforeend", items.map(function (post) {
            return renderPost(post, isHistory, entering);
        }).join(""));

        if (entering) {
            window.requestAnimationFrame(function () {
                list.querySelectorAll(".feed-post-card.is-entering").forEach(function (card) {
                    card.classList.remove("is-entering");
                });
            });
        }
    }

    function updateTabUI() {
        document.querySelectorAll("[data-feed-tab]").forEach(function (button) {
            button.classList.toggle("is-active", button.getAttribute("data-feed-tab") === state.tab);
        });
        document.querySelectorAll("[data-feed-panel]").forEach(function (panel) {
            panel.hidden = panel.getAttribute("data-feed-panel") !== state.tab;
        });
    }

    function renderMessageOptions(options) {
        var select = document.getElementById("feedMessageSelect");
        if (!select) {
            return;
        }

        if (!options.length) {
            select.innerHTML = "";
            return;
        }

        if (!state.selectedMessageVariant) {
            state.selectedMessageVariant = options[0].id;
        }

        select.innerHTML = options.map(function (option) {
            var selected = option.id === state.selectedMessageVariant ? " selected" : "";
            return '<option value="' + escape(option.id) + '"' + selected + ">" + escape(option.text) + "</option>";
        }).join("");
    }

    function renderComposeState(data) {
        var box = document.getElementById("feedComposeState");
        var form = document.getElementById("feedComposeForm");

        if (!box || !form) {
            return;
        }

        state.status = data;
        box.innerHTML = "";
        form.hidden = true;

        if (data.default_message_variant) {
            state.selectedMessageVariant = data.default_message_variant;
        }

        if (!data.authenticated) {
            box.innerHTML = '<p>投稿するにはログインが必要です。</p><a class="button button-secondary" href="' + APP.pages.settings + '">ログインする</a>';
            return;
        }

        if (data.reason === "username_required") {
            box.innerHTML = '<p>投稿する前にユーザー名を設定してください。</p><a class="button button-secondary" href="' + APP.pages.settings + '">ユーザー名を設定</a>';
            return;
        }

        if (!data.can_post) {
            if (data.reason === "daily_limit_reached") {
                box.innerHTML = "<p>今日は2回投稿済みです。</p>";
                return;
            }

            box.innerHTML = "<p>今日はまだ投稿できません。完了したタスクが1件以上ある日に共有できます。</p>";
            return;
        }

        if (data.today_posts_count > 0) {
            box.innerHTML = "<p>今日は" + escape(String(data.today_posts_count)) + "回投稿済みです。あと" + escape(String(data.remaining_posts || 0)) + "回共有できます。</p>";
        } else {
            box.innerHTML = "<p>今日はまだ投稿していません。気軽に1回共有してみましょう。</p>";
        }

        form.hidden = false;
        renderMessageOptions(data.message_options || []);
        renderComposeConfirm();
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
            var active = state.selectedIconKey === icon.key;
            return [
                '<label class="feed-icon-option' + (active ? " is-active" : "") + '">',
                '  <input type="radio" name="feedIcon" value="' + escape(icon.key) + '"' + (active ? " checked" : "") + ">",
                '  <img src="' + escape(icon.url) + '" alt="' + escape(icon.label) + '">',
                "</label>"
            ].join("");
        }).join("");
    }

    function renderTemplateGroup(root, templates) {
        if (!root) {
            return;
        }

        root.innerHTML = templates.map(function (template) {
            var active = state.selectedTemplates.indexOf(template.text) !== -1;
            return '<button class="feed-template-chip' + (active ? " is-active" : "") + '" type="button" data-template-text="' + escape(template.text) + '">' + escape(template.text) + "</button>";
        }).join("");
    }

    function renderTemplates(templates) {
        var featuredRoot = document.getElementById("feedTemplateGrid");
        var extraRoot = document.getElementById("feedTemplateExtra");
        var details = document.getElementById("feedTemplateMore");

        if (!featuredRoot || !extraRoot || !details) {
            return;
        }

        if (!state.featuredTemplateIds.length) {
            state.featuredTemplateIds = pickFeaturedTemplateIds(templates);
        }

        var groups = splitTemplates(templates);
        renderTemplateGroup(featuredRoot, groups.featured);
        renderTemplateGroup(extraRoot, groups.extra);
        details.hidden = groups.extra.length === 0;
        renderComposeConfirm();
    }

    function renderComposeConfirm() {
        var root = document.getElementById("feedComposeConfirm");
        var select = document.getElementById("feedMessageSelect");
        var lines = [];
        var messageText = "";

        if (!root) {
            return;
        }

        if (select && select.selectedIndex >= 0) {
            messageText = select.options[select.selectedIndex].text || "";
        }

        if (messageText) {
            lines.push('<p><strong>この内容で共有します</strong></p>');
            lines.push('<p>' + escape(messageText) + "</p>");
        }

        if (state.selectedTemplates.length) {
            lines.push('<div class="feed-compose-confirm-tags">' + state.selectedTemplates.map(function (line) {
                return '<span class="feed-post-template">' + escape(line) + "</span>";
            }).join("") + "</div>");
        }

        root.innerHTML = lines.join("");
        root.hidden = lines.length === 0;
    }

    async function loadStatus() {
        var data = await app.apiFetch(APP.routes.feedStatus);
        state.featuredTemplateIds = pickFeaturedTemplateIds(data.templates || []);
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
        var items = data.items || [];

        if (!list || !empty || !more) {
            return;
        }

        if (reset) {
            list.innerHTML = "";
        }

        appendPosts(list, items, false, !reset && items.length > 0);

        state.publicOffset += items.length;
        state.publicHasMore = Boolean(data.paging && data.paging.has_more);
        empty.hidden = items.length > 0 || state.publicOffset > 0;
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
        var root = document.getElementById("feedHistorySummary");
        if (!root) {
            return;
        }

        try {
            var data = await app.apiFetch(APP.routes.historySummary);
            renderHistorySummary(data);
        } catch (error) {
            root.innerHTML = '<article class="feed-history-stat"><span class="feed-post-meta">履歴を見るにはログインが必要です。</span><strong>--</strong></article>';
        }
    }

    async function loadHistory(reset) {
        var list = document.getElementById("feedHistoryList");
        var empty = document.getElementById("feedHistoryEmpty");
        var more = document.getElementById("feedHistoryMore");

        if (!list || !empty || !more) {
            return;
        }

        if (reset) {
            state.historyOffset = 0;
            list.innerHTML = "";
        }

        try {
            var data = await app.apiFetch(APP.routes.historyList + "?limit=" + state.limit + "&offset=" + state.historyOffset);
            var items = data.items || [];
            appendPosts(list, items, true, !reset && items.length > 0);
            state.historyOffset += items.length;
            state.historyHasMore = Boolean(data.paging && data.paging.has_more);
            empty.hidden = items.length > 0 || state.historyOffset > 0;
            more.hidden = !state.historyHasMore;
        } catch (error) {
            empty.hidden = false;
            var message = empty.querySelector("p");
            if (message) {
                message.textContent = "履歴を見るにはログインが必要です。";
            }
            more.hidden = true;
        }
    }

    function updateRenderedPost(post) {
        document.querySelectorAll('[data-feed-post-id="' + post.id + '"]').forEach(function (card) {
            var button = card.querySelector("[data-feed-like]");
            if (!button) {
                return;
            }

            button.classList.toggle("is-active", Boolean(post.liked_by_viewer));
            var count = button.querySelector("span:last-child");
            if (count) {
                count.textContent = String(post.likes_count || 0);
            }
        });
    }

    async function submitPost() {
        var data = await app.apiFetch(APP.routes.feedList, {
            method: "POST",
            body: {
                template_lines: state.selectedTemplates,
                icon_key: state.selectedIconKey,
                message_variant: state.selectedMessageVariant
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

    async function toggleLike(id) {
        if (!state.status || !state.status.authenticated) {
            app.showToast("いいねするにはログインが必要です。");
            window.setTimeout(function () {
                window.location.href = APP.pages.settings;
            }, 400);
            return;
        }

        var data = await app.apiFetch(APP.routes.feedLike + "?id=" + encodeURIComponent(id), {
            method: "POST"
        });

        if (data.post) {
            updateRenderedPost(data.post);
        }

        if (data.message) {
            app.showToast(
                data.message,
                data.post && data.post.owned_by_viewer && data.post.liked_by_viewer
                    ? { icon: APP.assets && APP.assets.mitchie01 ? APP.assets.mitchie01 : "" }
                    : undefined
            );
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        var composeForm = document.getElementById("feedComposeForm");
        var modeTabs = document.getElementById("feedModeTabs");
        var messageSelect = document.getElementById("feedMessageSelect");
        var iconGrid = document.getElementById("feedIconGrid");
        var publicMore = document.getElementById("feedPublicMore");
        var historyMore = document.getElementById("feedHistoryMore");

        updateTabUI();

        if (composeForm) {
            composeForm.addEventListener("submit", function (event) {
                event.preventDefault();
                submitPost().catch(function (error) {
                    console.error(error);
                    app.showToast(error.message);
                });
            });

            composeForm.addEventListener("click", function (event) {
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
                    app.showToast("定型文は3つまで選べます。");
                }

                renderTemplates(state.status ? (state.status.templates || []) : []);
                renderComposeConfirm();
            });
        }

        if (modeTabs) {
            modeTabs.addEventListener("click", function (event) {
                var button = event.target.closest("[data-feed-tab]");
                if (!button) {
                    return;
                }
                state.tab = button.getAttribute("data-feed-tab");
                updateTabUI();
            });
        }

        if (messageSelect) {
            messageSelect.addEventListener("change", function (event) {
                state.selectedMessageVariant = event.target.value;
                renderComposeConfirm();
            });
        }

        if (iconGrid) {
            iconGrid.addEventListener("click", function (event) {
                var option = event.target.closest(".feed-icon-option");
                if (!option) {
                    return;
                }
                state.selectedIconKey = option.querySelector("input").value;
                renderIcons(state.status ? (state.status.icons || []) : []);
                renderComposeConfirm();
            });
        }

        if (publicMore) {
            publicMore.addEventListener("click", function () {
                loadPublic(false).catch(function (error) {
                    console.error(error);
                    app.showToast(error.message);
                });
            });
        }

        if (historyMore) {
            historyMore.addEventListener("click", function () {
                loadHistory(false).catch(function (error) {
                    console.error(error);
                    app.showToast(error.message);
                });
            });
        }

        document.addEventListener("click", function (event) {
            var deleteButton = event.target.closest("[data-feed-delete]");
            if (deleteButton) {
                deletePost(deleteButton.getAttribute("data-feed-delete")).catch(function (error) {
                    console.error(error);
                    app.showToast(error.message);
                });
                return;
            }

            var likeButton = event.target.closest("[data-feed-like]");
            if (likeButton) {
                toggleLike(likeButton.getAttribute("data-feed-like")).catch(function (error) {
                    console.error(error);
                    app.showToast(error.message);
                });
            }
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
