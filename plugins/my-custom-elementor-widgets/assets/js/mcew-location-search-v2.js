(function ($) {
	"use strict";

	var searchCore = window.BornadoSearchCore || null;

	function getAjaxUrl() {
		if (window.MCEWLocationSearchV2 && window.MCEWLocationSearchV2.ajaxUrl) {
			return window.MCEWLocationSearchV2.ajaxUrl;
		}
		return window.ajaxurl || "";
	}

	function getNonce() {
		var themeNonce = $("#adforest_get_countries_nonce").val();
		if (themeNonce) {
			return themeNonce;
		}
		if (window.MCEWLocationSearchV2 && window.MCEWLocationSearchV2.nonce) {
			return window.MCEWLocationSearchV2.nonce;
		}
		return "";
	}

	function prepareCountryInput($widget) {
		var $countryInput = $widget.find("[data-mcew-country-input]");
		if (!$countryInput.length) {
			return;
		}

		if (searchCore && typeof searchCore.toggleFieldName === "function") {
			searchCore.toggleFieldName($countryInput[0], "country_id");
			return;
		}

		if ($countryInput.val()) {
			$countryInput.attr("name", "country_id");
			return;
		}

		$countryInput.val("");
		$countryInput.removeAttr("name");
	}

	function submitWidgetForm($widget, targetUrl, countryId) {
		var $form = $widget.find("#search_countries");
		var $countryInput = $widget.find("[data-mcew-country-input]");
		if (!$form.length) {
			return;
		}

		$form.attr("action", targetUrl || $form.attr("action") || window.location.href);
		if ($countryInput.length) {
			$countryInput.val(countryId || "");
		}
		prepareCountryInput($widget);

		if (searchCore && typeof searchCore.navigateWithForm === "function") {
			searchCore.navigateWithForm($form[0], targetUrl || $form.attr("action") || window.location.href);
			return;
		}

		$form.trigger("submit");
	}

	function handleResponse($widget, response) {
		var trimmed = $.trim(response || "");
		if (trimmed === "submit") {
			submitWidgetForm($widget, $widget.find("#search_countries").attr("action"), $widget.find("[data-mcew-country-input]").val());
			return;
		}

		$("#countries_response").html(response);
		$("#states_model").modal("show");
	}

	function requestRelatedCities($widget, countryId) {
		var ajaxUrl = getAjaxUrl();
		var nonce = getNonce();
		var $countryInput = $widget.find("[data-mcew-country-input]");

		if (!countryId || !ajaxUrl || !nonce) {
			submitWidgetForm($widget, $widget.find("#search_countries").attr("action"), countryId);
			return;
		}

		$countryInput.val(countryId);

		if (window.loading && typeof window.loading.show === "function") {
			window.loading.show();
		}
		$("#countries_response").html("");

		$.post(ajaxUrl, {
			action: "get_related_cities",
			country_id: countryId,
			security: nonce
		}).done(function (response) {
			if (window.loading && typeof window.loading.hide === "function") {
				window.loading.hide();
			}
			handleResponse($widget, response);
		}).fail(function () {
			if (window.loading && typeof window.loading.hide === "function") {
				window.loading.hide();
			}
			submitWidgetForm($widget, $widget.find("#search_countries").attr("action"), countryId);
		});
	}

	function handleSelect($select) {
		var $widget = $select.closest("[data-mcew-location-v2]");
		var $selectedOption = $select.find("option:selected");
		var countryId = String($selectedOption.data("country-id") || $select.val() || "");
		var $form = $widget.find("#search_countries");
		var allCitiesUrl = $widget.data("all-cities-url") || $form.attr("data-all-cities-action") || $form.attr("action") || window.location.href;

		if (!countryId) {
			submitWidgetForm($widget, allCitiesUrl, "");
			return;
		}

		requestRelatedCities($widget, countryId);
	}

	$(document).on("select2:select", "[data-mcew-location-v2] [data-mcew-location-select]", function () {
		$(this).data("mcew-last-select-value", $(this).val());
		handleSelect($(this));
	});

	$(document).on("change", "[data-mcew-location-v2] [data-mcew-location-select]", function () {
		var $select = $(this);
		var currentValue = String($select.val() || "");
		if ($select.data("mcew-last-select-value") === currentValue) {
			$select.removeData("mcew-last-select-value");
			return;
		}
		handleSelect($select);
	});
})(jQuery);
