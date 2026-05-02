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
			btn.type = "button";
			btn.className = "adf-mobile-category-sheet__item";
			btn.textContent = category.label;
			btn.setAttribute("data-value", category.value);
			btn.setAttribute("data-label", category.label);
			btn.setAttribute("data-url", category.url || "");
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
			navigateToCategory({
				value: btn.getAttribute("data-value") || "",
				label: btn.getAttribute("data-label") || btn.textContent || "",
				url: btn.getAttribute("data-url") || ""
			});
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
		handleScroll();
	});
})();
