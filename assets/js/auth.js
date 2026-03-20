(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
        if (!window.MitchieApp) {
            return;
        }

        window.MitchieApp.loadMe().catch(function (error) {
            console.error(error);
        });
    });
}());
