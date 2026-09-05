(function ($) {
	'use strict';

	function bindSortIconDropdown() {
		var $select = $('#select-sort');
		if (!$select.length || $select.data('bornadoSortIconBound')) {
			return;
		}

		$select.data('bornadoSortIconBound', true);
		$select.attr('aria-label', 'مرتب‌سازی');

		$select.on('select2:open.bornadoSort', function () {
			window.setTimeout(function () {
				$('.select2-container--open .select2-dropdown').css({
					minWidth: '220px',
					width: 'auto'
				});
			}, 0);
		});
	}

	$(bindSortIconDropdown);
	$(document).on('adforest:ajax-search-complete', bindSortIconDropdown);
})(jQuery);
