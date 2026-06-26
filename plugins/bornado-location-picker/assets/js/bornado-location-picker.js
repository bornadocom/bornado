(function () {
	"use strict";

	var globalConfig = window.BornadoLocationPicker || {};

	function parseConfig(root) {
		var node = root.querySelector(".blp__config");
		if (!node) {
			return null;
		}

		try {
			return JSON.parse(node.textContent || "{}");
		} catch (error) {
			return null;
		}
	}

	function buildItemButton(type, item, selectedId) {
		var button = document.createElement("button");
		var copy = document.createElement("span");
		var label = document.createElement("span");
		var check = document.createElement("span");
		var isSelected = Number(selectedId || 0) === Number(item.id || 0);

		button.type = "button";
		button.className = "blp__item blp__item--" + type + (isSelected ? " is-selected" : "");
		if (type === "country") {
			button.dataset.blpCountry = "1";
		} else if (type === "city") {
			button.dataset.blpCity = "1";
		}
		button.dataset.id = String(item.id || 0);
		button.dataset.url = String(item.url || "");
		button.dataset.label = String(item.label || "");
		button.dataset.kind = String(item.kind || type);
		if (typeof item.parentId !== "undefined") {
			button.dataset.parentId = String(item.parentId || 0);
		}

		copy.className = "blp__item-copy";
		label.className = "blp__item-label";
		label.textContent = String(item.label || "");
		copy.appendChild(label);

		check.className = "blp__check";

		button.appendChild(copy);
		button.appendChild(check);

		return button;
	}

	function filterButtons(container, query) {
		var normalized = String(query || "").trim().toLowerCase();
		var buttons = container.querySelectorAll(".blp__item");
		var visibleCount = 0;

		buttons.forEach(function (button) {
			var label = String(button.dataset.label || "").toLowerCase();
			var show = normalized === "" || label.indexOf(normalized) !== -1;
			button.hidden = !show;
			if (show) {
				visibleCount += 1;
			}
		});

		return visibleCount;
	}

	function requestCities(countryId, includeUrls) {
		if (!globalConfig.ajaxUrl || !globalConfig.nonce || !globalConfig.action) {
			return Promise.resolve([]);
		}

		var body = new URLSearchParams();
		body.set("action", globalConfig.action);
		body.set("nonce", globalConfig.nonce);
		body.set("country_id", String(countryId || 0));
		body.set("include_urls", includeUrls ? "1" : "0");

		return fetch(globalConfig.ajaxUrl, {
			method: "POST",
			headers: {
				"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
			},
			body: body.toString()
		}).then(function (response) {
			return response.json();
		}).then(function (payload) {
			if (!payload || !payload.success || !payload.data || !Array.isArray(payload.data.items)) {
				return [];
			}
			return payload.data.items;
		}).catch(function () {
			return [];
		});
	}

	function syncFormAction(form, city, country, fallbackUrl) {
		if (!form) {
			return;
		}

		if (city && city.url) {
			form.action = city.url;
			return;
		}

		if (country && country.url) {
			form.action = country.url;
			return;
		}

		if (fallbackUrl) {
			form.action = fallbackUrl;
		}
	}

	function resolveExternalNode(root, selector) {
		if (!selector) {
			return null;
		}

		if (root.closest) {
			var closestMatch = root.closest(selector);
			if (closestMatch) {
				return closestMatch;
			}
		}

		try {
			return document.querySelector(selector);
		} catch (error) {
			return null;
		}
	}

	function syncPersistedLocationSelection(selected) {
		var searchCore = window.BornadoSearchCore || null;
		var locationKeys = ["country_id", "ad_country", "location", "city_id", "bornado_country", "bornado_city"];
		var deepestTermId = Number(selected && selected.deepestTermId ? selected.deepestTermId : 0);

		if (!searchCore) {
			return;
		}

		if (deepestTermId > 0) {
			var params = new URLSearchParams();
			params.set("country_id", String(deepestTermId));
			if (typeof searchCore.mergePersistedContext === "function") {
				searchCore.mergePersistedContext(params);
			}
			return;
		}

		if (typeof searchCore.clearPersistedContext === "function") {
			searchCore.clearPersistedContext(locationKeys);
		}
	}

	function hasCurrentLocationContext() {
		var searchCore = window.BornadoSearchCore || null;
		var params = searchCore && typeof searchCore.getEffectiveCurrentSearchParams === "function"
			? searchCore.getEffectiveCurrentSearchParams()
			: new URLSearchParams(window.location.search);
		var routeContext = window.BornadoSearchCoreConfig && window.BornadoSearchCoreConfig.routeContext
			? window.BornadoSearchCoreConfig.routeContext
			: {};
		var locationKeys = ["country_id", "ad_country", "location", "city_id", "bornado_country", "bornado_city"];

		for (var index = 0; index < locationKeys.length; index += 1) {
			var key = locationKeys[index];
			if (!params.has(key)) {
				continue;
			}
			if (String(params.get(key) || "").trim() !== "") {
				return true;
			}
		}

		return Number(routeContext.cityId || 0) > 0 || Number(routeContext.countryId || 0) > 0;
	}

	function cloneSelection(selection) {
		return {
			countryId: Number(selection && selection.countryId ? selection.countryId : 0),
			cityId: Number(selection && selection.cityId ? selection.cityId : 0),
			deepestTermId: Number(selection && selection.deepestTermId ? selection.deepestTermId : 0)
		};
	}

	function Picker(root) {
		this.root = root;
		this.config = parseConfig(root);
		if (!this.config) {
			return;
		}

		this.form = root.querySelector("[data-blp-form]");
		this.panel = root.querySelector("[data-blp-panel]");
		this.backdrop = root.querySelector("[data-blp-backdrop]");
		this.trigger = root.querySelector("[data-blp-trigger]");
		this.summary = root.querySelector("[data-blp-summary]");
		this.current = root.querySelector("[data-blp-current]");
		this.countryList = root.querySelector("[data-blp-country-list]");
		this.cityList = root.querySelector("[data-blp-city-list]");
		this.countryInput = root.querySelector("[data-blp-country-input]");
		this.countrySearch = root.querySelector("[data-blp-country-search]");
		this.citySearch = root.querySelector("[data-blp-city-search]");
		this.applyButton = root.querySelector("[data-blp-apply]");
		this.resetButton = root.querySelector("[data-blp-reset]");
		this.closeButton = root.querySelector("[data-blp-close]");

		this.state = {
			mode: this.config.mode || "compact",
			selected: cloneSelection(this.config.selected),
			committed: cloneSelection(this.config.selected),
			countries: Array.isArray(this.config.countries) ? this.config.countries.slice() : [],
			citiesCache: {}
		};

		if (Array.isArray(this.config.initialCities) && this.state.selected.countryId) {
			this.state.citiesCache[this.state.selected.countryId] = this.config.initialCities.slice();
		}

		this.externalForm = resolveExternalNode(root, this.config.externalFormSelector || "");
		this.externalInput = resolveExternalNode(root, this.config.externalInputSelector || "");
		this.activeForm = this.externalForm || this.form;
		this.activeInput = this.externalInput || this.countryInput;
		this.isHeaderPicker = root.classList.contains("adt-header-location-picker");
		this.boundPositionPanel = this.positionPanel.bind(this);

		this.bindEvents();
		this.refreshSummary();
		this.syncSelectionFromCurrentContext();
	}

	Picker.prototype.bindEvents = function () {
		var self = this;

		if (this.trigger) {
			this.trigger.addEventListener("click", function () {
				self.openPanel();
			});
		}

		if (this.backdrop) {
			this.backdrop.addEventListener("click", function () {
				self.closePanel();
			});
		}

		if (this.closeButton) {
			this.closeButton.addEventListener("click", function () {
				self.closePanel();
			});
		}

		if (this.applyButton) {
			this.applyButton.addEventListener("click", function () {
				self.applySelection();
			});
		}

		if (this.resetButton) {
			this.resetButton.addEventListener("click", function () {
				self.resetSelection();
			});
		}

		if (this.countryList) {
			this.countryList.addEventListener("click", function (event) {
				var button = event.target.closest("[data-blp-country]");
				if (!button) {
					return;
				}
				self.handleCountryClick(button);
			});
		}

		if (this.cityList) {
			this.cityList.addEventListener("click", function (event) {
				var button = event.target.closest("[data-blp-city]");
				if (!button) {
					return;
				}
				self.handleCityClick(button);
			});
		}

		if (this.countrySearch) {
			this.countrySearch.addEventListener("input", function () {
				filterButtons(self.countryList, self.countrySearch.value);
			});
		}

		if (this.citySearch) {
			this.citySearch.addEventListener("input", function () {
				filterButtons(self.cityList, self.citySearch.value);
			});
		}

		document.addEventListener("keydown", function (event) {
			if (event.key === "Escape") {
				self.closePanel();
			}
		});

		if (this.isHeaderPicker) {
			window.addEventListener("resize", this.boundPositionPanel, { passive: true });
			window.addEventListener("scroll", this.boundPositionPanel, { passive: true });
		}

		window.addEventListener("pageshow", function () {
			self.syncSelectionFromCurrentContext();
		});

		window.addEventListener("popstate", function () {
			window.setTimeout(function () {
				self.syncSelectionFromCurrentContext();
			}, 0);
		});
	};

	Picker.prototype.positionPanel = function () {
		if (!this.isHeaderPicker || !this.panel || !this.trigger || this.panel.hidden) {
			return;
		}

		var triggerRect = this.trigger.getBoundingClientRect();
		var viewportWidth = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
		var gutter = 16;
		var panelWidth = Math.min(840, Math.max(280, viewportWidth - (gutter * 2)));

		this.panel.style.position = "fixed";
		this.panel.style.top = Math.round(triggerRect.bottom + 12) + "px";
		this.panel.style.left = "50%";
		this.panel.style.right = "auto";
		this.panel.style.width = Math.round(panelWidth) + "px";
		this.panel.style.maxWidth = "none";
		this.panel.style.transform = "translateX(-50%)";
	};

	Picker.prototype.openPanel = function () {
		if (!this.panel || this.state.mode === "inline") {
			return;
		}

		this.syncDraftFromCommitted();
		this.panel.hidden = false;
		if (this.backdrop) {
			this.backdrop.hidden = false;
		}
		if (this.trigger) {
			this.trigger.setAttribute("aria-expanded", "true");
		}
		document.body.classList.add("blp-panel-open");
		this.positionPanel();
	};

	Picker.prototype.closePanel = function () {
		if (!this.panel || this.state.mode === "inline") {
			return;
		}

		this.syncDraftFromCommitted();
		this.panel.hidden = true;
		if (this.backdrop) {
			this.backdrop.hidden = true;
		}
		if (this.trigger) {
			this.trigger.setAttribute("aria-expanded", "false");
		}
		document.body.classList.remove("blp-panel-open");

		if (this.isHeaderPicker) {
			this.panel.style.position = "";
			this.panel.style.top = "";
			this.panel.style.left = "";
			this.panel.style.right = "";
			this.panel.style.width = "";
			this.panel.style.maxWidth = "";
			this.panel.style.transform = "";
		}
	};

	Picker.prototype.getCountryById = function (countryId) {
		return this.state.countries.find(function (item) {
			return Number(item.id || 0) === Number(countryId || 0);
		}) || null;
	};

	Picker.prototype.getCityById = function (countryId, cityId) {
		var items = this.state.citiesCache[countryId] || [];
		return items.find(function (item) {
			return Number(item.id || 0) === Number(cityId || 0);
		}) || null;
	};

	Picker.prototype.refreshSelectionClasses = function () {
		var self = this;

		if (this.countryList) {
			this.countryList.querySelectorAll("[data-blp-country]").forEach(function (button) {
				var isSelected = Number(button.dataset.id || 0) === self.state.selected.countryId;
				button.classList.toggle("is-selected", isSelected);
			});
		}

		if (this.cityList) {
			this.cityList.querySelectorAll("[data-blp-city]").forEach(function (button) {
				var isSelected = Number(button.dataset.id || 0) === self.state.selected.cityId;
				button.classList.toggle("is-selected", isSelected);
			});
		}
	};

	Picker.prototype.refreshSummary = function () {
		var country = this.getCountryById(this.state.committed.countryId);
		var city = this.getCityById(this.state.committed.countryId, this.state.committed.cityId);
		var summary = this.config.summaryFallback || "";

		if (country && city && Number(city.id || 0) > 0) {
			summary = country.label + "، " + city.label;
		} else if (country) {
			summary = country.label;
		}

		if (this.summary) {
			this.summary.textContent = summary;
		}

		if (this.current) {
			this.current.textContent = summary;
		}

		if (this.activeInput) {
			this.activeInput.value = String(this.state.committed.cityId || this.state.committed.countryId || "");
		}

		syncFormAction(
			this.activeForm,
			city,
			country,
			this.config.actions && this.config.actions.all_countries_action
				? this.config.actions.all_countries_action
				: ""
		);

		this.refreshSelectionClasses();
	};

	Picker.prototype.syncDraftFromCommitted = function () {
		this.state.selected = cloneSelection(this.state.committed);
		this.renderCities(
			this.state.selected.countryId,
			this.state.selected.countryId ? (this.state.citiesCache[this.state.selected.countryId] || []) : []
		);
		this.refreshSelectionClasses();
	};

	Picker.prototype.renderCities = function (countryId, items) {
		var self = this;

		if (!this.cityList) {
			return;
		}

		this.cityList.innerHTML = "";
		if (this.citySearch) {
			this.citySearch.value = "";
			this.citySearch.disabled = !countryId;
		}

		if (!countryId) {
			return;
		}

		if (!items.length) {
			this.cityList.innerHTML = '<div class="blp__empty" data-blp-city-empty>' + String(globalConfig.strings && globalConfig.strings.noCities ? globalConfig.strings.noCities : "") + "</div>";
			return;
		}

		items.forEach(function (item) {
			self.cityList.appendChild(buildItemButton("city", item, self.state.selected.cityId));
		});

	};

	Picker.prototype.handleCountryClick = function (button) {
		var self = this;
		var countryId = Number(button.dataset.id || 0);
		var kind = String(button.dataset.kind || "country");

		if (kind === "reset" || countryId === 0) {
			this.resetSelection();
			return;
		}

		this.state.selected.countryId = countryId;
		this.state.selected.cityId = 0;
		this.state.selected.deepestTermId = countryId;
		this.refreshSelectionClasses();

		if (this.state.citiesCache[countryId]) {
			this.renderCities(countryId, this.state.citiesCache[countryId]);
			return;
		}

		if (this.cityList) {
			this.cityList.innerHTML = '<div class="blp__empty">' + String(globalConfig.strings && globalConfig.strings.loading ? globalConfig.strings.loading : "") + "</div>";
		}

		requestCities(countryId, true).then(function (items) {
			self.state.citiesCache[countryId] = items;
			self.renderCities(countryId, items);
			if (self.config.autoSubmit && (!items.length || items.length === 1)) {
				self.applySelection();
			}
		});
	};

	Picker.prototype.handleCityClick = function (button) {
		var cityId = Number(button.dataset.id || 0);

		this.state.selected.cityId = cityId;
		this.state.selected.deepestTermId = cityId || this.state.selected.countryId;
		this.refreshSelectionClasses();

		if (this.config.autoSubmit) {
			this.applySelection();
		}
	};

	Picker.prototype.resetSelection = function () {
		this.state.selected.countryId = 0;
		this.state.selected.cityId = 0;
		this.state.selected.deepestTermId = 0;
		syncFormAction(
			this.activeForm,
			null,
			null,
			this.config.actions && this.config.actions.all_countries_action
				? this.config.actions.all_countries_action
				: ""
		);
		this.renderCities(0, []);
		this.refreshSelectionClasses();
		if (this.config.autoSubmit && this.config.submitOnApply) {
			this.applySelection();
		}
	};

	Picker.prototype.syncSelectionFromCurrentContext = function () {
		var hasSelection = this.state.selected.countryId || this.state.selected.cityId || this.state.selected.deepestTermId;
		var hasInputValue = this.activeInput && String(this.activeInput.value || "").trim() !== "";

		if (hasCurrentLocationContext() || (!hasSelection && !hasInputValue)) {
			return;
		}

		this.state.selected.countryId = 0;
		this.state.selected.cityId = 0;
		this.state.selected.deepestTermId = 0;
		this.state.committed.countryId = 0;
		this.state.committed.cityId = 0;
		this.state.committed.deepestTermId = 0;
		this.renderCities(0, []);
		this.refreshSummary();
		syncPersistedLocationSelection(this.state.committed);
	};

	Picker.prototype.applySelection = function () {
		if (!this.activeForm) {
			return;
		}

		this.state.committed = cloneSelection(this.state.selected);
		this.refreshSummary();
		syncPersistedLocationSelection(this.state.committed);

		if (!this.config.submitOnApply) {
			this.closePanel();
			return;
		}

		if (typeof this.activeForm.requestSubmit === "function") {
			this.activeForm.requestSubmit();
		} else {
			this.activeForm.submit();
		}
		this.closePanel();
	};

	document.querySelectorAll("[data-blp-root]").forEach(function (root) {
		new Picker(root);
	});
})();
