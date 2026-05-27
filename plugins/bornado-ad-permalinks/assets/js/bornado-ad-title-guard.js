(function () {
	"use strict";

	var config = window.bornadoAdTitleGuard || {};
	var minLength = Number(config.minLength || 20);
	var maxLength = Number(config.maxLength || 80);
	var message = String(config.message || "");
	var placeholder = String(config.placeholder || "");

	function showError(text) {
		if (window.toastr && typeof window.toastr.error === "function") {
			window.toastr.error(text, "", {
				timeOut: 5000,
				closeButton: true,
				positionClass: "toast-top-right"
			});
			return;
		}

		window.alert(text);
	}

	function getTitleField() {
		return document.getElementById("ad_title");
	}

	function updateFieldAttributes(field) {
		if (!field) {
			return;
		}

		field.setAttribute("maxlength", String(maxLength));
		field.setAttribute("minlength", String(minLength));
		field.setAttribute("data-parsley-maxlength", String(maxLength));
		field.setAttribute("data-parsley-minlength", String(minLength));
		field.setAttribute("dir", "auto");

		if (placeholder) {
			field.setAttribute("placeholder", placeholder);
		}
	}

	function getTitleLength(field) {
		if (!field || typeof field.value !== "string") {
			return 0;
		}

		return field.value.trim().length;
	}

	function validateTitle(field) {
		var length = getTitleLength(field);
		var isValid = length >= minLength && length <= maxLength;

		if (typeof field.setCustomValidity === "function") {
			field.setCustomValidity(isValid ? "" : message);
		}

		return isValid;
	}

	function bindSubmitGuard(form) {
		if (!form) {
			return;
		}

		form.addEventListener("submit", function (event) {
			var field = getTitleField();
			if (!field) {
				return;
			}

			updateFieldAttributes(field);
			if (validateTitle(field)) {
				return;
			}

			event.preventDefault();
			event.stopPropagation();
			if (typeof event.stopImmediatePropagation === "function") {
				event.stopImmediatePropagation();
			}

			showError(message);
			field.focus();
		}, true);
	}

	function init() {
		var field = getTitleField();
		var form = document.getElementById("adforest-ad-post-form");

		if (!field || !form) {
			return;
		}

		updateFieldAttributes(field);
		field.addEventListener("input", function () {
			validateTitle(field);
		});
		field.addEventListener("blur", function () {
			validateTitle(field);
		});

		bindSubmitGuard(form);
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", init);
	} else {
		init();
	}
})();
