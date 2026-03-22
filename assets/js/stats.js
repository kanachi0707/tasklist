(function () {
    "use strict";

    if (!window.MitchieApp || window.MitchieApp.APP.page !== "stats") {
        return;
    }

    var app = window.MitchieApp;
    var APP = app.APP;
    var currentRange = "7d";

    function renderTrend(series) {
        var svg = document.getElementById("statsTrendSvg");
        var legend = document.getElementById("statsTrendLegend");
        var width = 640;
        var height = 220;
        var padding = 28;
        var max = Math.max.apply(null, series.map(function (item) { return item.count; }).concat([1]));
        var slot = (width - padding * 2) / series.length;

        svg.innerHTML = "";
        series.forEach(function (item, index) {
            var barHeight = Math.max(12, ((height - padding * 2) * item.count / max));
            var barWidth = Math.max(26, slot - 16);
            var x = padding + slot * index + (slot - barWidth) / 2;
            var y = height - padding - barHeight;
            var fill = item.count === max ? "#4a148c" : "#e2e2e4";
            svg.innerHTML += '<rect x="' + x + '" y="' + y + '" width="' + barWidth + '" height="' + barHeight + '" rx="10" fill="' + fill + '"></rect>';
        });

        legend.innerHTML = series.map(function (item) {
            return '<div class="chart-legend-item"><strong>' + item.count + '</strong><span>' + item.label + "</span></div>";
        }).join("");
    }

    function renderBreakdown(items) {
        var container = document.getElementById("statsCategoryList");
        var total = items.reduce(function (sum, item) { return sum + item.task_count; }, 0) || 1;

        container.innerHTML = items.map(function (item) {
            var ratio = Math.round((item.task_count / total) * 100);
            return [
                '<article class="breakdown-row">',
                '  <header>',
                '    <div>',
                '      <strong>' + app.escapeHtml(item.name) + '</strong>',
                '      <p>' + item.done_count + '件完了 / ' + item.task_count + '件</p>',
                "    </div>",
                '    <span>' + ratio + "%</span>",
                "  </header>",
                '  <div class="breakdown-bar"><span style="width:' + ratio + '%; background:' + app.escapeHtml(item.color) + ';"></span></div>',
                "</article>"
            ].join("");
        }).join("");
    }

    async function loadStats() {
        var summaryData = await app.apiFetch(APP.routes.statsSummary);
        var trendData = await app.apiFetch(APP.routes.statsDaily + "?range=" + encodeURIComponent(currentRange));
        var categoryData = await app.apiFetch(APP.routes.statsCategory);
        var summary = summaryData.summary;
        var rate = Math.max(0, Math.min(100, Number(summary.completion_rate || 0)));
        var bestPoint = (trendData.series || []).slice().sort(function (a, b) {
            return b.count - a.count;
        })[0];

        document.getElementById("statsCompletionRate").textContent = Math.round(rate);
        document.getElementById("statsDoneCount").textContent = summary.done_count;
        document.getElementById("statsOpenCount").textContent = summary.open_count;
        document.getElementById("statsRingLabel").textContent = Math.round(rate) + "%";
        document.getElementById("statsBestDay").textContent = bestPoint ? bestPoint.label : "-";

        var ring = document.getElementById("statsRingProgress");
        if (ring) {
            var circumference = 2 * Math.PI * 34;
            ring.style.strokeDasharray = String(circumference);
            ring.style.strokeDashoffset = String(circumference - (circumference * rate / 100));
        }

        renderTrend(trendData.series || []);
        renderBreakdown(categoryData.breakdown || []);
    }

    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll("[data-range]").forEach(function (button) {
            button.addEventListener("click", function () {
                currentRange = button.getAttribute("data-range");
                document.querySelectorAll("[data-range]").forEach(function (item) {
                    item.classList.toggle("is-active", item === button);
                });
                loadStats().catch(function (error) {
                    console.error(error);
                    app.showToast(error.message);
                });
            });
        });

        loadStats().catch(function (error) {
            console.error(error);
            app.showToast(error.message);
        });
    });
}());
