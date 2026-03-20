(function () {
    "use strict";

    if (!window.MitchieApp) {
        return;
    }

    var app = window.MitchieApp;
    var APP = app.APP;
    var currentSortMode = "manual";
    var currentSearchQuery = "";
    var DELETE_HOLD_MS = 900;
    var reorderState = {
        active: null,
        ignoreClickUntil: 0
    };
    var deleteHoldState = {
        button: null,
        card: null,
        pointerId: null,
        startX: 0,
        startY: 0,
        timer: 0,
        deleting: false
    };

    function renderTodo(todo) {
        var priorityDotClass = todo.priority === "high"
            ? "priority-dot high"
            : (todo.priority === "low" ? "priority-dot low" : "priority-dot medium");
        var isDoing = Boolean(todo.is_doing) && !todo.is_done;

        return [
            '<article class="task-card' + (todo.is_done ? ' is-done' : '') + (isDoing ? ' is-doing' : '') + '" data-todo-id="' + todo.id + '">',
            '  <button class="task-check-button" type="button" data-action="toggle" aria-label="' + (todo.is_done ? '未完了に戻す' : '完了にする') + '">',
            todo.is_done ? '<span class="material-symbols-outlined">check</span>' : "",
            "  </button>",
            '  <div class="task-card-body">',
            '    <div class="task-card-heading">',
            '      <h3 class="task-card-title">' + app.escapeHtml(todo.title) + '</h3>',
            '      <div class="task-card-tools">',
            '        <button class="todo-action-button todo-mode-button' + (isDoing ? ' is-active' : '') + '" type="button" data-action="doing" aria-pressed="' + (isDoing ? 'true' : 'false') + '" aria-label="DOINGモード切り替え">',
            '          <span class="todo-mode-button-text">' + (isDoing ? 'DOING' : 'TODO') + '</span>',
            "        </button>",
            '        <a class="todo-action-button todo-action-icon" href="' + APP.pages.taskForm + '?id=' + todo.id + '" aria-label="編集">',
            '          <span class="material-symbols-outlined">edit</span>',
            "        </a>",
            '        <button class="todo-action-button todo-action-icon" type="button" data-action="delete" aria-label="削除">',
            '          <span class="material-symbols-outlined">delete</span>',
            "        </button>",
            '        <span class="' + priorityDotClass + '"></span>',
            "      </div>",
            "    </div>",
            (todo.description ? '    <p class="task-card-description">' + app.escapeHtml(todo.description) + '</p>' : ""),
            '    <div class="task-card-meta">',
            (todo.category_name ? '      <span class="task-pill task-pill-category">' + app.escapeHtml(todo.category_name).toUpperCase() + '</span>' : ""),
            (todo.due_date ? '      <span class="task-pill task-pill-time"><span class="material-symbols-outlined">schedule</span>' + app.escapeHtml(app.formatDateJP(todo.due_date)) + "</span>" : ""),
            "    </div>",
            "  </div>",
            "</article>"
        ].join("");
    }

    async function toggleDoing(id) {
        await app.apiFetch(APP.routes.todoDoing + "?id=" + encodeURIComponent(id), {
            method: "PATCH"
        });
        return loadHomeTodos();
    }

    async function toggleTodo(id) {
        await app.apiFetch(APP.routes.todoToggle + "?id=" + encodeURIComponent(id), {
            method: "PATCH"
        });
        app.showToast("タスクの状態を更新しました。");
        return loadHomeTodos();
    }

    function animateTodoRemoval(card) {
        return new Promise(function (resolve) {
            if (!card || !card.parentNode) {
                resolve();
                return;
            }

            var rect = card.getBoundingClientRect();
            card.style.height = rect.height + "px";
            card.style.pointerEvents = "none";

            window.requestAnimationFrame(function () {
                card.classList.add("is-deleting-away");
                card.style.height = "0px";
                card.style.marginTop = "0px";
                card.style.marginBottom = "0px";
                card.style.paddingTop = "0px";
                card.style.paddingBottom = "0px";
                card.style.opacity = "0";
            });

            window.setTimeout(function () {
                if (card.parentNode) {
                    card.parentNode.removeChild(card);
                }
                resolve();
            }, 320);
        });
    }

    async function deleteTodo(id, card) {
        await app.apiFetch(APP.routes.todoItem + "?id=" + encodeURIComponent(id), {
            method: "DELETE"
        });
        await animateTodoRemoval(card);
        app.showToast("タスクを削除しました。");
        return loadHomeTodos();
    }

    function resetDeleteHoldState() {
        clearTimeout(deleteHoldState.timer);

        if (deleteHoldState.card) {
            deleteHoldState.card.classList.remove("is-delete-arming");
            deleteHoldState.card.classList.remove("is-delete-committing");
        }

        deleteHoldState.button = null;
        deleteHoldState.card = null;
        deleteHoldState.pointerId = null;
        deleteHoldState.startX = 0;
        deleteHoldState.startY = 0;
        deleteHoldState.timer = 0;
        deleteHoldState.deleting = false;
    }

    function beginDeleteHold(button, event) {
        var card = button.closest(".task-card");
        if (!card) {
            return;
        }

        resetDeleteHoldState();

        deleteHoldState.button = button;
        deleteHoldState.card = card;
        deleteHoldState.pointerId = event.pointerId;
        deleteHoldState.startX = event.clientX;
        deleteHoldState.startY = event.clientY;
        card.classList.add("is-delete-arming");

        deleteHoldState.timer = window.setTimeout(function () {
            var id = card.getAttribute("data-todo-id");
            deleteHoldState.deleting = true;
            card.classList.add("is-delete-committing");
            deleteTodo(id, card).catch(function (error) {
                console.error(error);
                app.showToast(error.message);
            }).finally(function () {
                resetDeleteHoldState();
            });
        }, DELETE_HOLD_MS);
    }

    function cancelDeleteHold(pointerId) {
        if (!deleteHoldState.button) {
            return;
        }

        if (pointerId != null && deleteHoldState.pointerId !== pointerId) {
            return;
        }

        if (deleteHoldState.deleting) {
            return;
        }

        resetDeleteHoldState();
    }

    function groupTodos(todos) {
        return todos.reduce(function (acc, todo) {
            if (todo.is_done) {
                acc.done.push(todo);
            } else {
                acc.open.push(todo);
            }

            if (todo.due_date === APP.today) {
                acc.today += 1;
            }

            return acc;
        }, { open: [], done: [], today: 0 });
    }

    function priorityRank(priority) {
        if (priority === "high") {
            return 0;
        }
        if (priority === "medium") {
            return 1;
        }
        return 2;
    }

    function sortTodos(todos) {
        if (currentSortMode === "manual") {
            return todos.slice();
        }

        return todos.slice().sort(function (left, right) {
            if (currentSortMode === "due") {
                if (left.due_date && right.due_date && left.due_date !== right.due_date) {
                    return left.due_date.localeCompare(right.due_date);
                }
                if (left.due_date && !right.due_date) {
                    return -1;
                }
                if (!left.due_date && right.due_date) {
                    return 1;
                }
            }

            if (currentSortMode === "priority") {
                var leftRank = priorityRank(left.priority);
                var rightRank = priorityRank(right.priority);
                if (leftRank !== rightRank) {
                    return leftRank - rightRank;
                }
            }

            if (left.due_date && right.due_date && left.due_date !== right.due_date) {
                return left.due_date.localeCompare(right.due_date);
            }

            return Number(left.sort_order || 0) - Number(right.sort_order || 0);
        });
    }

    function matchesSearch(todo) {
        if (!currentSearchQuery) {
            return true;
        }

        var haystack = [
            todo.title || "",
            todo.description || "",
            todo.category_name || ""
        ].join(" ").toLowerCase();

        return haystack.indexOf(currentSearchQuery) !== -1;
    }

    function updateSortButtons() {
        document.querySelectorAll("[data-sort-mode]").forEach(function (button) {
            button.classList.toggle("is-active", button.getAttribute("data-sort-mode") === currentSortMode);
        });
        document.body.classList.toggle("is-sort-mode-active", currentSortMode !== "manual");
    }

    function captureListRects(list) {
        var rects = new Map();
        Array.from(list.children).forEach(function (element) {
            if (element.nodeType === 1) {
                rects.set(element, element.getBoundingClientRect());
            }
        });
        return rects;
    }

    function animateListReflow(list, previousRects) {
        Array.from(list.children).forEach(function (element) {
            var previous = previousRects.get(element);
            if (!previous) {
                return;
            }

            var next = element.getBoundingClientRect();
            var deltaX = previous.left - next.left;
            var deltaY = previous.top - next.top;

            if (!deltaX && !deltaY) {
                return;
            }

            element.animate([
                { transform: "translate(" + deltaX + "px, " + deltaY + "px)" },
                { transform: "translate(0, 0)" }
            ], {
                duration: 220,
                easing: "cubic-bezier(0.22, 1, 0.36, 1)"
            });
        });
    }

    function sortableIds(list) {
        return Array.from(list.querySelectorAll(".task-card")).map(function (card) {
            return Number(card.getAttribute("data-todo-id") || 0);
        }).filter(Boolean);
    }

    async function persistTodoOrder(list) {
        var ids = sortableIds(list);
        if (ids.length <= 1) {
            return;
        }

        await app.apiFetch(APP.routes.todoReorder, {
            method: "PATCH",
            body: {
                ids: ids,
                is_done: list.id === "todoDoneList"
            }
        });
    }

    function cleanupDragState(state) {
        if (!state) {
            return;
        }

        document.body.classList.remove("is-sorting-todos");
        state.card.classList.remove("is-dragging");
        state.card.style.position = "";
        state.card.style.left = "";
        state.card.style.top = "";
        state.card.style.width = "";
        state.card.style.height = "";
        state.card.style.zIndex = "";
        state.card.style.pointerEvents = "";
        state.card.style.transition = "";
        state.card.style.transform = "";

        if (state.placeholder && state.placeholder.parentNode) {
            state.placeholder.parentNode.removeChild(state.placeholder);
        }
    }

    function movePlaceholder(state, clientY) {
        var list = state.list;
        var placeholder = state.placeholder;
        var cards = Array.from(list.querySelectorAll(".task-card")).filter(function (card) {
            return card !== state.card;
        });
        var beforeRects = captureListRects(list);
        var nextCard = null;

        cards.some(function (card) {
            var rect = card.getBoundingClientRect();
            if (clientY < rect.top + (rect.height / 2)) {
                nextCard = card;
                return true;
            }
            return false;
        });

        if (nextCard) {
            if (placeholder.nextSibling !== nextCard || placeholder.parentNode !== list) {
                list.insertBefore(placeholder, nextCard);
                animateListReflow(list, beforeRects);
            }
            return;
        }

        if (list.lastElementChild !== placeholder) {
            list.appendChild(placeholder);
            animateListReflow(list, beforeRects);
        }
    }

    function startDrag(state) {
        var rect = state.card.getBoundingClientRect();
        var placeholder = document.createElement("div");

        placeholder.className = "task-card-placeholder";
        placeholder.style.height = rect.height + "px";

        state.dragging = true;
        state.card.classList.add("is-dragging");
        state.card.style.width = rect.width + "px";
        state.card.style.height = rect.height + "px";
        state.card.style.left = rect.left + "px";
        state.card.style.top = rect.top + "px";
        state.card.style.position = "fixed";
        state.card.style.zIndex = "30";
        state.card.style.pointerEvents = "none";
        state.placeholder = placeholder;

        state.list.insertBefore(placeholder, state.card.nextSibling);
        document.body.classList.add("is-sorting-todos");
    }

    function handleReorderPointerDown(event) {
        if (APP.page !== "home") {
            return;
        }

        if (currentSortMode !== "manual") {
            return;
        }

        if (event.pointerType === "mouse" && event.button !== 0) {
            return;
        }

        var card = event.target.closest(".task-card");
        if (!card || event.target.closest("a, button, input, textarea, select, label")) {
            return;
        }

        var list = card.parentElement;
        if (!list || !list.classList.contains("task-stream") || list.querySelectorAll(".task-card").length < 2) {
            return;
        }

        var rect = card.getBoundingClientRect();
        reorderState.active = {
            pointerId: event.pointerId,
            card: card,
            list: list,
            startX: event.clientX,
            startY: event.clientY,
            offsetX: event.clientX - rect.left,
            offsetY: event.clientY - rect.top,
            dragging: false,
            placeholder: null
        };
    }

    function handleReorderPointerMove(event) {
        var state = reorderState.active;
        if (!state || state.pointerId !== event.pointerId) {
            return;
        }

        var deltaX = event.clientX - state.startX;
        var deltaY = event.clientY - state.startY;

        if (!state.dragging) {
            if (Math.hypot(deltaX, deltaY) < 8) {
                return;
            }
            startDrag(state);
        }

        event.preventDefault();

        state.card.style.left = (event.clientX - state.offsetX) + "px";
        state.card.style.top = (event.clientY - state.offsetY) + "px";
        movePlaceholder(state, event.clientY);
    }

    function finishReorder(event, cancelled) {
        var state = reorderState.active;
        if (!state || (event && state.pointerId !== event.pointerId)) {
            return;
        }

        reorderState.active = null;

        if (!state.dragging) {
            return;
        }

        reorderState.ignoreClickUntil = Date.now() + 260;

        if (cancelled) {
            cleanupDragState(state);
            return;
        }

        var finalRect = state.placeholder.getBoundingClientRect();
        state.list.insertBefore(state.card, state.placeholder);
        state.card.style.transition = "left 220ms cubic-bezier(0.22, 1, 0.36, 1), top 220ms cubic-bezier(0.22, 1, 0.36, 1), box-shadow 220ms ease";
        state.card.style.left = finalRect.left + "px";
        state.card.style.top = finalRect.top + "px";

        window.setTimeout(function () {
            cleanupDragState(state);
            persistTodoOrder(state.list).catch(function (error) {
                console.error(error);
                app.showToast(error.message);
                loadHomeTodos().catch(function (loadError) {
                    console.error(loadError);
                });
            });
        }, 230);
    }

    function paintHomeSidebar(openTodos) {
        var totalNode = document.getElementById("homeSidebarOpenTotal");
        var breakdownNode = document.getElementById("homeSidebarOpenBreakdown");
        var upcomingNode = document.getElementById("homeUpcomingList");
        var upcomingEmptyNode = document.getElementById("homeUpcomingEmpty");

        if (totalNode) {
            totalNode.textContent = openTodos.length + "件";
        }

        if (breakdownNode) {
            var categoryCounts = openTodos.reduce(function (acc, todo) {
                var label = todo.category_name || "その他";
                acc[label] = (acc[label] || 0) + 1;
                return acc;
            }, {});

            var breakdownItems = Object.keys(categoryCounts).sort(function (left, right) {
                if (categoryCounts[right] !== categoryCounts[left]) {
                    return categoryCounts[right] - categoryCounts[left];
                }
                return left.localeCompare(right, "ja");
            });

            breakdownNode.innerHTML = breakdownItems.length
                ? breakdownItems.map(function (label) {
                    return [
                        '<div class="home-side-breakdown-row">',
                        '  <span>' + app.escapeHtml(label) + '</span>',
                        '  <strong>' + categoryCounts[label] + '件</strong>',
                        '</div>'
                    ].join("");
                }).join("")
                : '<p class="home-side-empty">未完了タスクはありません。</p>';
        }

        if (upcomingNode && upcomingEmptyNode) {
            var upcomingTodos = openTodos.filter(function (todo) {
                return Boolean(todo.due_date) && todo.due_date > APP.today;
            }).sort(function (left, right) {
                return left.due_date.localeCompare(right.due_date);
            }).slice(0, 5);

            upcomingNode.innerHTML = upcomingTodos.map(function (todo) {
                return [
                    '<article class="home-upcoming-item">',
                    '  <div>',
                    '    <strong>' + app.escapeHtml(todo.title) + '</strong>',
                    '    <p>' + app.escapeHtml(todo.category_name || "その他") + '</p>',
                    '  </div>',
                    '  <span>' + app.escapeHtml(app.formatDateJP(todo.due_date)) + '</span>',
                    '</article>'
                ].join("");
            }).join("");
            upcomingEmptyNode.hidden = upcomingTodos.length > 0;
            upcomingNode.hidden = upcomingTodos.length === 0;
        }
    }

    function paintHomeLists(grouped) {
        var openList = document.getElementById("todoOpenList");
        var doneList = document.getElementById("todoDoneList");
        var openEmpty = document.getElementById("todoOpenEmpty");
        var doneEmpty = document.getElementById("todoDoneEmpty");
        var sortedOpen = sortTodos(grouped.open).filter(matchesSearch);
        var sortedDone = sortTodos(grouped.done).filter(matchesSearch);

        openList.innerHTML = sortedOpen.map(renderTodo).join("");
        doneList.innerHTML = sortedDone.map(renderTodo).join("");
        openList.setAttribute("data-reorder-status", "open");
        doneList.setAttribute("data-reorder-status", "done");
        openEmpty.hidden = sortedOpen.length > 0;
        doneEmpty.hidden = sortedDone.length > 0;

        var homeOpenCount = document.getElementById("homeOpenCount");
        var homeTodayCount = document.getElementById("homeTodayCount");
        var doneListCount = document.getElementById("doneListCount");
        var summaryLine = document.getElementById("homeSummaryLine");

        if (homeOpenCount) {
            homeOpenCount.textContent = grouped.open.length;
        }
        if (homeTodayCount) {
            homeTodayCount.textContent = grouped.today;
        }
        if (doneListCount) {
            doneListCount.textContent = grouped.done.length + "件";
        }
        if (summaryLine) {
            summaryLine.textContent = app.formatDateJP(APP.today) + "・残り " + grouped.open.length + " 件のタスク";
        }

        paintHomeSidebar(grouped.open);
    }

    async function loadHomeTodos() {
        if (APP.page !== "home") {
            return;
        }

        var data = await app.apiFetch(APP.routes.todos);
        var grouped = groupTodos(data.todos || []);
        paintHomeLists(grouped);
    }

    async function loadCategories() {
        var data = await app.apiFetch(APP.routes.categories);
        return data.categories || [];
    }

    function renderCategoryPills(categories, selectedId) {
        var container = document.getElementById("taskCategoryPills");
        var hiddenInput = document.getElementById("taskCategory");
        if (!container || !hiddenInput) {
            return;
        }

        var items = [{
            id: "",
            name: "未設定"
        }].concat(categories.map(function (category) {
            return {
                id: String(category.id),
                name: category.name
            };
        }));

        container.innerHTML = items.map(function (item) {
            var isActive = String(selectedId || "") === String(item.id);
            return '<button class="task-category-pill' + (isActive ? ' is-active' : '') + '" type="button" data-category-value="' + app.escapeHtml(item.id) + '">' + app.escapeHtml(item.name) + "</button>";
        }).join("");
    }

    async function initTaskForm() {
        if (APP.page !== "task-form") {
            return;
        }

        var form = document.getElementById("taskForm");
        if (!form) {
            return;
        }

        var categoryInput = document.getElementById("taskCategory");
        var taskId = Number(document.getElementById("taskId").value || 0);
        var categoryList = await loadCategories();

        renderCategoryPills(categoryList, "");

        if (taskId > 0) {
            var existing = await app.apiFetch(APP.routes.todoItem + "?id=" + encodeURIComponent(taskId));
            var todo = existing.todo || {};
            document.getElementById("taskTitle").value = todo.title || "";
            document.getElementById("taskDescription").value = todo.description || "";
            document.getElementById("taskDueDate").value = todo.due_date || "";
            categoryInput.value = todo.category_id || "";
            renderCategoryPills(categoryList, todo.category_id || "");
            var priorityInput = form.querySelector('input[name="priority"][value="' + todo.priority + '"]');
            if (priorityInput) {
                priorityInput.checked = true;
            }
        }

        document.getElementById("taskCategoryPills").addEventListener("click", function (event) {
            var button = event.target.closest("[data-category-value]");
            if (!button) {
                return;
            }

            var value = button.getAttribute("data-category-value") || "";
            categoryInput.value = value;
            renderCategoryPills(categoryList, value);
        });

        form.addEventListener("submit", async function (event) {
            event.preventDefault();

            var payload = {
                title: document.getElementById("taskTitle").value.trim(),
                category_id: categoryInput.value,
                due_date: document.getElementById("taskDueDate").value,
                priority: (form.querySelector('input[name="priority"]:checked') || {}).value || "medium",
                description: document.getElementById("taskDescription").value.trim()
            };

            try {
                if (taskId > 0) {
                    await app.apiFetch(APP.routes.todoItem + "?id=" + encodeURIComponent(taskId), {
                        method: "PUT",
                        body: payload
                    });
                    app.showToast("タスクを更新しました。");
                } else {
                    await app.apiFetch(APP.routes.todos, {
                        method: "POST",
                        body: payload
                    });
                    app.showToast("タスクを保存しました。");
                }
                window.location.href = APP.pages.home;
            } catch (error) {
                app.showToast(error.message);
            }
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        if (APP.page === "home") {
            document.querySelectorAll("[data-sort-mode]").forEach(function (button) {
                button.addEventListener("click", function () {
                    var nextMode = button.getAttribute("data-sort-mode") || "manual";
                    currentSortMode = currentSortMode === nextMode ? "manual" : nextMode;
                    updateSortButtons();
                    loadHomeTodos().catch(function (error) {
                        console.error(error);
                        app.showToast(error.message);
                    });
                });
            });

            var searchInput = document.getElementById("taskSearchInput");
            if (searchInput) {
                searchInput.addEventListener("input", function () {
                    currentSearchQuery = String(searchInput.value || "").trim().toLowerCase();
                    loadHomeTodos().catch(function (error) {
                        console.error(error);
                        app.showToast(error.message);
                    });
                });
            }

            document.addEventListener("click", function (event) {
                if (reorderState.ignoreClickUntil > Date.now()) {
                    event.preventDefault();
                    return;
                }

                var actionTarget = event.target.closest("[data-action]");
                if (!actionTarget) {
                    return;
                }

                var card = actionTarget.closest("[data-todo-id]");
                if (!card) {
                    return;
                }

                var id = card.getAttribute("data-todo-id");
                var action = actionTarget.getAttribute("data-action");

                if (action === "toggle") {
                    toggleTodo(id).catch(function (error) {
                        console.error(error);
                        app.showToast(error.message);
                    });
                    return;
                }

                if (action === "doing") {
                    toggleDoing(id).catch(function (error) {
                        console.error(error);
                        app.showToast(error.message);
                    });
                }
            });

            document.addEventListener("pointerdown", function (event) {
                var deleteButton = event.target.closest('[data-action="delete"]');
                if (!deleteButton) {
                    return;
                }

                event.preventDefault();
                beginDeleteHold(deleteButton, event);
            });

            document.addEventListener("pointermove", function (event) {
                if (!deleteHoldState.button || deleteHoldState.deleting || deleteHoldState.pointerId !== event.pointerId) {
                    return;
                }

                if (Math.hypot(event.clientX - deleteHoldState.startX, event.clientY - deleteHoldState.startY) > 10) {
                    cancelDeleteHold(event.pointerId);
                }
            });

            document.addEventListener("pointerup", function (event) {
                cancelDeleteHold(event.pointerId);
            });

            document.addEventListener("pointercancel", function (event) {
                cancelDeleteHold(event.pointerId);
            });

            document.addEventListener("pointerdown", handleReorderPointerDown);
            window.addEventListener("pointermove", handleReorderPointerMove, { passive: false });
            window.addEventListener("pointerup", function (event) {
                finishReorder(event, false);
            });
            window.addEventListener("pointercancel", function (event) {
                finishReorder(event, true);
            });

            loadHomeTodos().catch(function (error) {
                console.error(error);
                app.showToast(error.message);
            });
            updateSortButtons();
        }

        initTaskForm().catch(function (error) {
            console.error(error);
            app.showToast(error.message);
        });
    });
}());
