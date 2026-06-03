(function () {
    "use strict";

    var labelMap = {
        "Category": "دسته بندی",
        "Location": "مکان",
        "Currency": "ارز",
        "Search": "جستجو",
        "Type": "نوع آگهی",
        "Condition": "وضعیت",
        "Warranty": "گارانتی",
        "Featured": "ویژه",
        "Min Price": "حداقل قیمت",
        "Max Price": "حداکثر قیمت",
        "Within (km)": "شعاع (کیلومتر)"
    };

    function translateChipLabels(root) {
        var scope = root && root.querySelectorAll ? root : document;

        scope.querySelectorAll(".adf-active-filters .adf-chip__label").forEach(function (node) {
            var raw = (node.textContent || "").trim();
            var key = raw.replace(/\s*:\s*$/, "");

            if (labelMap[key]) {
                node.textContent = labelMap[key] + ":";
            }
        });

        scope.querySelectorAll(".adf-active-filters .adf-clear-all").forEach(function (button) {
            button.textContent = "حذف همه";
        });

        scope.querySelectorAll(".adf-active-filters .adf-chip").forEach(function (chip) {
            chip.setAttribute("title", "حذف فیلتر");
        });

        scope.querySelectorAll(".adf-active-filters[aria-label]").forEach(function (bar) {
            bar.setAttribute("aria-label", "فیلترهای فعال");
        });
    }

    function init() {
        translateChipLabels(document);

        if (typeof MutationObserver === "undefined") {
            return;
        }

        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node && node.nodeType === 1) {
                        translateChipLabels(node);
                    }
                });

                if (mutation.type === "childList" || mutation.type === "attributes") {
                    translateChipLabels(mutation.target);
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ["class"]
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
