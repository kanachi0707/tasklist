(function () {
    "use strict";

    if (!window.MitchieApp || window.MitchieApp.APP.page !== "calendar") {
        return;
    }

    var app = window.MitchieApp;
    var APP = app.APP;
    var currentMonth = APP.month;
    var selectedDate = APP.today;
    var currentTodos = [];
    var currentFilter = "all";

    function pad(value) {
        return String(value).padStart(2, "0");
    }

    function toLocalYmd(date) {
        return date.getFullYear() + "-" + pad(date.getMonth() + 1) + "-" + pad(date.getDate());
    }

    function toLocalYm(date) {
        return date.getFullYear() + "-" + pad(date.getMonth() + 1);
    }

    function formatMonthLabel(month) {
        var parts = month.split("-");
        var date = new Date(Number(parts[0]), Number(parts[1]) - 1, 1);
        return date.toLocaleString("en-US", { month: "short" }).toUpperCase();
    }

    function shiftMonth(month, diff) {
        var parts = month.split("-");
        var date = new Date(Number(parts[0]), Number(parts[1]) - 1, 1);
        date.setMonth(date.getMonth() + diff);
        return toLocalYm(date);
    }

    function renderDayList(dateString) {
        var list = document.getElementById("calendarDayList");
        var empty = document.getElementById("calendarDayEmpty");
        var label = document.getElementById("selectedDateLabel");
        var count = document.getElementById("selectedDateCount");
        var items = currentTodos.filter(function (todo) {
            return todo.due_date === dateString;
        });
        var openCount = items.filter(function (todo) {
            return !todo.is_done;
        }).length;
        var doneCount = items.length - openCount;
        var visibleItems = items.filter(function (todo) {
            if (currentFilter === "open") {
                return !todo.is_done;
            }
            if (currentFilter === "done") {
                return todo.is_done;
            }
            return true;
        });

        label.textContent = app.formatDateJP(dateString);
        count.textContent = "未完了 " + openCount + "件 / 完了 " + doneCount + "件";
        list.innerHTML = visibleItems.map(function (todo) {
            var moodClass = todo.priority === "high" ? "is-high" : (todo.priority === "low" ? "is-low" : "is-medium");
            return [
                '<article class="agenda-item ' + moodClass + '">',
                '  <div class="agenda-item-body">',
                '    <div class="agenda-time"><span class="material-symbols-outlined">schedule</span>' + (todo.due_date ? app.escapeHtml(app.formatDateJP(todo.due_date)) : "日付未設定") + "</div>",
                '    <h4>' + app.escapeHtml(todo.title) + "</h4>",
                (todo.description ? '    <p>' + app.escapeHtml(todo.description) + "</p>" : ""),
                "  </div>",
                '  <a class="todo-action-button" href="' + APP.pages.taskForm + '?id=' + todo.id + '">編集</a>',
                "</article>"
            ].join("");
        }).join("");
        empty.hidden = visibleItems.length > 0;
    }

    function renderCalendar() {
        var grid = document.getElementById("calendarGrid");
        var monthLabel = document.getElementById("calendarMonthLabel");
        var subLabel = document.getElementById("calendarMonthSubLabel");
        var monthParts = currentMonth.split("-");
        var start = new Date(Number(monthParts[0]), Number(monthParts[1]) - 1, 1);
        var firstWeekday = start.getDay();
        var firstVisible = new Date(start);
        firstVisible.setDate(firstVisible.getDate() - firstWeekday);
        var weekdays = ["日", "月", "火", "水", "木", "金", "土"];
        var dotsByDate = currentTodos.reduce(function (acc, todo) {
            if (todo.due_date) {
                acc[todo.due_date] = (acc[todo.due_date] || 0) + 1;
            }
            return acc;
        }, {});

        monthLabel.textContent = formatMonthLabel(currentMonth);
        subLabel.textContent = currentMonth.replace("-", "年 ") + "月";
        grid.innerHTML = weekdays.map(function (weekday) {
            return '<div class="weekday-cell">' + weekday + "</div>";
        }).join("");

        for (var i = 0; i < 42; i += 1) {
            var date = new Date(firstVisible);
            date.setDate(firstVisible.getDate() + i);
            var dateString = toLocalYmd(date);
            var isOtherMonth = toLocalYm(date) !== currentMonth;
            var isToday = dateString === APP.today;
            var isSelected = dateString === selectedDate;
            var dots = Math.min(dotsByDate[dateString] || 0, 4);
            var button = document.createElement("button");
            button.type = "button";
            button.className = "day-cell stitch-day-cell" + (isOtherMonth ? " is-other-month" : "") + (isToday ? " is-today" : "") + (isSelected ? " is-selected" : "");
            button.dataset.date = dateString;
            button.innerHTML =
                "<span>" + date.getDate() + "</span>" +
                '<span class="day-dots">' +
                new Array(dots).fill('<span class="day-dot"></span>').join("") +
                "</span>";
            grid.appendChild(button);
        }

        renderDayList(selectedDate);
    }

    async function loadMonth() {
        var data = await app.apiFetch(APP.routes.todos + "?month=" + encodeURIComponent(currentMonth));
        currentTodos = data.todos || [];
        if (!currentTodos.some(function (todo) {
            return todo.due_date === selectedDate;
        }) && selectedDate.slice(0, 7) !== currentMonth) {
            selectedDate = currentMonth + "-01";
        }
        renderCalendar();
    }

    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll("[data-calendar-filter]").forEach(function (button) {
            button.addEventListener("click", function () {
                currentFilter = button.getAttribute("data-calendar-filter") || "all";
                document.querySelectorAll("[data-calendar-filter]").forEach(function (item) {
                    item.classList.toggle("is-active", item === button);
                });
                renderDayList(selectedDate);
            });
        });

        document.getElementById("calendarPrev").addEventListener("click", function () {
            currentMonth = shiftMonth(currentMonth, -1);
            selectedDate = currentMonth + "-01";
            loadMonth().catch(function (error) {
                console.error(error);
                app.showToast(error.message);
            });
        });

        document.getElementById("calendarNext").addEventListener("click", function () {
            currentMonth = shiftMonth(currentMonth, 1);
            selectedDate = currentMonth + "-01";
            loadMonth().catch(function (error) {
                console.error(error);
                app.showToast(error.message);
            });
        });

        document.getElementById("calendarGrid").addEventListener("click", function (event) {
            var button = event.target.closest("[data-date]");
            if (!button) {
                return;
            }
            selectedDate = button.getAttribute("data-date");
            renderCalendar();
        });

        loadMonth().catch(function (error) {
            console.error(error);
            app.showToast(error.message);
        });
    });
}());
