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

	function requestCities(countryId) {
		if (!globalConfig.ajaxUrl || !globalConfig.nonce || !globalConfig.action) {
			return Promise.resolve([]);
		}

		var body = new URLSearchParams();
		body.set("action", globalConfig.action);
		body.set("nonce", globalConfig.nonce);
		body.set("country_id", String(countryId || 0));

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
			selected: {
				countryId: Number(this.config.selected && this.config.selected.countryId ? this.config.selected.countryId : 0),
				cityId: Number(this.config.selected && this.config.selected.cityId ? this.config.selected.cityId : 0),
				deepestTermId: Number(this.config.selected && this.config.selected.deepestTermId ? this.config.selected.deepestTermId : 0)
			},
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
		var country = this.getCountryById(this.state.selected.countryId);
		var city = this.getCityById(this.state.selected.countryId, this.state.selected.cityId);
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
			this.activeInput.value = String(this.state.selected.cityId || this.state.selected.countryId || "");
		}

		if (this.form && !this.externalForm) {
			if (city && city.url) {
				this.form.action = city.url;
			} else if (country && country.url) {
				this.form.action = country.url;
			} else if (this.config.actions && this.config.actions.all_countries_action) {
				this.form.action = this.config.actions.all_countries_action;
			}
		}

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
		this.refreshSummary();

		if (this.state.citiesCache[countryId]) {
			this.renderCities(countryId, this.state.citiesCache[countryId]);
			return;
		}

		if (this.cityList) {
			this.cityList.innerHTML = '<div class="blp__empty">' + String(globalConfig.strings && globalConfig.strings.loading ? globalConfig.strings.loading : "") + "</div>";
		}

		requestCities(countryId).then(function (items) {
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
		this.refreshSummary();

		if (this.config.autoSubmit) {
			this.applySelection();
		}
	};

	Picker.prototype.resetSelection = function () {
		this.state.selected.countryId = 0;
		this.state.selected.cityId = 0;
		this.state.selected.deepestTermId = 0;
		if (this.form && this.config.actions && this.config.actions.all_countries_action) {
			this.form.action = this.config.actions.all_countries_action;
		}
		this.renderCities(0, []);
		this.refreshSummary();
	};

	Picker.prototype.applySelection = function () {
		if (!this.activeForm) {
			return;
		}

		this.refreshSummary();

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
