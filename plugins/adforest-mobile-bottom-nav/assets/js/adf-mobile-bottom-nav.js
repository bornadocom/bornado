(function () {
	"use strict";

	var nav = document.querySelector(".adf-mobile-bottom-nav");
	var topSearch = document.querySelector(".adf-mobile-top-search");
	var citySheet = document.querySelector(".adf-mobile-city-sheet");
	var cityPanel = document.querySelector(".adf-mobile-city-sheet__panel");
	var cityTrigger = document.querySelector(".adf-mobile-top-search__city-strip");
	var cityTitle = document.querySelector(".adf-mobile-city-sheet__title");
	var categorySheet = document.querySelector(".adf-mobile-category-sheet");
	var categoryPanel = document.querySelector(".adf-mobile-category-sheet__panel");
	var categoryTrigger = document.querySelector(".adf-mobile-top-search__category-strip");
	var categoryTitle = document.querySelector(".adf-mobile-category-sheet__title");
	var titleInput = document.querySelector(".adf-mobile-top-search__input");
	var cityLabel = document.querySelector(".adf-mobile-top-search__city-label");
	var cityInput = document.querySelector(".adf-mobile-top-search__city-input");
	var categoryLabel = document.querySelector(".adf-mobile-top-search__category-label");
	var categoryInput = document.querySelector(".adf-mobile-top-search__category-input");
	var searchForm = document.querySelector(".adf-mobile-top-search__form");
	var defaultSearchAction = searchForm ? (searchForm.getAttribute("action") || window.location.href) : window.location.href;
	var cityList = document.querySelector(".adf-mobile-city-sheet__list");
	var citySearchInput = document.querySelector(".adf-mobile-city-sheet__search");
	var categoryList = document.querySelector(".adf-mobile-category-sheet__list");
	var categorySearchInput = document.querySelector(".adf-mobile-category-sheet__search");
	var closeNodes = document.querySelectorAll("[data-filter-close]");
	var config = window.ADFMobileBottomNav || {};
	var searchCore = window.BornadoSearchCore || null;
	var cityOptions = Array.isArray(config.cities) ? config.cities : [];
	var rootCityOptions = cityOptions.slice();
	var selectedCity = config.selectedCity || null;
	var categoryOptions = Array.isArray(config.categories) ? config.categories : [];
	var rootCategoryOptions = categoryOptions.slice();
	var selectedCategory = config.selectedCategory || null;
	var rootTitle = cityTitle && cityTitle.textContent ? cityTitle.textContent : "انتخاب شهر";
	var rootCategoryTitle = categoryTitle && categoryTitle.textContent ? categoryTitle.textContent : "انتخاب دسته‌بندی";
	var lastFocusedElement = null;
	var lastScrollY = window.scrollY;
	var ticking = false;
	var activeSheet = null;
	var activePanel = null;
	var activeTrigger = null;

	function isMobileViewport() {
		return window.innerWidth <= 768;
	}

	function isAnySheetOpen() {
		return !!(activeSheet && activeSheet.classList.contains("is-open"));
	}

	function getFocusableInSheet() {
		if (!activePanel) {
			return [];
		}
		return Array.prototype.slice.call(
			activePanel.querySelectorAll('button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')
		).filter(function (node) {
			return node.offsetParent !== null;
		});
	}

	function getTrimmedTitle() {
		return titleInput ? (titleInput.value || "").trim() : "";
	}

	function prepareSearchForm() {
		if (!titleInput) {
			return;
		}

		if (searchCore && typeof searchCore.toggleFieldName === "function") {
			searchCore.toggleFieldName(titleInput, "ad_title");
			return;
		}

		var trimmedTitle = getTrimmedTitle();
		if (trimmedTitle) {
			titleInput.value = trimmedTitle;
			titleInput.setAttribute("name", "ad_title");
			return;
		}

		titleInput.value = "";
		titleInput.removeAttribute("name");
	}

	function prepareCityInput() {
		if (!cityInput) {
			return;
		}

		if (searchCore && typeof searchCore.toggleFieldName === "function") {
			searchCore.toggleFieldName(cityInput, "country_id");
			return;
		}

		if (cityInput.value) {
			cityInput.setAttribute("name", "country_id");
			return;
		}

		cityInput.value = "";
		cityInput.removeAttribute("name");
	}

	function prepareCategoryInput() {
		if (!categoryInput) {
			return;
		}

		if (searchCore && typeof searchCore.toggleFieldName === "function") {
			searchCore.toggleFieldName(categoryInput, "cat_id");
			return;
		}

		if (categoryInput.value) {
			categoryInput.setAttribute("name", "cat_id");
			return;
		}

		categoryInput.value = "";
		categoryInput.removeAttribute("name");
	}

	function getDefaultAction() {
		if (!searchForm) {
			return defaultSearchAction;
		}
		return searchForm.getAttribute("data-default-action") || defaultSearchAction;
	}

	function getAllCitiesAction() {
		if (!searchForm) {
			return getDefaultAction();
		}
		return searchForm.getAttribute("data-all-cities-action") || getDefaultAction();
	}

	function getAllCategoriesAction() {
		if (!searchForm) {
			return getDefaultAction();
		}
		return searchForm.getAttribute("data-all-categories-action") || getDefaultAction();
	}

	function getAllFiltersAction() {
		if (!searchForm) {
			return getDefaultAction();
		}
		return searchForm.getAttribute("data-all-filters-action") || getDefaultAction();
	}

	function buildCleanSearchUrl(targetUrl) {
		if (!searchForm) {
			return null;
		}

		if (searchCore && typeof searchCore.buildUrlFromForm === "function") {
			return searchCore.buildUrlFromForm(searchForm, targetUrl || getDefaultAction());
		}

		var formData = new window.FormData(searchForm);
		var searchParams = new window.URLSearchParams();
		formData.forEach(function (value, key) {
			var safeKey = key ? String(key).trim() : "";
			var safeValue = value == null ? "" : String(value).trim();
			if (safeKey !== "" && safeValue !== "") {
				searchParams.append(safeKey, safeValue);
			}
		});

		var url = new window.URL(targetUrl || getDefaultAction(), window.location.origin);
		url.search = searchParams.toString();
		return url;
	}

	function submitCurrentSearch() {
		if (!searchForm) {
			return false;
		}

		var hasCity = !!(cityInput && cityInput.value && cityInput.value.trim());
		var hasCategory = !!(categoryInput && categoryInput.value && categoryInput.value.trim());
		var targetUrl = getDefaultAction();

		if (!hasCity && !hasCategory) {
			targetUrl = getAllFiltersAction();
		} else if (!hasCity) {
			targetUrl = getAllCitiesAction();
		} else if (!hasCategory) {
			targetUrl = getAllCategoriesAction();
		}

		prepareCityInput();
		prepareCategoryInput();
		prepareSearchForm();

		var cleanUrl = buildCleanSearchUrl(targetUrl);
		if (!cleanUrl) {
			return false;
		}

		window.location.href = cleanUrl.toString();
		return true;
	}

	function submitSearchForm(targetUrl, cityValue, categoryValue) {
		if (!searchForm) {
			return false;
		}

		searchForm.setAttribute("action", targetUrl || defaultSearchAction);
		if (cityInput) {
			cityInput.value = cityValue || "";
		}
		if (categoryInput) {
			categoryInput.value = categoryValue || "";
		}
		prepareCityInput();
		prepareCategoryInput();
		prepareSearchForm();

		var cleanUrl = buildCleanSearchUrl(targetUrl || defaultSearchAction);
		if (!cleanUrl) {
			return false;
		}

		window.location.href = cleanUrl.toString();
		return true;
	}

	function updateCitySheetTitle(titleText) {
		if (!cityTitle) {
			return;
		}
		cityTitle.textContent = titleText || rootTitle;
	}

	function updateCategorySheetTitle(titleText) {
		if (!categoryTitle) {
			return;
		}
		categoryTitle.textContent = titleText || rootCategoryTitle;
	}

	function setCity(city) {
		if (!city || !cityLabel || !cityInput) {
			return;
		}
		cityLabel.textContent = city.label;
		cityInput.value = city.value;
		try {
			window.localStorage.setItem("adf_mbn_city", JSON.stringify(city));
		} catch (e) {}
	}

	function setCategory(category) {
		if (!category || !categoryLabel || !categoryInput) {
			return;
		}
		selectedCategory = category;
		categoryLabel.textContent = category.label;
		categoryInput.value = category.value;
	}

	function closeActiveSheet(skipFocusRestore) {
		if (!activeSheet || !activeTrigger) {
			return;
		}
		activeSheet.classList.remove("is-open");
		activeSheet.setAttribute("aria-hidden", "true");
		activeTrigger.setAttribute("aria-expanded", "false");
		document.body.classList.remove("adf-city-sheet-open");
		if (!skipFocusRestore && lastFocusedElement && typeof lastFocusedElement.focus === "function") {
			lastFocusedElement.focus();
		}
		activeSheet = null;
		activePanel = null;
		activeTrigger = null;
		lastFocusedElement = null;
	}

	function suppressInitialSearchInputFocus(searchInput) {
		if (!searchInput) {
			return;
		}

		searchInput.blur();
		searchInput.readOnly = true;
		searchInput.style.pointerEvents = "none";
		window.setTimeout(function () {
			searchInput.blur();
			searchInput.readOnly = false;
			searchInput.style.pointerEvents = "";
		}, 260);
	}

	function focusCurrentOption(list, itemSelector, currentValue) {
		if (!list) {
			return false;
		}

		var current = null;
		var items = list.querySelectorAll(itemSelector);
		var normalizedValue = String(currentValue || "");
		items.forEach(function (item) {
			if (!current && (item.getAttribute("data-value") || "") === normalizedValue) {
				current = item;
			}
		});
		if (!current) {
			current = list.querySelector(itemSelector);
		}
		if (!current) {
			return false;
		}

		current.focus();
		if (typeof current.scrollIntoView === "function") {
			current.scrollIntoView({ block: "nearest" });
		}
		return true;
	}

	function openSheet(sheet, panel, trigger, searchInput, onOpenSearch, focusSelectedOption) {
		if (!isMobileViewport() || !sheet || !trigger) {
			return;
		}
		if (activeSheet && activeSheet !== sheet) {
			closeActiveSheet(true);
		}
		lastFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : trigger;
		activeSheet = sheet;
		activePanel = panel;
		activeTrigger = trigger;
		sheet.classList.add("is-open");
		sheet.setAttribute("aria-hidden", "false");
		trigger.setAttribute("aria-expanded", "true");
		document.body.classList.add("adf-city-sheet-open");
		if (panel) {
			panel.setAttribute("tabindex", "-1");
		}
		if (searchInput) {
			searchInput.value = "";
			if (typeof onOpenSearch === "function") {
				onOpenSearch();
			}
			suppressInitialSearchInputFocus(searchInput);
		}
		if (typeof focusSelectedOption === "function") {
			window.setTimeout(function () {
				if (!focusSelectedOption() && panel) {
					panel.focus();
				}
			}, 50);
		} else if (panel) {
			window.setTimeout(function () {
				panel.focus();
			}, 50);
		}
	}

	function navigateToCity(city) {
		if (!city) {
			return;
		}
		setCity(city);
		closeActiveSheet();
		if (!city.value) {
			var allCitiesTarget = city.url || getAllCitiesAction();
			if (submitSearchForm(allCitiesTarget, "", categoryInput ? categoryInput.value : "")) {
				return;
			}
		}
		submitCurrentSearch();
	}

	function navigateToCategory(category) {
		if (!category) {
			return;
		}
		setCategory(category);
		closeActiveSheet();
		submitCurrentSearch();
	}

	function markSelectedCategoryItem(activeButton) {
		if (!categoryList || !activeButton) {
			return;
		}

		Array.prototype.forEach.call(categoryList.querySelectorAll(".adf-mobile-category-sheet__item"), function (button) {
			button.classList.toggle("is-selected", button === activeButton);
		});
	}

	function parseRelatedCitiesResponse(responseHtml) {
		var parser = new DOMParser();
		var doc = parser.parseFromString(responseHtml, "text/html");
		var titleNode = doc.querySelector("label");
		var anchors = doc.querySelectorAll(".city-select-city a[data-country-id], #ajax_states[data-country-id]");
		var related = [];
		anchors.forEach(function (a) {
			related.push({
				value: a.getAttribute("data-country-id") || "",
				label: (a.textContent || "").replace(/\(\d+\)\s*$/, "").trim(),
				url: ""
			});
		});
		return {
			title: titleNode ? titleNode.textContent.trim() : "",
			options: related
		};
	}

	function renderFromOptions(options, titleText) {
		cityOptions = Array.isArray(options) ? options : [];
		updateCitySheetTitle(titleText);
		renderCityList();
		filterCities(citySearchInput ? citySearchInput.value : "");
		if (citySearchInput) {
			citySearchInput.placeholder = titleText ? ("جستجو در " + titleText) : "جستجوی شهر...";
		}
	}

	function requestRelatedCities(city) {
		var nonceEl = document.querySelector("#adforest_get_countries_nonce");
		var nonce = (nonceEl && nonceEl.value) || config.countriesNonce || "";
		var ajaxUrl = config.ajaxUrl || window.ajaxurl || "";
		if (!ajaxUrl || !nonce || !city || !city.value) {
			navigateToCity(city);
			return;
		}

		var params = new URLSearchParams();
		params.set("action", "get_related_cities");
		params.set("country_id", city.value);
		params.set("security", nonce);

		fetch(ajaxUrl, {
			method: "POST",
			headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
			body: params.toString()
		})
			.then(function (res) { return res.text(); })
			.then(function (text) {
				var trimmed = (text || "").trim();
				if (trimmed === "submit") {
					navigateToCity(city);
					return;
				}
				var parsed = parseRelatedCitiesResponse(trimmed);
				if (!parsed.options.length) {
					navigateToCity(city);
					return;
				}
				renderFromOptions(parsed.options, parsed.title);
			})
			.catch(function () {
				navigateToCity(city);
			});
	}

	function getSavedCity() {
		try {
			var saved = window.localStorage.getItem("adf_mbn_city");
			if (!saved) {
				return null;
			}
			return JSON.parse(saved);
		} catch (e) {
			return null;
		}
	}

	function renderCityList() {
		if (!cityList) {
			return;
		}
		cityList.innerHTML = "";
		if (!cityOptions.length) {
			var empty = document.createElement("li");
			empty.className = "adf-mobile-city-sheet__empty";
			empty.textContent = "شهری در ad_country یافت نشد.";
			cityList.appendChild(empty);
			return;
		}
		cityOptions.forEach(function (city) {
			var item = document.createElement("li");
			var btn = document.createElement("button");
			btn.type = "button";
			btn.className = "adf-mobile-city-sheet__item";
			btn.textContent = city.label;
			btn.setAttribute("data-value", city.value);
			btn.setAttribute("data-label", city.label);
			btn.setAttribute("data-url", city.url || "");
			item.appendChild(btn);
			cityList.appendChild(item);
		});
	}

	function renderCategoryList() {
		if (!categoryList) {
			return;
		}
		categoryList.innerHTML = "";
		if (!categoryOptions.length) {
			var empty = document.createElement("li");
			empty.className = "adf-mobile-category-sheet__empty";
			empty.textContent = "دسته‌بندی‌ای در ad_cats یافت نشد.";
			categoryList.appendChild(empty);
			return;
		}
		categoryOptions.forEach(function (category) {
			var item = document.createElement("li");
			var btn = document.createElement("button");
			var isSelected =
				selectedCategory &&
				String(selectedCategory.value || "") !== "" &&
				String(selectedCategory.value || "") === String(category.value || "");
			btn.type = "button";
			btn.className = "adf-mobile-category-sheet__item bornado-mobile-choice__item" + (isSelected ? " is-selected" : "");
			btn.setAttribute("data-value", category.value);
			btn.setAttribute("data-label", category.label);
			btn.setAttribute("data-url", category.url || "");
			btn.innerHTML =
				'<span class="bornado-mobile-choice__item-copy">' +
					'<span class="bornado-mobile-choice__item-label"></span>' +
				"</span>" +
				'<span class="bornado-mobile-choice__check" aria-hidden="true"></span>';
			btn.querySelector(".bornado-mobile-choice__item-label").textContent = category.label;
			item.appendChild(btn);
			categoryList.appendChild(item);
		});
	}

	function filterCities(query) {
		if (!cityList) {
			return;
		}
		var q = (query || "").toLowerCase().trim();
		var items = cityList.querySelectorAll(".adf-mobile-city-sheet__item");
		items.forEach(function (btn) {
			var text = (btn.textContent || "").toLowerCase();
			btn.parentElement.style.display = text.indexOf(q) > -1 ? "" : "none";
		});
	}

	function filterCategories(query) {
		if (!categoryList) {
			return;
		}
		var q = (query || "").toLowerCase().trim();
		var items = categoryList.querySelectorAll(".adf-mobile-category-sheet__item");
		items.forEach(function (btn) {
			var text = (btn.textContent || "").toLowerCase();
			btn.parentElement.style.display = text.indexOf(q) > -1 ? "" : "none";
		});
	}

	function openCitySheet() {
		renderFromOptions(rootCityOptions, "");
		openSheet(citySheet, cityPanel, cityTrigger, citySearchInput, function () {
			filterCities("");
		}, function () {
			return focusCurrentOption(cityList, ".adf-mobile-city-sheet__item", cityInput ? cityInput.value : "");
		});
	}

	function openCategorySheet() {
		categoryOptions = rootCategoryOptions.slice();
		updateCategorySheetTitle("");
		renderCategoryList();
		openSheet(categorySheet, categoryPanel, categoryTrigger, categorySearchInput, function () {
			filterCategories("");
		}, function () {
			return focusCurrentOption(categoryList, ".adf-mobile-category-sheet__item", categoryInput ? categoryInput.value : "");
		});
	}

	function handleScroll() {
		if (!isMobileViewport()) {
			if (nav) {
				nav.classList.remove("is-hidden");
			}
			if (topSearch) {
				topSearch.classList.remove("city-hidden");
			}
			lastScrollY = window.scrollY;
			ticking = false;
			return;
		}

		var currentScrollY = window.scrollY;
		var isScrollingDown = currentScrollY > lastScrollY;
		var isScrollingUp = currentScrollY < lastScrollY;

		if (config.hideOnScroll && nav && currentScrollY > lastScrollY && currentScrollY > 80) {
			nav.classList.add("is-hidden");
		} else if (nav) {
			nav.classList.remove("is-hidden");
		}

		if (topSearch) {
			if (isScrollingDown && currentScrollY > 24) {
				topSearch.classList.add("city-hidden");
			} else if (isScrollingUp || currentScrollY < 10) {
				topSearch.classList.remove("city-hidden");
			}
		}

		lastScrollY = currentScrollY;
		ticking = false;
	}

	var storedCity = getSavedCity();
	if (selectedCity && selectedCity.label) {
		setCity(selectedCity);
	} else if (storedCity && storedCity.label) {
		setCity(storedCity);
	}
	if (selectedCategory && selectedCategory.label) {
		setCategory(selectedCategory);
	}

	renderCityList();
	renderCategoryList();

	if (cityTrigger) {
		cityTrigger.addEventListener("click", openCitySheet);
	}
	if (categoryTrigger) {
		categoryTrigger.addEventListener("click", openCategorySheet);
	}
	if (cityList) {
		cityList.addEventListener("click", function (event) {
			var btn = event.target.closest(".adf-mobile-city-sheet__item");
			if (!btn) {
				return;
			}
			requestRelatedCities({
				value: btn.getAttribute("data-value") || "",
				label: btn.getAttribute("data-label") || btn.textContent || "",
				url: btn.getAttribute("data-url") || ""
			});
		});
	}
	if (categoryList) {
		categoryList.addEventListener("click", function (event) {
			var btn = event.target.closest(".adf-mobile-category-sheet__item");
			if (!btn) {
				return;
			}
			markSelectedCategoryItem(btn);
			window.setTimeout(function () {
				navigateToCategory({
					value: btn.getAttribute("data-value") || "",
					label: btn.getAttribute("data-label") || btn.textContent || "",
					url: btn.getAttribute("data-url") || ""
				});
			}, 120);
		});
	}
	closeNodes.forEach(function (node) {
		node.addEventListener("click", function () {
			closeActiveSheet();
		});
	});
	document.addEventListener("keydown", function (event) {
		if (event.key === "Escape" && isAnySheetOpen()) {
			closeActiveSheet();
			return;
		}
		if (event.key === "Escape" && isFiltersDrawerOpen()) {
			setFiltersDrawerState(false);
			return;
		}
		if (event.key === "Tab" && isAnySheetOpen()) {
			var focusable = getFocusableInSheet();
			if (!focusable.length) {
				event.preventDefault();
				return;
			}
			var first = focusable[0];
			var last = focusable[focusable.length - 1];
			if (event.shiftKey && document.activeElement === first) {
				event.preventDefault();
				last.focus();
			} else if (!event.shiftKey && document.activeElement === last) {
				event.preventDefault();
				first.focus();
			}
		}
	});
	if (citySearchInput) {
		citySearchInput.addEventListener("input", function (event) {
			filterCities(event.target.value);
		});
	}
	if (categorySearchInput) {
		categorySearchInput.addEventListener("input", function (event) {
			filterCategories(event.target.value);
		});
	}
	if (searchForm) {
		searchForm.addEventListener("submit", function (event) {
			event.preventDefault();
			submitCurrentSearch();
		});
	}

	if (topSearch && searchForm && titleInput) {
		var collapseTimer = null;

		var expandSearch = function () {
			if (collapseTimer) {
				window.clearTimeout(collapseTimer);
				collapseTimer = null;
			}
			topSearch.classList.add("is-focused");
		};

		var collapseSearch = function () {
			topSearch.classList.remove("is-focused");
		};

		searchForm.addEventListener("focusin", expandSearch);

		searchForm.addEventListener("focusout", function (event) {
			var nextTarget = event.relatedTarget;
			if (nextTarget && searchForm.contains(nextTarget)) {
				return;
			}
			if (collapseTimer) {
				window.clearTimeout(collapseTimer);
			}
			collapseTimer = window.setTimeout(function () {
				if (document.activeElement && searchForm.contains(document.activeElement)) {
					return;
				}
				collapseSearch();
			}, 60);
		});

		titleInput.addEventListener("touchstart", expandSearch, { passive: true });
	}

	window.addEventListener(
		"scroll",
		function () {
			if (!ticking) {
				window.requestAnimationFrame(handleScroll);
				ticking = true;
			}
		},
		{ passive: true }
	);
	window.addEventListener("resize", function () {
		if (!isMobileViewport() && isAnySheetOpen()) {
			closeActiveSheet();
		}
		if (!isMobileViewport()) {
			setFiltersDrawerState(false);
			teardownFiltersDrawerEnhancements();
		}
		handleScroll();
	});

	var favoritesToastTimer = null;
	var lastFocusedFilterElement = null;
	var mobileFiltersCountTimer = null;
	var mobileFiltersCountRequest = null;
	var mobileFiltersFooter = null;
	var mobileFiltersApplyButton = null;
	var mobileFiltersHint = null;
	var mobileFiltersCount = null;
	var mobileFiltersLastQuery = "";

	function getFilterNavLink() {
		return nav ? nav.querySelector("[data-adf-mbn-filters]") : null;
	}

	function getJQuery() {
		return window.jQuery || null;
	}

	function getSearchFilterSidebar() {
		return document.getElementById("adforest-ajax-sidebar");
	}

	function isFiltersDrawerOpen() {
		var sidebar = getSearchFilterSidebar();
		return document.body.classList.contains("adf-mobile-filters-open") || !!(sidebar && sidebar.classList.contains("active"));
	}

	function isMobileFiltersDraftMode() {
		var sidebar = getSearchFilterSidebar();
		return isMobileViewport() && !!(sidebar && sidebar.classList.contains("mobile-filters")) && isFiltersDrawerOpen();
	}

	function isIgnoredMobileFilterForm(form) {
		if (!form) {
			return false;
		}
		return (
			form.id === "search_cats_w" ||
			!!form.querySelector("#adforest-category-select, #adforest-category-selected")
		);
	}

	function isIgnoredMobileFilterField(field) {
		return !!(field && field.form && isIgnoredMobileFilterForm(field.form));
	}

	function splitQueryPairs(queryString) {
		var source = String(queryString || "").replace(/^\?/, "");
		if (!source) {
			return [];
		}
		return source
			.split("&")
			.map(function (piece) {
				var safePiece = String(piece || "");
				if (!safePiece) {
					return null;
				}
				var separatorIndex = safePiece.indexOf("=");
				var rawKey = separatorIndex > -1 ? safePiece.slice(0, separatorIndex) : safePiece;
				var rawValue = separatorIndex > -1 ? safePiece.slice(separatorIndex + 1) : "";
				var key = rawKey;
				var value = rawValue;
				try {
					key = decodeURIComponent(rawKey.replace(/\+/g, " "));
				} catch (e) {}
				try {
					value = decodeURIComponent(rawValue.replace(/\+/g, " "));
				} catch (e) {}
				return { key: key, value: value };
			})
			.filter(Boolean);
	}

	function appendQueryPair(bag, seenScalars, key, value) {
		var safeKey = String(key || "").trim();
		var safeValue = value == null ? "" : String(value).trim();
		if (!safeKey || safeValue === "") {
			return;
		}
		if (
			safeKey === "security" ||
			safeKey === "action" ||
			safeKey === "_wpnonce" ||
			safeKey === "_wp_http_referer" ||
			safeKey === "paged" ||
			safeKey === "page-number"
		) {
			return;
		}
		if (safeKey.indexOf("[]") !== -1) {
			bag.push({ key: safeKey, value: safeValue });
			return;
		}
		if (seenScalars[safeKey]) {
			return;
		}
		seenScalars[safeKey] = true;
		bag.push({ key: safeKey, value: safeValue });
	}

	function shouldSkipFieldValue(field, value) {
		if (!field) {
			return true;
		}
		var name = field.getAttribute("name") || "";
		var safeValue = value == null ? "" : String(value).trim();
		if (!name || safeValue === "") {
			return true;
		}
		if (safeValue === "0") {
			if (
				name === "cat_id" ||
				name === "country_id" ||
				name === "ad_currency" ||
				name === "condition" ||
				name === "warranty" ||
				name === "ad_type" ||
				name === "adtype" ||
				name === "sort" ||
				name === "c" ||
				name.indexOf("custom[") === 0
			) {
				return true;
			}
		}
		if (field.hasAttribute("data-adforest-default") && safeValue === String(field.getAttribute("data-adforest-default"))) {
			return true;
		}
		if ((name === "min_price" || name === "max_price") && field.form) {
			var defaultAttr = name === "min_price" ? "data-adforest-default-min" : "data-adforest-default-max";
			var defaultValue = field.form.getAttribute(defaultAttr);
			if (defaultValue != null && safeValue === String(defaultValue)) {
				return true;
			}
		}
		if (name === "sort" && field.tagName === "SELECT") {
			var firstOption = field.options && field.options.length ? field.options[0].value : "";
			if (safeValue === String(firstOption || "")) {
				return true;
			}
		}
		return false;
	}

	function collectFormFieldValues(field) {
		if (!field || field.disabled || !field.name) {
			return [];
		}
		var type = (field.type || "").toLowerCase();
		if (type === "submit" || type === "button" || type === "reset" || type === "file") {
			return [];
		}
		if ((type === "checkbox" || type === "radio") && !field.checked) {
			return [];
		}
		if (field.tagName === "SELECT" && field.multiple) {
			return Array.prototype.slice
				.call(field.options || [])
				.filter(function (option) {
					return option.selected;
				})
				.map(function (option) {
					return option.value;
				})
				.filter(function (value) {
					return !shouldSkipFieldValue(field, value);
				});
		}
		var fieldValue = field.value == null ? "" : field.value;
		return shouldSkipFieldValue(field, fieldValue) ? [] : [fieldValue];
	}

	function getManagedFilterForms() {
		var sidebar = getSearchFilterSidebar();
		var forms = [];
		if (sidebar) {
			Array.prototype.forEach.call(sidebar.querySelectorAll("form"), function (form) {
				if (!isIgnoredMobileFilterForm(form)) {
					forms.push(form);
				}
			});
		}
		var sortForm = document.getElementById("sort-form");
		if (sortForm) {
			forms.push(sortForm);
		}
		return forms;
	}

	function buildMobileFiltersQuery() {
		var managedForms = getManagedFilterForms();
		var queryPairs = [];
		var seenScalars = {};
		var controlledRoots = {};

		managedForms.forEach(function (form) {
			Array.prototype.forEach.call(form.querySelectorAll("input[name], select[name], textarea[name]"), function (field) {
				if (field.disabled || isIgnoredMobileFilterField(field)) {
					return;
				}
				var name = field.getAttribute("name") || "";
				if (!name) {
					return;
				}
				controlledRoots[name] = true;
				controlledRoots[name.split("[")[0]] = true;
			});
		});

		splitQueryPairs(window.location.search).forEach(function (pair) {
			var root = String(pair.key || "").split("[")[0];
			if (controlledRoots[pair.key] || controlledRoots[root]) {
				return;
			}
			appendQueryPair(queryPairs, seenScalars, pair.key, pair.value);
		});

		managedForms.forEach(function (form) {
			Array.prototype.forEach.call(form.querySelectorAll("input[name], select[name], textarea[name]"), function (field) {
				var values = collectFormFieldValues(field);
				if (!values.length) {
					return;
				}
				values.forEach(function (value) {
					appendQueryPair(queryPairs, seenScalars, field.name, value);
				});
			});
		});

		return queryPairs
			.map(function (pair) {
				return encodeURIComponent(pair.key) + "=" + encodeURIComponent(pair.value);
			})
			.join("&");
	}

	function formatMobileFiltersApplyLabel(count) {
		var template = config.mobileFiltersApplyTemplate || "نمایش {{count}} آگهی";
		return template.replace("{{count}}", String(Math.max(0, parseInt(count, 10) || 0)));
	}

	function updateMobileFiltersApplyState(state, count) {
		if (!mobileFiltersApplyButton) {
			return;
		}
		mobileFiltersApplyButton.classList.remove("is-loading", "is-disabled", "is-error");
		mobileFiltersApplyButton.disabled = false;

		if (state === "loading") {
			mobileFiltersApplyButton.classList.add("is-loading");
			mobileFiltersApplyButton.disabled = true;
			mobileFiltersApplyButton.textContent = config.mobileFiltersApplyLoading || "در حال محاسبه...";
			return;
		}

		if (state === "error") {
			mobileFiltersApplyButton.classList.add("is-error");
			mobileFiltersApplyButton.textContent = config.mobileFiltersApplyError || "خطا در محاسبه آگهی‌ها";
			return;
		}

		if (state === "zero") {
			mobileFiltersApplyButton.classList.add("is-disabled");
			mobileFiltersApplyButton.disabled = true;
			mobileFiltersApplyButton.textContent = config.mobileFiltersApplyZero || "آگهی‌ای یافت نشد";
			return;
		}

		mobileFiltersApplyButton.textContent = formatMobileFiltersApplyLabel(count);
	}

	function getCountFromDom() {
		var countNode = document.getElementById("adforest-ajax-count");
		if (!countNode) {
			return null;
		}
		var match = String(countNode.textContent || "").match(/\d+/);
		return match ? parseInt(match[0], 10) : null;
	}

	function syncMobileFiltersCountFromDom() {
		var countValue = getCountFromDom();
		if (countValue == null || isNaN(countValue)) {
			return;
		}
		mobileFiltersCount = countValue;
		updateMobileFiltersApplyState(countValue > 0 ? "ready" : "zero", countValue);
	}

	function teardownFiltersDrawerEnhancements() {
		var sidebar = getSearchFilterSidebar();
		var $ = getJQuery();

		if (mobileFiltersCountTimer) {
			window.clearTimeout(mobileFiltersCountTimer);
			mobileFiltersCountTimer = null;
		}
		abortMobileFiltersCountRequest();

		if (mobileFiltersFooter && mobileFiltersFooter.parentNode) {
			mobileFiltersFooter.parentNode.removeChild(mobileFiltersFooter);
		}
		mobileFiltersFooter = null;
		mobileFiltersApplyButton = null;
		mobileFiltersHint = null;
		mobileFiltersLastQuery = "";

		if (!sidebar) {
			return;
		}

		sidebar.classList.remove("adf-mbn-filters-sheet");
		sidebar.removeAttribute("dir");

		Array.prototype.forEach.call(sidebar.querySelectorAll(".adf-mbn-hidden-mobile-filter"), function (node) {
			node.classList.remove("adf-mbn-hidden-mobile-filter");
		});

		var handle = sidebar.querySelector(".adf-mbn-filters-sheet__handle");
		if (handle && handle.parentNode) {
			handle.parentNode.removeChild(handle);
		}

		var closeButton = sidebar.querySelector(".adf-mobile-filters-close");
		if (closeButton && closeButton.parentNode) {
			closeButton.parentNode.removeChild(closeButton);
		}

		if ($) {
			getManagedFilterForms().forEach(function (form) {
				$(form).off(".adfMbnDraft").removeData("adfMbnDraftSubmitBound");
			});
			Array.prototype.forEach.call(sidebar.querySelectorAll("input[name], select[name], textarea[name]"), function (field) {
				$(field)
					.off(".adfMbnDraft")
					.removeData("adfMbnDraftChangeBound")
					.removeData("adfMbnDraftInputBound");
			});
		}
	}

	function ensureFiltersFooter(sidebar) {
		if (!sidebar) {
			return null;
		}
		if (!isMobileViewport() || !sidebar.classList.contains("mobile-filters")) {
			return null;
		}
		if (!mobileFiltersFooter || !sidebar.contains(mobileFiltersFooter)) {
			mobileFiltersFooter = sidebar.querySelector(".adf-mbn-filters-sheet__footer");
		}
		if (!mobileFiltersFooter) {
			mobileFiltersFooter = document.createElement("div");
			mobileFiltersFooter.className = "adf-mbn-filters-sheet__footer";
			mobileFiltersFooter.innerHTML =
				'<p class="adf-mbn-filters-sheet__hint"></p><button type="button" class="adf-mbn-filters-sheet__apply"></button>';
			sidebar.appendChild(mobileFiltersFooter);
		}
		mobileFiltersHint = mobileFiltersFooter.querySelector(".adf-mbn-filters-sheet__hint");
		mobileFiltersApplyButton = mobileFiltersFooter.querySelector(".adf-mbn-filters-sheet__apply");
		if (mobileFiltersHint) {
			mobileFiltersHint.textContent =
				config.mobileFiltersApplyHint || "فیلترهای دلخواه را انتخاب کنید و در پایان نتایج را نمایش دهید.";
		}
		if (mobileFiltersApplyButton && !mobileFiltersApplyButton.hasAttribute("data-adf-mbn-bound")) {
			mobileFiltersApplyButton.setAttribute("data-adf-mbn-bound", "1");
			mobileFiltersApplyButton.addEventListener("click", function (event) {
				event.preventDefault();
				handleMobileFiltersApply();
			});
		}
		syncMobileFiltersCountFromDom();
		if (mobileFiltersCount == null) {
			updateMobileFiltersApplyState("loading");
		}
		return mobileFiltersFooter;
	}

	function bindMobileDraftHandlers(sidebar) {
		var $ = getJQuery();
		if (!$ || !sidebar || !isMobileViewport() || !sidebar.classList.contains("mobile-filters")) {
			return;
		}

		getManagedFilterForms().forEach(function (form) {
			var $form = $(form);
			if ($form.data("adfMbnDraftSubmitBound")) {
				return;
			}
			$form.data("adfMbnDraftSubmitBound", true);
			$form.on("submit.adfMbnDraft", function (event) {
				if (!isMobileFiltersDraftMode()) {
					return;
				}
				event.preventDefault();
				event.stopImmediatePropagation();
				scheduleMobileFiltersCountRefresh(true);
				return false;
			});
		});

		Array.prototype.forEach.call(sidebar.querySelectorAll("input[name], select[name], textarea[name]"), function (field) {
			if (field.disabled || isIgnoredMobileFilterField(field)) {
				return;
			}
			var $field = $(field);
			if (!$field.data("adfMbnDraftChangeBound")) {
				$field.data("adfMbnDraftChangeBound", true);
				$field.on("change.adfMbnDraft", function (event) {
					if (!isMobileFiltersDraftMode()) {
						return;
					}
					event.stopImmediatePropagation();
					scheduleMobileFiltersCountRefresh(true);
				});
			}
			if (
				!$field.data("adfMbnDraftInputBound") &&
				(field.matches('input[type="text"]') ||
					field.matches('input[type="search"]') ||
					field.matches('input[type="number"]') ||
					field.matches("textarea"))
			) {
				$field.data("adfMbnDraftInputBound", true);
				$field.on("input.adfMbnDraft", function (event) {
					if (!isMobileFiltersDraftMode()) {
						return;
					}
					event.stopImmediatePropagation();
					scheduleMobileFiltersCountRefresh(false);
				});
			}
		});

		if (!sidebar._adfMbnDraftObserver && typeof MutationObserver !== "undefined") {
			sidebar._adfMbnDraftObserver = new MutationObserver(function () {
				if (!sidebar.classList.contains("adf-mbn-filters-sheet")) {
					return;
				}
				bindMobileDraftHandlers(sidebar);
			});
			sidebar._adfMbnDraftObserver.observe(sidebar, { childList: true, subtree: true });
		}
	}

	function abortMobileFiltersCountRequest() {
		if (mobileFiltersCountRequest && typeof mobileFiltersCountRequest.abort === "function") {
			try {
				mobileFiltersCountRequest.abort();
			} catch (e) {}
		}
		mobileFiltersCountRequest = null;
	}

	function requestMobileFiltersCount() {
		if (!isMobileFiltersDraftMode()) {
			return;
		}
		var $ = getJQuery();
		if (!$ || !config.ajaxUrl || !config.mobileCountNonce) {
			return;
		}
		var queryString = buildMobileFiltersQuery();
		if (queryString === mobileFiltersLastQuery && mobileFiltersCount != null) {
			updateMobileFiltersApplyState(mobileFiltersCount > 0 ? "ready" : "zero", mobileFiltersCount);
			return;
		}

		mobileFiltersLastQuery = queryString;
		updateMobileFiltersApplyState("loading");
		abortMobileFiltersCountRequest();

		mobileFiltersCountRequest = $.ajax({
			url: config.ajaxUrl,
			method: "POST",
			dataType: "json",
			data: {
				action: "adf_mobile_filter_count",
				security: config.mobileCountNonce,
				filters_raw: queryString
			}
		})
			.done(function (response) {
				if (!response || !response.success || !response.data) {
					updateMobileFiltersApplyState("error");
					return;
				}
				mobileFiltersCount = parseInt(response.data.total, 10) || 0;
				updateMobileFiltersApplyState(mobileFiltersCount > 0 ? "ready" : "zero", mobileFiltersCount);
			})
			.fail(function (_xhr, status) {
				if (status === "abort") {
					return;
				}
				updateMobileFiltersApplyState("error");
			})
			.always(function () {
				mobileFiltersCountRequest = null;
			});
	}

	function scheduleMobileFiltersCountRefresh(immediate) {
		if (mobileFiltersCountTimer) {
			window.clearTimeout(mobileFiltersCountTimer);
		}
		mobileFiltersCountTimer = window.setTimeout(
			requestMobileFiltersCount,
			immediate ? 30 : parseInt(config.mobileFiltersCountDebounce, 10) || 260
		);
	}

	function resolveMobileFiltersActionUrl() {
		var forms = getManagedFilterForms();
		for (var i = 0; i < forms.length; i++) {
			var action = forms[i].getAttribute("action");
			if (action) {
				return action;
			}
		}
		return window.location.pathname || window.location.href;
	}

	function handleMobileFiltersApply() {
		var queryString = buildMobileFiltersQuery();
		if (mobileFiltersCount === 0) {
			return;
		}
		if (
			window.adforestAjaxSearchApi &&
			typeof window.adforestAjaxSearchApi.run === "function" &&
			document.getElementById("adforest-ajax-results")
		) {
			window.adforestAjaxSearchApi.run(queryString);
			setFiltersDrawerState(false);
			return;
		}

		var targetUrl = resolveMobileFiltersActionUrl();
		window.location.href = queryString ? targetUrl.replace(/\?.*$/, "") + "?" + queryString : targetUrl.replace(/\?.*$/, "");
	}

	function ensureFiltersDrawerEnhancements() {
		var sidebar = getSearchFilterSidebar();
		if (!sidebar) {
			return null;
		}
		if (!isMobileViewport() || !sidebar.classList.contains("mobile-filters")) {
			teardownFiltersDrawerEnhancements();
			return sidebar;
		}

		sidebar.classList.add("adf-mbn-filters-sheet");
		sidebar.setAttribute("dir", "rtl");

		var heading = sidebar.querySelector(".mobile-filter-heading");
		if (heading) {
			heading.classList.add("adf-mbn-filters-sheet__header");

			if (!heading.querySelector(".adf-mbn-filters-sheet__handle")) {
				var handle = document.createElement("span");
				handle.className = "adf-mbn-filters-sheet__handle";
				handle.setAttribute("aria-hidden", "true");
				heading.insertBefore(handle, heading.firstChild);
			}

			if (!heading.querySelector(".adf-mobile-filters-close")) {
				var closeButton = document.createElement("button");
				closeButton.type = "button";
				closeButton.className = "adf-mobile-filters-close";
				closeButton.setAttribute("aria-label", "بستن فیلترها");
				closeButton.innerHTML =
					'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.4 5l.7.7L12 10.59l4.9-4.89.7-.7 1.4 1.41-.7.7L13.41 12l4.89 4.9.7.7-1.41 1.4-.7-.7L12 13.41l-4.9 4.89-.7.7-1.4-1.41.7-.7L10.59 12 5.7 7.1l-.7-.7L6.4 5z"/></svg>';
				heading.appendChild(closeButton);
			}
		}

		var backdrop = document.querySelector(".adf-mbn-filters-backdrop");
		if (!backdrop) {
			backdrop = document.createElement("button");
			backdrop.type = "button";
			backdrop.className = "adf-mbn-filters-backdrop";
			backdrop.setAttribute("aria-label", "بستن پنل فیلترها");
			document.body.appendChild(backdrop);
		}

		Array.prototype.forEach.call(sidebar.querySelectorAll("form"), function (form) {
			if (!isIgnoredMobileFilterForm(form)) {
				return;
			}
			form.classList.add("adf-mbn-hidden-mobile-filter");
			var filterCard = form.closest(".accordion-item");
			if (filterCard) {
				filterCard.classList.add("adf-mbn-hidden-mobile-filter");
			}
		});

		ensureFiltersFooter(sidebar);
		bindMobileDraftHandlers(sidebar);
		return sidebar;
	}

	function handleFilterNavClick(event) {
		if (event) {
			event.preventDefault();
			event.stopPropagation();
			lastFocusedFilterElement = document.activeElement instanceof HTMLElement ? document.activeElement : getFilterNavLink();
		}
		ensureFiltersDrawerEnhancements();
		setFiltersDrawerState(!isFiltersDrawerOpen());
	}

	function setFiltersDrawerState(isOpen) {
		var sidebar = ensureFiltersDrawerEnhancements();
		document.body.classList.toggle("adf-mobile-filters-open", !!isOpen);
		if (sidebar && sidebar.classList.contains("mobile-filters")) {
			sidebar.classList.toggle("active", !!isOpen);
			sidebar.setAttribute("aria-hidden", isOpen ? "false" : "true");
		}
		if (isOpen) {
			mobileFiltersLastQuery = "";
			syncMobileFiltersCountFromDom();
			scheduleMobileFiltersCountRefresh(false);
			window.setTimeout(function () {
				var focusTarget =
					(sidebar &&
						sidebar.querySelector(
							".adf-mobile-filters-close, .adf-mbn-filters-sheet__apply, input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled])"
						)) ||
					sidebar;
				if (focusTarget && typeof focusTarget.focus === "function") {
					focusTarget.focus();
				}
			}, 70);
		} else {
			if (mobileFiltersCountTimer) {
				window.clearTimeout(mobileFiltersCountTimer);
			}
			abortMobileFiltersCountRequest();
			if (lastFocusedFilterElement && typeof lastFocusedFilterElement.focus === "function") {
				window.setTimeout(function () {
					lastFocusedFilterElement.focus();
				}, 20);
			}
		}
		syncFilterNavState();
	}

	function syncFilterNavState() {
		var filterLink = getFilterNavLink();
		if (!filterLink) {
			return;
		}
		var isOpen = isFiltersDrawerOpen();
		var item = filterLink.closest(".adf-mbn__item");
		filterLink.setAttribute("aria-expanded", isOpen ? "true" : "false");
		if (item) {
			item.classList.toggle("is-active", isOpen);
		}
	}

	function showFavoritesLoginMessage() {
		var message = config.favoritesLoginMessage || "برای مشاهده علاقه‌مندی‌ها ابتدا وارد حساب کاربری شوید.";
		var toast = document.querySelector(".adf-mbn-toast");

		if (!toast) {
			toast = document.createElement("div");
			toast.className = "adf-mbn-toast";
			toast.setAttribute("role", "status");
			toast.setAttribute("aria-live", "polite");
			document.body.appendChild(toast);
		}

		toast.textContent = message;
		toast.classList.add("is-visible");

		if (favoritesToastTimer) {
			window.clearTimeout(favoritesToastTimer);
		}

		favoritesToastTimer = window.setTimeout(function () {
			toast.classList.remove("is-visible");
		}, 3200);
	}

	if (nav) {
		if (getFilterNavLink()) {
			var directFilterLink = getFilterNavLink();
			document.body.classList.add("adf-mbn-has-filters-nav");
			syncFilterNavState();

			if (directFilterLink) {
				directFilterLink.addEventListener("click", handleFilterNavClick);
			}

			if (typeof MutationObserver !== "undefined") {
				var sidebar = getSearchFilterSidebar();
				new MutationObserver(syncFilterNavState).observe(document.body, {
					attributes: true,
					attributeFilter: ["class"]
				});
				if (sidebar) {
					new MutationObserver(syncFilterNavState).observe(sidebar, {
						attributes: true,
						attributeFilter: ["class"]
					});
				}
			}

			var $ = getJQuery();
			if ($) {
				$(document).on("adforest:search:rendered", function (_event, data) {
					if (data && typeof data.total !== "undefined") {
						mobileFiltersCount = parseInt(data.total, 10) || 0;
						if (isFiltersDrawerOpen()) {
							updateMobileFiltersApplyState(mobileFiltersCount > 0 ? "ready" : "zero", mobileFiltersCount);
						}
					}
				});
			}
		}

		nav.addEventListener("click", function (event) {
			var filterLink = event.target.closest("[data-adf-mbn-filters]");
			if (filterLink) {
				handleFilterNavClick(event);
				return;
			}

			var guestLink = event.target.closest("[data-adf-mbn-favorites-guest]");
			if (!guestLink) {
				return;
			}
			event.preventDefault();
			showFavoritesLoginMessage();
		});
	}

	document.addEventListener("click", function (event) {
		var closeTrigger = event.target.closest(
			".adf-mbn-filters-backdrop, .adf-mobile-filters-close, .adf-filters-backdrop, .filter-close-btn, .close-sidebar"
		);
		if (!closeTrigger || !isFiltersDrawerOpen()) {
			return;
		}
		event.preventDefault();
		setFiltersDrawerState(false);
	});
})();
