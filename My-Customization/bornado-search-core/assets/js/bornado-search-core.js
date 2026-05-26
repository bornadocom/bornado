(function (window) {
	"use strict";

	var config = window.BornadoSearchCoreConfig || {};
	var CONTEXT_KEYS = [
		"country_id",
		"ad_country",
		"location",
		"city_id",
		"bornado_country",
		"bornado_city"
	];

	function toElement(node) {
		if (window.jQuery && node && node.jquery) {
			return node[0] || null;
		}
		return node || null;
	}

	function trimValue(value) {
		return value == null ? "" : String(value).trim();
	}

	function isFileValue(value) {
		return typeof window.File !== "undefined" && value instanceof window.File;
	}

	function toSearchParams(source) {
		if (source instanceof window.URLSearchParams) {
			return new window.URLSearchParams(source.toString());
		}

		if (typeof source === "string") {
			return new window.URLSearchParams(source.charAt(0) === "?" ? source.slice(1) : source);
		}

		if (source && typeof source === "object") {
			var params = new window.URLSearchParams();
			Object.keys(source).forEach(function (key) {
				var value = source[key];
				if (Array.isArray(value)) {
					value.forEach(function (entry) {
						params.append(key, entry);
					});
					return;
				}
				params.append(key, value);
			});
			return params;
		}

		return new window.URLSearchParams();
	}

	function getCookieName() {
		return String(config.cookieName || "bornado_search_context");
	}

	function getCookieTtl() {
		var ttl = Number(config.cookieTtl || 2592000);
		return ttl > 0 ? ttl : 2592000;
	}

	function readContextCookie() {
		var cookieName = getCookieName();
		var prefix = cookieName + "=";
		var entries = String(document.cookie || "").split(";");

		for (var index = 0; index < entries.length; index += 1) {
			var item = entries[index].trim();
			if (item.indexOf(prefix) !== 0) {
				continue;
			}

			try {
				var rawValue = decodeURIComponent(item.slice(prefix.length));
				var parsed = JSON.parse(rawValue);
				return parsed && typeof parsed === "object" ? parsed : {};
			} catch (error) {
				return {};
			}
		}

		return {};
	}

	function normalizeContextValue(value) {
		return value == null ? "" : String(value).trim();
	}

	function writeContextCookie(context) {
		var payload = {};

		CONTEXT_KEYS.forEach(function (key) {
			if (!Object.prototype.hasOwnProperty.call(context || {}, key)) {
				return;
			}

			var value = normalizeContextValue(context[key]);
			if (value !== "") {
				payload[key] = value;
			}
		});

		document.cookie =
			getCookieName() +
			"=" +
			encodeURIComponent(JSON.stringify(payload)) +
			"; path=/; max-age=" +
			String(getCookieTtl()) +
			"; SameSite=Lax";
	}

	function isCustomManagedSearchForm(form) {
		return !!(
			form.getAttribute("data-default-action") ||
			form.querySelector("[data-search-role]") ||
			form.querySelector("[data-mcew-country-input]") ||
			form.classList.contains("adf-mobile-top-search__form")
		);
	}

	function isLikelySearchForm(form) {
		if (!form || String(form.method || "get").toLowerCase() !== "get") {
			return false;
		}

		if (form.getAttribute("data-bornado-search-core-auto") === "off") {
			return false;
		}

		if (isCustomManagedSearchForm(form)) {
			return false;
		}

		return !!form.querySelector(
			'[name="ad_title"], [name="country_id"], [name="cat_id"], [name="ad_country"], [name="location"], [name="ad_cats"], [name="ad_type"], [name="type"], [name^="ad_cat_sub"], [name^="custom["], [name^="min_custom["], [name^="max_custom["]'
		);
	}

	var api = window.BornadoSearchCore || {};

	api.toggleFieldName = function (field, name) {
		if (!field) {
			return "";
		}

		var normalized = trimValue(field.value);
		if (normalized !== "") {
			field.value = normalized;
			field.setAttribute("name", name);
			return normalized;
		}

		field.value = "";
		field.removeAttribute("name");
		return "";
	};

	api.buildSearchParams = function (form) {
		form = toElement(form);
		if (!form || typeof window.FormData === "undefined" || typeof window.URLSearchParams === "undefined") {
			return null;
		}

		var formData = new window.FormData(form);
		var searchParams = new window.URLSearchParams();
		formData.forEach(function (value, key) {
			var safeKey = trimValue(key);
			if (safeKey === "") {
				return;
			}

			if (isFileValue(value)) {
				if (value.name) {
					searchParams.append(safeKey, value.name);
				}
				return;
			}

			var safeValue = trimValue(value);
			if (safeValue !== "") {
				searchParams.append(safeKey, safeValue);
			}
		});

		return searchParams;
	};

	api.buildSearchParamsFromSource = function (source) {
		if (typeof window.URLSearchParams === "undefined") {
			return null;
		}

		var rawParams = toSearchParams(source);
		var searchParams = new window.URLSearchParams();
		rawParams.forEach(function (value, key) {
			var safeKey = trimValue(key);
			var safeValue = trimValue(value);
			if (safeKey !== "" && safeValue !== "") {
				searchParams.append(safeKey, safeValue);
			}
		});

		return searchParams;
	};

	api.getCleanCurrentSearchParams = function () {
		return api.buildSearchParamsFromSource(window.location.search) || new window.URLSearchParams();
	};

	api.getPersistedContext = function () {
		return readContextCookie();
	};

	api.clearPersistedContext = function (keys) {
		var persisted = readContextCookie();
		(keys || []).forEach(function (key) {
			delete persisted[key];
		});
		writeContextCookie(persisted);
	};

	api.mergePersistedContext = function (source) {
		var persisted = readContextCookie();
		var searchParams = api.buildSearchParamsFromSource(source);
		if (!searchParams) {
			return persisted;
		}

		CONTEXT_KEYS.forEach(function (key) {
			if (!searchParams.has(key)) {
				return;
			}
			persisted[key] = normalizeContextValue(searchParams.get(key));
		});

		writeContextCookie(persisted);
		return persisted;
	};

	api.persistSearchParams = function (source) {
		var persisted = readContextCookie();
		var searchParams = api.buildSearchParamsFromSource(source);
		if (!searchParams) {
			writeContextCookie(persisted);
			return persisted;
		}

		CONTEXT_KEYS.forEach(function (key) {
			if (searchParams.has(key)) {
				persisted[key] = normalizeContextValue(searchParams.get(key));
				return;
			}
			delete persisted[key];
		});

		writeContextCookie(persisted);
		return persisted;
	};

	api.persistFormContext = function (form) {
		var searchParams = api.buildSearchParams(form);
		return api.persistSearchParams(searchParams);
	};

	api.buildUrlFromForm = function (form, targetUrl) {
		form = toElement(form);
		if (!form || typeof window.URL === "undefined") {
			return null;
		}

		var searchParams = api.buildSearchParams(form);
		var url = new window.URL(targetUrl || form.getAttribute("action") || window.location.href, window.location.origin);
		url.search = searchParams ? searchParams.toString() : "";
		return url;
	};

	api.navigateWithForm = function (form, targetUrl) {
		var url = api.buildUrlFromForm(form, targetUrl);
		if (!url) {
			return false;
		}

		api.persistSearchParams(url.search);
		window.location.href = url.toString();
		return true;
	};

	api.hasMeaningfulQuery = function (form) {
		var searchParams = api.buildSearchParams(form);
		return !!(searchParams && searchParams.toString());
	};

	api.getAction = function (form, attrName, fallback) {
		form = toElement(form);
		if (!form) {
			return fallback || window.location.href;
		}

		return form.getAttribute(attrName) || fallback || form.getAttribute("action") || window.location.href;
	};

	if (typeof document !== "undefined") {
		document.addEventListener(
			"submit",
			function (event) {
				var form = toElement(event.target);
				if (!isLikelySearchForm(form) || event.defaultPrevented) {
					return;
				}

				event.preventDefault();
				api.navigateWithForm(form, form.getAttribute("action") || window.location.href);
			},
			true
		);
	}

	window.BornadoSearchCore = api;
})(window);
