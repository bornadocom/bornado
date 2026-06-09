(function (window, document) {
	"use strict";

	var config = window.BornadoWindowedInfiniteScrollConfig || {};
	if (!config.enabled) {
		return;
	}

	var state = null;
	var initTimer = 0;
	var ticking = false;
	var entrySequence = 0;
	var boundScrollRoot = null;
	var suppressReconcile = false;
	var keepBeforeCards =
		Number(config.windowing && config.windowing.keepBeforeCards) || 24;
	var keepAfterCards =
		Number(config.windowing && config.windowing.keepAfterCards) || 24;

	function toArray(value) {
		if (!value) {
			return [];
		}
		if (Array.isArray(value)) {
			return value;
		}
		if (
			typeof value.length === "number" &&
			typeof value !== "string" &&
			!value.nodeType &&
			!value.tagName
		) {
			try {
				return Array.prototype.slice.call(value);
			} catch (error) {
				return [];
			}
		}
		return [value];
	}

	function eachSelector(selectors, callback) {
		toArray(selectors).some(function (selector) {
			if (!selector || typeof selector !== "string") {
				return false;
			}
			return !!callback(selector);
		});
	}

	function queryFirst(root, selectors) {
		var match = null;
		eachSelector(selectors, function (selector) {
			match = root.querySelector(selector);
			return !!match;
		});
		return match;
	}

	function getResultsRoot(doc) {
		return doc.querySelector(config.resultsSelector) || doc;
	}

	function getContainer(root) {
		return queryFirst(root, config.containerSelectors);
	}

	function getCardNodes(scope) {
		var nodes = [];
		var seen = new Set();
		if (!scope || !scope.querySelectorAll) {
			return nodes;
		}

		eachSelector(config.cardSelectors, function (selector) {
			toArray(scope.querySelectorAll(selector)).forEach(function (node) {
				if (!node || seen.has(node)) {
					return;
				}
				seen.add(node);
				nodes.push(node);
			});
			return false;
		});

		return nodes;
	}

	function isScrollableElement(node) {
		if (!node || node === window || node === document || node === document.body) {
			return false;
		}

		var style = window.getComputedStyle ? window.getComputedStyle(node) : null;
		var overflowY = style ? style.overflowY : "";
		var canScroll = overflowY === "auto" || overflowY === "scroll";
		return canScroll && node.scrollHeight > node.clientHeight + 20;
	}

	function getScrollRoot(container) {
		return isScrollableElement(container) ? container : window;
	}

	function getScrollTop() {
		if (!state || !state.scrollRoot || state.scrollRoot === window) {
			return window.pageYOffset || document.documentElement.scrollTop || 0;
		}

		return state.scrollRoot.scrollTop || 0;
	}

	function scrollByOffset(delta) {
		if (!delta) {
			return;
		}

		if (!state || !state.scrollRoot || state.scrollRoot === window) {
			window.scrollBy(0, delta);
			return;
		}

		state.scrollRoot.scrollTop += delta;
	}

	function bindScrollRoot(node) {
		if (boundScrollRoot && boundScrollRoot !== window) {
			boundScrollRoot.removeEventListener("scroll", onScroll, { passive: true });
		}

		boundScrollRoot = node && node !== window ? node : null;
		if (boundScrollRoot) {
			boundScrollRoot.addEventListener("scroll", onScroll, { passive: true });
		}
	}

	function matchesCard(node) {
		if (!node || node.nodeType !== 1 || typeof node.matches !== "function") {
			return false;
		}

		var found = false;
		eachSelector(config.cardSelectors, function (selector) {
			if (node.matches(selector)) {
				found = true;
				return true;
			}
			return false;
		});
		return found;
	}

	function measureNodeHeight(node) {
		if (!node) {
			return 0;
		}
		return Math.max(
			1,
			Math.round(
				node.getBoundingClientRect().height ||
					node.offsetHeight ||
					node.clientHeight ||
					1
			)
		);
	}

	function createSpacer(entry) {
		var spacer = document.createElement("div");
		spacer.className = "bornado-wis-spacer";
		spacer.setAttribute("data-bornado-wis-id", entry.id);
		spacer.setAttribute("data-bornado-wis-spacer", "1");
		spacer.setAttribute("aria-hidden", "true");
		spacer.style.height = Math.max(1, entry.height || 1) + "px";
		return spacer;
	}

	function createNodeFromHtml(html) {
		var template = document.createElement("template");
		template.innerHTML = String(html || "").trim();
		return template.content.firstElementChild || null;
	}

	function assignEntryIdentity(entry, node) {
		if (!entry || !node) {
			return;
		}
		node.setAttribute("data-bornado-wis-id", entry.id);
	}

	function reinitializeNode(node) {
		if (!node || !window.jQuery) {
			return;
		}

		var $root = window.jQuery(node);
		if (window.jQuery.fn.tooltip) {
			$root.find("[data-toggle='tooltip']").tooltip();
		}
		if (window.jQuery.fn.owlCarousel) {
			$root.find(".adt-car-ad-carousel").each(function () {
				if (window.jQuery(this).hasClass("owl-loaded")) {
					return;
				}
				window.jQuery(this).owlCarousel({
					loop: true,
					rtl: typeof window.is_rtl !== "undefined" ? window.is_rtl : false,
					margin: 0,
					nav: true,
					navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
					dots: false,
					responsive: { 0: { items: 1 } },
				});
			});
		}
	}

	function buildEntry(node) {
		entrySequence += 1;
		var entry = {
			id: "wis-" + entrySequence,
			html: node.outerHTML,
			height: measureNodeHeight(node),
			mounted: true,
			node: node,
			spacer: null,
		};
		assignEntryIdentity(entry, node);
		return entry;
	}

	function getTrackedElement(entry) {
		return entry.mounted ? entry.node : entry.spacer;
	}

	function getEntryTop(entry) {
		var element = getTrackedElement(entry);
		if (!element) {
			return 0;
		}

		if (state && state.scrollRoot && state.scrollRoot !== window) {
			return element.offsetTop || 0;
		}

		return element.getBoundingClientRect().top + (window.pageYOffset || document.documentElement.scrollTop || 0);
	}

	function getAnchorIndex() {
		if (!state || !state.entries.length) {
			return 0;
		}

		var anchorY = getScrollTop() + 160;
		var index = 0;

		state.entries.forEach(function (entry, currentIndex) {
			var top = getEntryTop(entry);
			var height = entry.mounted ? measureNodeHeight(entry.node) : Math.max(1, entry.height || 1);
			if (anchorY >= top) {
				index = currentIndex;
			}
			if (anchorY >= top && anchorY < top + height) {
				index = currentIndex;
			}
		});

		return index;
	}

	function unmountEntry(entry) {
		if (!entry || !entry.mounted || !entry.node || !entry.node.parentNode) {
			return;
		}

		suppressReconcile = true;
		entry.html = entry.node.outerHTML;
		entry.height = measureNodeHeight(entry.node);
		entry.spacer = createSpacer(entry);
		entry.node.parentNode.replaceChild(entry.spacer, entry.node);
		entry.node = null;
		entry.mounted = false;
		suppressReconcile = false;
	}

	function mountEntry(entry, anchorIndex, currentIndex) {
		if (!entry || entry.mounted || !entry.spacer || !entry.spacer.parentNode) {
			return;
		}

		var beforeHeight = measureNodeHeight(entry.spacer);
		var node = createNodeFromHtml(entry.html);
		if (!node) {
			return;
		}

		suppressReconcile = true;
		assignEntryIdentity(entry, node);
		entry.spacer.parentNode.replaceChild(node, entry.spacer);
		entry.node = node;
		entry.mounted = true;
		reinitializeNode(node);

		var afterHeight = measureNodeHeight(node);
		entry.height = afterHeight;
		if (currentIndex < anchorIndex && Math.abs(afterHeight - beforeHeight) > 1) {
			scrollByOffset(afterHeight - beforeHeight);
		}
		suppressReconcile = false;
	}

	function applyWindowing() {
		if (!state || !state.entries.length) {
			return;
		}

		var anchorIndex = getAnchorIndex();
		var minIndex = Math.max(0, anchorIndex - keepBeforeCards);
		var maxIndex = Math.min(state.entries.length - 1, anchorIndex + keepAfterCards);

		state.entries.forEach(function (entry, index) {
			if (index >= minIndex && index <= maxIndex) {
				mountEntry(entry, anchorIndex, index);
				return;
			}
			unmountEntry(entry);
		});

		renderDebug(anchorIndex);
	}

	function renderDebug(anchorIndex) {
		if (!config.debug || !state || !state.root) {
			return;
		}

		if (!state.debugNode) {
			state.debugNode = document.createElement("div");
			state.debugNode.className = "bornado-wis-debug";
			state.root.appendChild(state.debugNode);
		}

		var mountedCards = state.entries.filter(function (entry) {
			return entry.mounted;
		}).length;

		state.debugNode.textContent =
			"mounted-cards:" +
			mountedCards +
			" | total-cards:" +
			state.entries.length +
			" | anchor-index:" +
			anchorIndex;
	}

	function resetState() {
		var root = getResultsRoot(document);
		var container = getContainer(root);
		if (!container) {
			state = null;
			return;
		}

		var entries = [];
		getCardNodes(container).forEach(function (cardNode) {
			entries.push(buildEntry(cardNode));
		});

		if (state && state.mutationObserver) {
			state.mutationObserver.disconnect();
		}

		state = {
			root: root,
			container: container,
			scrollRoot: getScrollRoot(container),
			entries: entries,
			debugNode: state && state.debugNode ? state.debugNode : null,
			mutationObserver: null,
		};

		container.classList.add("bornado-wis-container");
		bindScrollRoot(state.scrollRoot);
		if (typeof window.MutationObserver !== "undefined") {
			state.mutationObserver = new window.MutationObserver(function () {
				if (suppressReconcile) {
					return;
				}
				scheduleReconcile();
			});
			state.mutationObserver.observe(container, {
				childList: true,
				subtree: true,
			});
		}
		applyWindowing();
	}

	function reconcileNewCards() {
		if (!state || !state.container) {
			resetState();
			return;
		}

		var added = 0;
		getCardNodes(state.container).forEach(function (child) {
			if (child.hasAttribute("data-bornado-wis-id") || child.closest("[data-bornado-wis-spacer='1']")) {
				return;
			}
			state.entries.push(buildEntry(child));
			added += 1;
		});

		if (added > 0) {
			applyWindowing();
		}
	}

	function scheduleReset() {
		window.clearTimeout(initTimer);
		initTimer = window.setTimeout(resetState, 80);
	}

	function scheduleReconcile() {
		window.clearTimeout(initTimer);
		initTimer = window.setTimeout(reconcileNewCards, 80);
	}

	function onScroll() {
		if (ticking) {
			return;
		}
		ticking = true;
		window.requestAnimationFrame(function () {
			ticking = false;
			applyWindowing();
		});
	}

	window.BornadoWindowedInfiniteScroll = {
		refresh: scheduleReset,
		debug: function () {
			return state
				? {
						totalCards: state.entries.length,
						mountedCards: state.entries.filter(function (entry) {
							return entry.mounted;
						}).length,
						scrollRoot:
							state.scrollRoot && state.scrollRoot !== window
								? (state.scrollRoot.className || state.scrollRoot.id || "custom-scroll-root")
								: "window",
						entries: state.entries.map(function (entry, index) {
							return {
								index: index,
								id: entry.id,
								height: entry.height,
								mounted: entry.mounted,
							};
						}),
				  }
				: null;
		},
	};

	window.addEventListener("scroll", onScroll, { passive: true });
	window.addEventListener("resize", onScroll);
	window.addEventListener("pageshow", scheduleReset);

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", resetState, { once: true });
	} else {
		resetState();
	}

	if (window.jQuery) {
		window.jQuery(document).on("adforest:search:rendered", scheduleReset);
		window.jQuery(document).on("ajax_content_loaded", scheduleReconcile);
	}
})(window, document);
