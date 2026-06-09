(function (window) {
	"use strict";

	var config = window.BornadoSearchCoreConfig || {};
	var routeContext = config.routeContext || {};
	var resolvedCategoryTargets = {};
	var resolvedAllCategoriesUrl = "";
	var CONTEXT_KEYS = [
		"country_id",
		"ad_country",
		"location",
		"city_id",
		"bornado_country",
		"bornado_city"
	];
	var STRUCTURAL_KEYS = [
		"country_id",
		"ad_country",
		"cat_id",
		"ad_cats",
		"ad_cat_sub",
		"ad_cat_sub_sub",
		"ad_cat_sub_sub_sub",
		"ad_cat_sub_sub_sub_sub"
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

	function sortSearchParams(source) {
		var params = toSearchParams(source);
		var entries = [];
		var sorted = new window.URLSearchParams();

		params.forEach(function (value, key) {
			entries.push([key, value]);
		});

		entries.sort(function (left, right) {
			if (left[0] === right[0]) {
				return left[1] < right[1] ? -1 : left[1] > right[1] ? 1 : 0;
			}
			return left[0] < right[0] ? -1 : 1;
		});

		entries.forEach(function (entry) {
			sorted.append(entry[0], entry[1]);
		});

		return sorted;
	}

	function isSemanticRoute() {
		return !!routeContext.isSemanticRoute;
	}

	function toPublicSearchParams(source) {
		var params = toSearchParams(source);
		if (!isSemanticRoute()) {
			return sortSearchParams(params);
		}

		STRUCTURAL_KEYS.forEach(function (key) {
			params.delete(key);
		});

		return sortSearchParams(params);
	}

	function toEffectiveSearchParams(source) {
		var params = toSearchParams(source);
		if (!isSemanticRoute()) {
			return params;
		}

		if (!params.has("country_id") && !params.has("ad_country")) {
			if (routeContext.cityId) {
				params.set("country_id", String(routeContext.cityId));
			} else if (routeContext.countryId) {
				params.set("country_id", String(routeContext.countryId));
			}
		}

		if (!params.has("cat_id") && !params.has("ad_cats") && routeContext.categoryId) {
			params.set("cat_id", String(routeContext.categoryId));
		}

		return params;
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

		return sortSearchParams(searchParams);
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
		return toPublicSearchParams(window.location.search);
	};

	api.getEffectiveCurrentSearchParams = function () {
		return toEffectiveSearchParams(window.location.search);
	};

	api.getPublicSearchParams = function (source) {
		return toPublicSearchParams(source);
	};

	api.buildVisibleUrl = function (targetUrl, source) {
		if (typeof window.URL === "undefined") {
			return null;
		}

		var url = new window.URL(targetUrl || window.location.href, window.location.origin);
		var params = sortSearchParams(toPublicSearchParams(source));
		url.search = params.toString();
		return url;
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
		url.search = searchParams ? sortSearchParams(searchParams).toString() : "";
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

	function getNavigationUrl(targetUrl) {
		var href = trimValue(targetUrl);
		if (href === "" || href === "#" || href.indexOf("javascript:") === 0 || href.indexOf("mailto:") === 0 || href.indexOf("tel:") === 0) {
			return null;
		}

		if (typeof window.URL === "undefined") {
			return null;
		}

		try {
			var url = new window.URL(href, window.location.origin);
			if (url.origin !== window.location.origin) {
				return null;
			}

			return url;
		} catch (error) {
			return null;
		}
	}

	function getCatIdFromUrl(targetUrl) {
		var url = getNavigationUrl(targetUrl);
		if (!url) {
			return 0;
		}

		var catId = Number(url.searchParams.get("cat_id") || 0);
		return catId > 0 ? catId : 0;
	}

	function appendQueryArgs(formData, prefix, value) {
		if (!formData || !prefix) {
			return;
		}

		if (Array.isArray(value)) {
			value.forEach(function (entry) {
				appendQueryArgs(formData, prefix + "[]", entry);
			});
			return;
		}

		if (value && typeof value === "object") {
			Object.keys(value).forEach(function (key) {
				appendQueryArgs(formData, prefix + "[" + key + "]", value[key]);
			});
			return;
		}

		var safeValue = trimValue(value);
		if (safeValue !== "") {
			formData.append(prefix, safeValue);
		}
	}

	function getCurrentLocationId() {
		var effectiveParams = api.getEffectiveCurrentSearchParams();
		var locationId = Number(
			effectiveParams.get("country_id") ||
			effectiveParams.get("ad_country") ||
			routeContext.cityId ||
			routeContext.countryId ||
			0
		);

		return locationId > 0 ? locationId : 0;
	}

	function getCurrentPublicQueryObject() {
		var params = api.getCleanCurrentSearchParams();
		var query = {};
		if (!params || typeof params.forEach !== "function") {
			return query;
		}

		params.forEach(function (value, key) {
			var safeKey = trimValue(key);
			var safeValue = trimValue(value);
			if (safeKey !== "" && safeValue !== "") {
				query[safeKey] = safeValue;
			}
		});

		return query;
	}

	function onDocumentReady(callback) {
		if (typeof callback !== "function") {
			return;
		}

		if (document.readyState === "loading") {
			document.addEventListener("DOMContentLoaded", callback, { once: true });
			return;
		}

		callback();
	}

	function syncCategoryLinkTargets(root) {
		if (!root || !root.querySelectorAll) {
			return;
		}

		root.querySelectorAll(".category-meta").forEach(function (meta) {
			var anchors = meta.querySelectorAll("a");
			if (anchors.length < 2) {
				return;
			}

			var primaryLink = anchors[0];
			var secondaryLink = anchors[1];
			var primaryTarget = trimValue(primaryLink.getAttribute("data-target-url") || primaryLink.getAttribute("href"));
			if (!getNavigationUrl(primaryTarget)) {
				return;
			}

			var secondaryTarget = trimValue(secondaryLink.getAttribute("href"));
			if (secondaryTarget === "" || secondaryTarget === "#" || secondaryTarget.indexOf("javascript:") === 0) {
				secondaryLink.setAttribute("href", primaryTarget);
			}
			secondaryLink.setAttribute("data-target-url", primaryTarget);
		});
	}

	function applyResolvedCategoryTargets(root, payload) {
		if (!root || !root.querySelectorAll || !payload || typeof payload !== "object") {
			return;
		}

		var categoryUrls = payload.categoryUrls || {};
		var allUrl = trimValue(payload.allUrl || "");

		Object.keys(categoryUrls).forEach(function (catId) {
			resolvedCategoryTargets[String(catId)] = categoryUrls[catId];
		});
		if (allUrl !== "") {
			resolvedAllCategoriesUrl = allUrl;
		}

		root.querySelectorAll("a.category_click_link[data-cat-id]").forEach(function (link) {
			var catId = trimValue(link.getAttribute("data-cat-id"));
			if (!catId || !Object.prototype.hasOwnProperty.call(resolvedCategoryTargets, catId)) {
				return;
			}

			link.setAttribute("href", resolvedCategoryTargets[catId]);
			link.setAttribute("data-target-url", resolvedCategoryTargets[catId]);
		});

		root.querySelectorAll(".categories_search a.btn").forEach(function (link) {
			var catId = getCatIdFromUrl(link.getAttribute("href"));
			if (catId > 0 && Object.prototype.hasOwnProperty.call(resolvedCategoryTargets, String(catId))) {
				link.setAttribute("href", resolvedCategoryTargets[String(catId)]);
				link.setAttribute("data-target-url", resolvedCategoryTargets[String(catId)]);
				return;
			}

			if (resolvedAllCategoriesUrl !== "" && trimValue(link.getAttribute("href")) !== "" && catId < 1) {
				link.setAttribute("href", resolvedAllCategoriesUrl);
				link.setAttribute("data-target-url", resolvedAllCategoriesUrl);
			}
		});

		syncCategoryLinkTargets(root);
	}

	function requestCategoryTargets(root, preferredCatId, callback) {
		if (!root || !root.querySelectorAll || typeof window.FormData === "undefined" || typeof window.fetch !== "function") {
			if (typeof callback === "function") {
				callback(false);
			}
			return;
		}

		var ajaxUrl = trimValue(config.ajaxUrl || "");
		if (ajaxUrl === "") {
			if (typeof callback === "function") {
				callback(false);
			}
			return;
		}

		var catIds = [];
		root.querySelectorAll("a.category_click_link[data-cat-id]").forEach(function (link) {
			var catId = Number(link.getAttribute("data-cat-id") || 0);
			if (catId > 0 && catIds.indexOf(catId) === -1) {
				catIds.push(catId);
			}
		});

		root.querySelectorAll(".categories_search a.btn[href]").forEach(function (link) {
			var catId = getCatIdFromUrl(link.getAttribute("href"));
			if (catId > 0 && catIds.indexOf(catId) === -1) {
				catIds.push(catId);
			}
		});

		preferredCatId = Number(preferredCatId || 0);
		if (preferredCatId > 0 && catIds.indexOf(preferredCatId) === -1) {
			catIds.push(preferredCatId);
		}

		if (!catIds.length) {
			if (typeof callback === "function") {
				callback(false);
			}
			return;
		}

		var formData = new window.FormData();
		formData.append("action", "bornado_semantic_category_targets");
		catIds.forEach(function (catId) {
			formData.append("cat_ids[]", String(catId));
		});
		var locationId = getCurrentLocationId();
		if (locationId) {
			formData.append("country_id", String(locationId));
		}
		appendQueryArgs(formData, "query_args", getCurrentPublicQueryObject());

		window.fetch(ajaxUrl, {
			method: "POST",
			credentials: "same-origin",
			body: formData
		})
			.then(function (response) {
				return response.ok ? response.json() : null;
			})
			.then(function (payload) {
				if (!payload || !payload.success || !payload.data) {
					if (typeof callback === "function") {
						callback(false);
					}
					return;
				}

				applyResolvedCategoryTargets(root, payload.data);
				if (typeof callback === "function") {
					callback(true);
				}
			})
			.catch(function () {
				if (typeof callback === "function") {
					callback(false);
				}
				return null;
			});
	}

	function navigateToTarget(targetUrl) {
		var url = getNavigationUrl(targetUrl);
		if (!url) {
			return false;
		}

		api.persistSearchParams(url.search);
		window.location.href = url.toString();
		return true;
	}

	function bootCategoryRoutingHelpers() {
		syncCategoryLinkTargets(document);
		requestCategoryTargets(document);
	}

	if (typeof document !== "undefined") {
		onDocumentReady(bootCategoryRoutingHelpers);

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

		document.addEventListener(
			"click",
			function (event) {
				var target = event.target;
				var link = target && target.closest ? target.closest("a.category_click_link[href]") : null;
				if (!link) {
					return;
				}

				event.preventDefault();
				event.stopPropagation();
				if (typeof event.stopImmediatePropagation === "function") {
					event.stopImmediatePropagation();
				}

				var targetUrl = link.getAttribute("data-target-url") || link.getAttribute("href");
				if (getNavigationUrl(targetUrl)) {
					navigateToTarget(targetUrl);
					return;
				}

				var catId = Number(link.getAttribute("data-cat-id") || 0);
				if (catId > 0 && Object.prototype.hasOwnProperty.call(resolvedCategoryTargets, String(catId))) {
					navigateToTarget(resolvedCategoryTargets[String(catId)]);
					return;
				}

				requestCategoryTargets(document, catId, function (resolved) {
					var fallbackTarget = catId > 0 ? resolvedCategoryTargets[String(catId)] : "";
					if (resolved && getNavigationUrl(fallbackTarget)) {
						navigateToTarget(fallbackTarget);
					}
				});
			},
			true
		);

		document.addEventListener(
			"change",
			function (event) {
				var select = toElement(event.target);
				if (!select || String(select.tagName || "").toLowerCase() !== "select") {
					return;
				}

				var selectedOption = select.options && select.selectedIndex >= 0 ? select.options[select.selectedIndex] : null;
				if (!selectedOption) {
					return;
				}

				var targetUrl = selectedOption.getAttribute("data-target-url");
				if (!getNavigationUrl(targetUrl)) {
					return;
				}

				navigateToTarget(targetUrl);
			},
			true
		);
	}

	window.BornadoSearchCore = api;
})(window);
