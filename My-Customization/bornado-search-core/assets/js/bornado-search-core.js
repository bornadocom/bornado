(function (window) {
	"use strict";

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
