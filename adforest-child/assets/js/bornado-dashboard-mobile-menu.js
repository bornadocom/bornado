(function () {
    "use strict";

    var config = window.BornadoDashboardMenu || {};
    var breakpoint = parseInt(config.breakpoint, 10) || 991;

    function isMobileViewport() {
        return window.innerWidth <= breakpoint;
    }

    function getCloseIconMarkup() {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.4 5l.7.7L12 10.59l4.9-4.89.7-.7 1.4 1.41-.7.7L13.41 12l4.89 4.9.7.7-1.41 1.4-.7-.7L12 13.41l-4.9 4.89-.7.7-1.4-1.41.7-.7L10.59 12 5.7 7.1l-.7-.7L6.4 5z"/></svg>';
    }

    function initSidebarSheet() {
        var body = document.body;
        var sidebar = document.querySelector(".sidebar-nav-wrapper");
        var overlay = document.querySelector(".overlay");
        var menuToggle = document.getElementById("menu-toggle");
        var mainWrapper = document.querySelector(".main-wrapper");
        var closeButton = null;

        if (!body || !sidebar || !overlay || !menuToggle) {
            return;
        }

        sidebar.setAttribute("dir", "rtl");

        function populateSidebarSheetBrand(top) {
            var brandHost = top ? top.querySelector(".bornado-dashboard-sheet__brand") : null;
            var logoLink = sidebar.querySelector(".navbar-logo a");

            if (!brandHost) {
                return;
            }

            brandHost.innerHTML = "";
            if (!logoLink) {
                return;
            }

            brandHost.appendChild(logoLink.cloneNode(true));
        }

        function ensureSidebarSheetTop() {
            var existingTop = sidebar.querySelector(".bornado-dashboard-sheet__top");

            if (!isMobileViewport()) {
                if (existingTop) {
                    existingTop.remove();
                }
                return null;
            }

            if (existingTop) {
                populateSidebarSheetBrand(existingTop);
                return existingTop;
            }

            var top = document.createElement("div");
            top.className = "bornado-dashboard-sheet__top";
            top.innerHTML =
                '<span class="bornado-dashboard-sheet__handle" aria-hidden="true"></span>' +
                '<div class="bornado-dashboard-sheet__bar">' +
                    '<strong class="bornado-dashboard-sheet__title"></strong>' +
                    '<span class="bornado-dashboard-sheet__brand"></span>' +
                    '<button type="button" class="bornado-dashboard-sheet__close" aria-label=""></button>' +
                '</div>';

            top.querySelector(".bornado-dashboard-sheet__title").textContent =
                config.sidebarTitle || "منوی پروفایل";
            top.querySelector(".bornado-dashboard-sheet__close").setAttribute(
                "aria-label",
                config.closeLabel || "بستن"
            );
            top.querySelector(".bornado-dashboard-sheet__close").innerHTML = getCloseIconMarkup();
            populateSidebarSheetBrand(top);
            sidebar.insertBefore(top, sidebar.firstChild);

            return top;
        }

        function bindSidebarCloseButton() {
            closeButton = sidebar.querySelector(".bornado-dashboard-sheet__close");

            if (closeButton && !closeButton.hasAttribute("data-bornado-bound")) {
                closeButton.setAttribute("data-bornado-bound", "1");
                closeButton.addEventListener("click", function (event) {
                    event.preventDefault();
                    closeSidebar();
                });
            }
        }

        function syncSidebarState() {
            var isOpen = isMobileViewport() && sidebar.classList.contains("active");
            body.classList.toggle("bornado-dashboard-sidebar-open", isOpen);
            overlay.classList.toggle("active", isOpen);
            menuToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
            sidebar.setAttribute("aria-hidden", isOpen ? "false" : "true");
        }

        function closeSidebar() {
            sidebar.classList.remove("active");
            if (mainWrapper) {
                mainWrapper.classList.remove("active");
            }
            syncSidebarState();
        }

        ensureSidebarSheetTop();
        bindSidebarCloseButton();

        if (!menuToggle.hasAttribute("data-bornado-bound")) {
            menuToggle.setAttribute("data-bornado-bound", "1");
            menuToggle.setAttribute("aria-expanded", "false");
            menuToggle.setAttribute("aria-controls", "bornado-dashboard-sidebar-sheet");
            sidebar.setAttribute("id", "bornado-dashboard-sidebar-sheet");
            menuToggle.addEventListener("click", function () {
                window.setTimeout(syncSidebarState, 0);
            });
        }

        if (typeof MutationObserver !== "undefined" && !sidebar._bornadoSidebarObserver) {
            sidebar._bornadoSidebarObserver = new MutationObserver(syncSidebarState);
            sidebar._bornadoSidebarObserver.observe(sidebar, {
                attributes: true,
                attributeFilter: ["class"]
            });
        }

        window.addEventListener("resize", function () {
            if (!isMobileViewport()) {
                body.classList.remove("bornado-dashboard-sidebar-open");
                overlay.classList.remove("active");
                if (mainWrapper) {
                    mainWrapper.classList.remove("active");
                }
            }
            ensureSidebarSheetTop();
            bindSidebarCloseButton();
            syncSidebarState();
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && sidebar.classList.contains("active") && isMobileViewport()) {
                closeSidebar();
            }
        });

        syncSidebarState();
    }

    function initProfileSheet() {
        var body = document.body;
        var trigger = document.getElementById("profile");
        var profileBox = trigger ? trigger.closest(".profile-box") : null;
        var menu = profileBox ? profileBox.querySelector(".dropdown-menu") : null;
        var closeButton = null;

        if (!body || !trigger || !profileBox || !menu) {
            return;
        }

        var backdrop = document.querySelector(".bornado-mobile-sheet-backdrop");
        if (!backdrop) {
            backdrop = document.createElement("button");
            backdrop.type = "button";
            backdrop.className = "bornado-mobile-sheet-backdrop";
            backdrop.setAttribute("aria-label", config.closeLabel || "بستن");
            document.body.appendChild(backdrop);
        }

        function ensureProfileSheetTop() {
            var existingTopItem = menu.querySelector(".bornado-profile-sheet__top");

            if (!isMobileViewport()) {
                if (existingTopItem) {
                    existingTopItem.remove();
                }
                return null;
            }

            if (existingTopItem) {
                return existingTopItem;
            }

            var topItem = document.createElement("li");
            topItem.className = "bornado-profile-sheet__top";
            topItem.innerHTML =
                '<span class="bornado-profile-sheet__handle" aria-hidden="true"></span>' +
                '<strong class="bornado-profile-sheet__title"></strong>' +
                '<button type="button" class="bornado-profile-sheet__close" aria-label=""></button>';

            topItem.querySelector(".bornado-profile-sheet__title").textContent =
                config.profileTitle || "حساب کاربری";
            topItem.querySelector(".bornado-profile-sheet__close").setAttribute(
                "aria-label",
                config.closeLabel || "بستن"
            );
            topItem.querySelector(".bornado-profile-sheet__close").innerHTML = getCloseIconMarkup();
            menu.insertBefore(topItem, menu.firstChild);

            return topItem;
        }

        function bindProfileCloseButton() {
            closeButton = menu.querySelector(".bornado-profile-sheet__close");

            if (closeButton && !closeButton.hasAttribute("data-bornado-bound")) {
                closeButton.setAttribute("data-bornado-bound", "1");
                closeButton.addEventListener("click", function (event) {
                    event.preventDefault();
                    hideProfileMenu();
                });
            }
        }

        function syncProfileState() {
            var isOpen = isMobileViewport() && menu.classList.contains("show");
            body.classList.toggle("bornado-profile-menu-open", isOpen);
            backdrop.classList.toggle("is-visible", isOpen);
            trigger.setAttribute("aria-expanded", isOpen ? "true" : "false");
        }

        function hideProfileMenu() {
            if (window.bootstrap && window.bootstrap.Dropdown) {
                window.bootstrap.Dropdown.getOrCreateInstance(trigger).hide();
                return;
            }

            menu.classList.remove("show");
            syncProfileState();
        }

        ensureProfileSheetTop();
        bindProfileCloseButton();

        if (!backdrop.hasAttribute("data-bornado-bound")) {
            backdrop.setAttribute("data-bornado-bound", "1");
            backdrop.addEventListener("click", function (event) {
                event.preventDefault();
                hideProfileMenu();
            });
        }

        trigger.addEventListener("shown.bs.dropdown", function () {
            syncProfileState();
            if (!isMobileViewport()) {
                return;
            }

            window.setTimeout(function () {
                if (closeButton && typeof closeButton.focus === "function") {
                    closeButton.focus();
                }
            }, 80);
        });

        trigger.addEventListener("hidden.bs.dropdown", syncProfileState);

        if (typeof MutationObserver !== "undefined" && !menu._bornadoProfileObserver) {
            menu._bornadoProfileObserver = new MutationObserver(syncProfileState);
            menu._bornadoProfileObserver.observe(menu, {
                attributes: true,
                attributeFilter: ["class"]
            });
        }

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && menu.classList.contains("show") && isMobileViewport()) {
                hideProfileMenu();
            }
        });

        window.addEventListener("resize", function () {
            if (!isMobileViewport()) {
                ensureProfileSheetTop();
                body.classList.remove("bornado-profile-menu-open");
                backdrop.classList.remove("is-visible");
                return;
            }

            ensureProfileSheetTop();
            bindProfileCloseButton();
            syncProfileState();
        });

        syncProfileState();
    }

    function getManageAdsPageTypes() {
        return [
            "my_ads",
            "feature_ads",
            "featured_ads",
            "rej_ads",
            "rejected_ads",
            "inactive_ads",
            "fav_ads",
            "expire_ads",
            "expired_ads"
        ];
    }

    function getCurrentPageType() {
        try {
            return new window.URL(window.location.href).searchParams.get("page_type") || "";
        } catch (error) {
            return "";
        }
    }

    function isManageAdsPage() {
        return getManageAdsPageTypes().indexOf(getCurrentPageType()) !== -1;
    }

    function normalizeText(value) {
        return String(value || "").replace(/\s+/g, " ").trim();
    }

    function getActionLabel(node) {
        if (!node) {
            return "";
        }

        if (node.classList.contains("more-btn")) {
            return "اطلاعات";
        }

        if (node.classList.contains("ad_package_info")) {
            return "اطلاعات";
        }

        if (node.classList.contains("edit")) {
            return "ویرایش";
        }

        if (node.classList.contains("remove_ad")) {
            return "حذف";
        }

        if (node.classList.contains("remove_fav_ad")) {
            return "حذف";
        }

        if (node.classList.contains("bump_it_up_new_pkg") || node.classList.contains("lni-arrow-up-circle")) {
            return "ارتقا";
        }

        if (node.classList.contains("sb_make_feature_ad_new_pkg")) {
            return "ویژه";
        }

        if (node.classList.contains("ad_status")) {
            var statusValue = normalizeText(node.getAttribute("data-value"));
            if (statusValue === "active") {
                return "فعال";
            }
            if (statusValue === "expired") {
                return "منقضی";
            }
            if (statusValue === "sold") {
                return "فروخته شد";
            }
        }

        var explicitLabel = normalizeText(
            node.getAttribute("data-action-label") ||
            node.getAttribute("title") ||
            node.getAttribute("aria-label")
        );

        if (explicitLabel) {
            return explicitLabel;
        }

        return normalizeText(node.textContent);
    }

    function getCellText(cell) {
        return cell ? normalizeText(cell.textContent) : "";
    }

    function getProductMetaHost(titleCell) {
        var product = titleCell ? titleCell.querySelector(".product") : null;
        var existingHost = null;
        var contentHost = null;

        if (!product) {
            return null;
        }

        Array.prototype.some.call(product.children || [], function (child) {
            if (!child || child.classList.contains("image")) {
                return false;
            }

            if (child.tagName === "A" || child.classList.contains("bornado-ads-content")) {
                existingHost = child;
                return true;
            }

            return false;
        });

        if (existingHost) {
            return existingHost;
        }

        contentHost = document.createElement("div");
        contentHost.className = "bornado-ads-content";

        Array.prototype.slice.call(product.childNodes).forEach(function (node) {
            if (!node || (node.nodeType === 1 && node.classList.contains("image"))) {
                return;
            }

            if (node.nodeType === 3 && !normalizeText(node.textContent)) {
                return;
            }

            contentHost.appendChild(node);
        });

        if (!contentHost.childNodes.length) {
            return null;
        }

        product.appendChild(contentHost);
        return contentHost;
    }

    function injectCompactMeta(row) {
        if (!row) {
            return;
        }

        var titleCell = row.children && row.children[0] ? row.children[0] : null;
        var priceCell = row.children && row.children[1] ? row.children[1] : null;
        var statusCell = row.children && row.children[2] ? row.children[2] : null;
        var metaHost = getProductMetaHost(titleCell);
        var existingMeta = titleCell ? titleCell.querySelector(".bornado-ads-compact-meta") : null;

        if (!metaHost) {
            return;
        }

        if (existingMeta) {
            existingMeta.remove();
        }

        var priceText = getCellText(priceCell);
        var statusText = getCellText(statusCell);
        if (!priceText && !statusText) {
            return;
        }

        var meta = document.createElement("div");
        meta.className = "bornado-ads-compact-meta";

        if (priceText) {
            var priceNode = document.createElement("span");
            priceNode.className = "bornado-ads-compact-meta__item is-price";
            priceNode.textContent = "قیمت: " + priceText;
            meta.appendChild(priceNode);
        }

        if (statusText) {
            var statusNode = document.createElement("span");
            statusNode.className = "bornado-ads-compact-meta__item is-status";
            statusNode.textContent = "وضعیت: " + statusText;
            meta.appendChild(statusNode);
        }

        metaHost.appendChild(meta);
        row.classList.add("bornado-ads-row--enhanced");
    }

    function decorateAdsActionControls(scope) {
        if (!scope) {
            return;
        }

        Array.prototype.forEach.call(
            scope.querySelectorAll(".ad_action_container a, .ad_action_container button, .ad_action_container span, td > .remove_fav_ad, td > .more-btn"),
            function (node) {
                var label = getActionLabel(node);
                if (label) {
                    node.setAttribute("data-action-label", label);
                }
            }
        );
    }

    function localizeDropdownMenuItems(scope) {
        if (!scope) {
            return;
        }

        Array.prototype.forEach.call(
            scope.querySelectorAll(".dropdown-menu .dropdown-item a, .dropdown-menu .dropdown-item button"),
            function (node) {
                var label = getActionLabel(node);
                if (!label) {
                    return;
                }

                node.setAttribute("data-action-label", label);
                if (normalizeText(node.textContent) !== label) {
                    node.textContent = label;
                }
            }
        );
    }

    function setAdsTableLabels(table) {
        if (!table) {
            return;
        }

        var headers = Array.prototype.map.call(table.querySelectorAll("thead th"), function (th) {
            return normalizeText(th.textContent);
        });

        Array.prototype.forEach.call(table.querySelectorAll("tbody tr"), function (row) {
            var cells = row.children;
            Array.prototype.forEach.call(cells, function (cell, index) {
                if (!cell || cell.tagName !== "TD") {
                    return;
                }

                if (cell.hasAttribute("colspan")) {
                    cell.removeAttribute("data-label");
                    return;
                }

                var label = headers[index] || "";
                if (label) {
                    cell.setAttribute("data-label", label);
                }
            });
        });
    }

    function enhanceAdsTables() {
        if (!isManageAdsPage()) {
            return;
        }

        Array.prototype.forEach.call(document.querySelectorAll(".top-selling-table"), function (table) {
            table.classList.add("bornado-dashboard-ads-table");
            if (table.parentNode && table.parentNode.classList) {
                table.parentNode.classList.add("bornado-ads-table-wrap");
            }
            setAdsTableLabels(table);
            decorateAdsActionControls(table);
            localizeDropdownMenuItems(table);
            Array.prototype.forEach.call(table.querySelectorAll("tbody tr"), function (row) {
                injectCompactMeta(row);
            });
        });
    }

    function buildManageAdsSwitcher() {
        if (!isManageAdsPage()) {
            return;
        }

        var currentCard = document.querySelector(".top-selling-table");
        var hostCard = currentCard ? currentCard.closest(".card-style") : null;
        var sidebarMenu = document.querySelector("#ddmenu_2");

        if (!hostCard || !sidebarMenu || hostCard.parentNode.querySelector(".bornado-ads-mobile-switcher")) {
            return;
        }

        var links = Array.prototype.slice.call(sidebarMenu.querySelectorAll("a[href]")).filter(function (link) {
            return normalizeText(link.textContent) !== "";
        });

        if (!links.length) {
            return;
        }

        var switcher = document.createElement("section");
        switcher.className = "bornado-ads-mobile-switcher";

        var currentPageType = getCurrentPageType();
        var currentLabel = "بخش موردنظر را انتخاب کنید";
        var sheetId = "bornado-ads-mobile-switcher-sheet";
        var header = document.createElement("div");
        header.className = "bornado-ads-mobile-switcher__header";
        header.innerHTML =
            '<span class="bornado-ads-mobile-switcher__eyebrow">مدیریت آگهی‌ها</span>' +
            '<strong class="bornado-ads-mobile-switcher__title">دسترسی سریع به همه بخش‌ها</strong>';
        switcher.appendChild(header);

        var trigger = document.createElement("button");
        trigger.type = "button";
        trigger.className = "bornado-mobile-choice__trigger bornado-ads-mobile-switcher__trigger";
        trigger.setAttribute("aria-expanded", "false");
        trigger.setAttribute("aria-controls", sheetId);
        trigger.innerHTML =
            '<span class="bornado-mobile-choice__trigger-copy">' +
                '<span class="bornado-mobile-choice__trigger-label">بخش انتخاب شده</span>' +
                '<span class="bornado-mobile-choice__summary">بخش موردنظر را انتخاب کنید</span>' +
            "</span>" +
            '<span class="bornado-mobile-choice__trigger-icon" aria-hidden="true">' +
                '<svg viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>' +
            "</span>";

        var sheet = document.createElement("div");
        sheet.className = "bornado-mobile-choice__sheet bornado-ads-mobile-switcher__sheet";
        sheet.id = sheetId;
        sheet.hidden = true;
        sheet.innerHTML =
            '<button type="button" class="bornado-mobile-choice__backdrop" aria-label="بستن"></button>' +
            '<div class="bornado-mobile-choice__panel" role="dialog" aria-modal="true" aria-label="بخش مدیریت آگهی‌ها">' +
                '<div class="bornado-mobile-choice__handle" aria-hidden="true"></div>' +
                '<div class="bornado-mobile-choice__panel-head">' +
                    '<h4 class="bornado-mobile-choice__panel-title">انتخاب بخش</h4>' +
                    '<button type="button" class="bornado-mobile-choice__close" aria-label="بستن">' + getCloseIconMarkup() + "</button>" +
                "</div>" +
                '<div class="bornado-mobile-choice__list" role="listbox"></div>' +
            "</div>";

        var list = sheet.querySelector(".bornado-mobile-choice__list");
        var backdrop = sheet.querySelector(".bornado-mobile-choice__backdrop");
        var closeButton = sheet.querySelector(".bornado-mobile-choice__close");
        var summary = trigger.querySelector(".bornado-mobile-choice__summary");
        var redirectTimer = null;

        function setSheetState(isOpen) {
            switcher.classList.toggle("is-open", !!isOpen);
            trigger.setAttribute("aria-expanded", isOpen ? "true" : "false");
            sheet.hidden = !isOpen;
            document.body.classList.toggle("bornado-mobile-choice-open", !!isOpen);
        }

        function markSelectedManageAdsItem(activeItem, label) {
            Array.prototype.forEach.call(list.querySelectorAll(".bornado-mobile-choice__item"), function (item) {
                var isActive = item === activeItem;
                item.classList.toggle("is-selected", isActive);
                item.setAttribute("aria-selected", isActive ? "true" : "false");
            });
            if (label) {
                summary.textContent = label;
            }
        }

        links.forEach(function (link) {
            var item = document.createElement("button");
            var pageType = "";
            var label = normalizeText(link.textContent);

            try {
                pageType = new window.URL(link.href, window.location.origin).searchParams.get("page_type") || "";
            } catch (error) {
                pageType = "";
            }

            item.type = "button";
            item.className = "bornado-mobile-choice__item";
            item.setAttribute("role", "option");
            item.setAttribute("data-href", link.href);
            item.innerHTML =
                '<span class="bornado-mobile-choice__item-copy">' +
                    '<span class="bornado-mobile-choice__item-label"></span>' +
                "</span>" +
                '<span class="bornado-mobile-choice__check" aria-hidden="true"></span>';
            item.querySelector(".bornado-mobile-choice__item-label").textContent = label;

            if (pageType && pageType === currentPageType) {
                item.classList.add("is-selected");
                item.setAttribute("aria-selected", "true");
                currentLabel = label;
            } else {
                item.setAttribute("aria-selected", "false");
            }
            item.addEventListener("click", function () {
                var targetHref = item.getAttribute("data-href");
                if (!targetHref) {
                    return;
                }
                if (redirectTimer) {
                    window.clearTimeout(redirectTimer);
                }
                markSelectedManageAdsItem(item, label);
                redirectTimer = window.setTimeout(function () {
                    setSheetState(false);
                    window.setTimeout(function () {
                        window.location.href = targetHref;
                    }, 80);
                }, 120);
            });
            list.appendChild(item);
        });

        summary.textContent = currentLabel;
        trigger.addEventListener("click", function () {
            setSheetState(sheet.hidden);
        });

        if (backdrop) {
            backdrop.addEventListener("click", function () {
                setSheetState(false);
            });
        }

        if (closeButton) {
            closeButton.addEventListener("click", function () {
                setSheetState(false);
            });
        }

        switcher.appendChild(trigger);
        switcher.appendChild(sheet);
        hostCard.parentNode.insertBefore(switcher, hostCard);
    }

    function watchAdsTableChanges() {
        if (!isManageAdsPage() || typeof MutationObserver === "undefined") {
            return;
        }

        Array.prototype.forEach.call(document.querySelectorAll(".top-selling-table tbody"), function (tbody) {
            if (tbody._bornadoAdsObserver) {
                return;
            }

            tbody._bornadoAdsObserver = new MutationObserver(function () {
                enhanceAdsTables();
            });

            tbody._bornadoAdsObserver.observe(tbody, {
                childList: true
            });
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        initSidebarSheet();
        initProfileSheet();
        buildManageAdsSwitcher();
        enhanceAdsTables();
        watchAdsTableChanges();

        if (window.jQuery) {
            window.jQuery(document).ajaxComplete(function () {
                buildManageAdsSwitcher();
                enhanceAdsTables();
                watchAdsTableChanges();
            });
        }
    });
})();
