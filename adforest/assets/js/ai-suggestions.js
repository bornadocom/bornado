/**
 * AI-Based Title & Description Suggestions for Ad Posting
 *
 * This script handles the client-side functionality for generating
 * AI-powered suggestions for ad titles and descriptions.
 *
 * @package AdForest
 * @since 1.0.0
 *
 * Features:
 * - Triggers on category/subcategory selection
 * - Collects custom field data from the form
 * - Makes AJAX request to get AI suggestions
 * - Auto-fills title and description fields
 * - Allows manual editing after auto-fill
 *
 * Dependencies:
 * - jQuery
 * - toastr (for notifications, optional)
 *
 * Feature Guard:
 * - This script checks adforestAiSuggestions.ready before initialization
 * - If NOT ready: NO button shown, NO events attached, NO AJAX requests fired
 * - AI is a PROGRESSIVE ENHANCEMENT - when not ready, ad posting works normally
 * - Single source of truth: PHP adforest_is_ai_ready() function
 */

(function($) {
    'use strict';

    /**
     * Debug Mode Flag
     * Set to true only for development/debugging purposes.
     * In production, this should always be false.
     * Can be controlled from PHP via adforestAiSuggestions.debug
     */
    var ADFOREST_AI_DEBUG = (typeof adforestAiSuggestions !== 'undefined' && adforestAiSuggestions.debug === true);

    /**
     * Debug logging helper function
     * Only outputs to console when ADFOREST_AI_DEBUG is true
     * Safe to leave in production code - no output when debug is off
     *
     * @param {...*} arguments - Any arguments to pass to console.log
     */
    function aiDebugLog() {
        if (!ADFOREST_AI_DEBUG || typeof console === 'undefined' || typeof console.log !== 'function') {
            return;
        }
        console.log.apply(console, arguments);
    }

    aiDebugLog('[AI] ai-suggestions.js loaded');

    // Check config availability
    var configAvailable = typeof adforestAiSuggestions !== 'undefined';

    aiDebugLog('[AI] Config available:', configAvailable);

    // If config not available, we cannot proceed at all
    if (!configAvailable) {
        aiDebugLog('[AI] FATAL: adforestAiSuggestions not localized');

        // EMERGENCY: Bind a click handler that shows an error
        $(document).on('click', '#adforest-get-ai-suggestions', function(e) {
            e.preventDefault();
            alert('AI Suggestions Error: Configuration not loaded. Please contact administrator.');
        });
        return;
    }

    aiDebugLog('[AI] Config loaded, proceeding with initialization');

    /**
     * AI Suggestions Module
     */
    var AiSuggestions = {
        // Configuration
        config: {
            ajaxUrl: adforestAiSuggestions.ajaxUrl,
            nonce: adforestAiSuggestions.nonce,
            strings: adforestAiSuggestions.strings
        },

        // DOM elements cache
        elements: {
            form: null,
            categorySelect: null,
            titleField: null,
            descriptionField: null,
            aiButton: null,
            loadingIndicator: null
        },

        // State
        state: {
            isLoading: false,
            lastCategoryId: null,
            lastSubcategoryId: null,
            suggestionsGenerated: false
        },

        /**
         * Initialize the module
         *
         * IMPORTANT: Event binding uses delegation and happens immediately.
         * No dependency on specific form IDs or DOM element existence.
         * This ensures click handling works regardless of page structure.
         */
        init: function() {
            var self = this;

            aiDebugLog('[AI] init() called');

            // Bind delegated events IMMEDIATELY - no DOM dependency
            // This ensures clicks work even if elements are loaded dynamically
            self.bindEvents();

            // Add CSS styles for button hover effects
            self.addStyles();

            // Cache elements when DOM is ready (for non-critical operations)
            $(document).ready(function() {
                self.cacheElements();
                aiDebugLog('[AI] DOM ready, button exists:', $('#adforest-get-ai-suggestions').length > 0);
            });
        },

        /**
         * Cache DOM elements for performance
         * These are used for reading values, not for event binding
         */
        cacheElements: function() {
            this.elements.categorySelect = $('#ad_post_category_select');
            this.elements.titleField = $('#ad_title');
            this.elements.descriptionField = $('#ad_description');
            this.elements.aiButton = $('#adforest-get-ai-suggestions');
            this.elements.loadingIndicator = $('#ai-suggestions-loading');
        },

        /**
         * Add CSS styles for the AI button
         */
        addStyles: function() {
            var styles = '<style>' +
                '.ai-suggestions-btn {' +
                '    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);' +
                '    border: none;' +
                '    color: #fff;' +
                '    padding: 8px 16px;' +
                '    border-radius: 5px;' +
                '    cursor: pointer;' +
                '    font-size: 14px;' +
                '    transition: all 0.3s ease;' +
                '}' +
                '.ai-suggestions-btn:hover {' +
                '    transform: translateY(-2px);' +
                '    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);' +
                '    color: #fff;' +
                '}' +
                '.ai-suggestions-btn:disabled {' +
                '    opacity: 0.6;' +
                '    cursor: not-allowed;' +
                '    transform: none;' +
                '}' +
                '.ai-suggestions-btn i {' +
                '    margin-right: 5px;' +
                '}' +
                '.ai-suggestions-loading {' +
                '    color: #667eea;' +
                '    font-size: 14px;' +
                '}' +
                '.ai-suggestions-loading i {' +
                '    margin-right: 5px;' +
                '}' +
                '.ai-field-highlight {' +
                '    animation: aiHighlight 1s ease-out;' +
                '}' +
                '@keyframes aiHighlight {' +
                '    0% { background-color: rgba(102, 126, 234, 0.3); }' +
                '    100% { background-color: transparent; }' +
                '}' +
                '</style>';

            $('head').append(styles);
        },

        /**
         * Bind event handlers
         *
         * NOTE: Button visibility is controlled entirely by PHP (server-side).
         * The wizard guarantees category selection before reaching the Ad Content tab.
         * JS only handles: click event, loading state, AJAX request, notifications.
         */
        bindEvents: function() {
            var self = this;

            aiDebugLog('[AI] bindEvents() called');

            // Listen for category changes (for state tracking only)
            $(document).on('change', '#ad_post_category_select', function() {
                self.onCategoryChange();
            });

            // Listen for subcategory changes (for state tracking only)
            $(document).on('change', '[id^="ad_post_child_category_select_"]', function() {
                self.onSubcategoryChange();
            });

            // AI button click handler (server-rendered button ID)
            $(document).on('click', '#adforest-get-ai-suggestions', function(e) {
                aiDebugLog('[AI] Button clicked');
                e.preventDefault();
                self.generateSuggestions();
            });
        },

        /**
         * Handle main category change
         */
        onCategoryChange: function() {
            var categoryId = $('#ad_post_category_select').val();

            if (categoryId && categoryId !== this.state.lastCategoryId) {
                this.state.lastCategoryId = categoryId;
                this.state.lastSubcategoryId = null;
                this.state.suggestionsGenerated = false;
            }
        },

        /**
         * Handle subcategory change
         */
        onSubcategoryChange: function() {
            // Get the last selected subcategory
            var $lastSubcat = $('[id^="ad_post_child_category_select_"]:visible').last();
            if ($lastSubcat.length && $lastSubcat.val()) {
                this.state.lastSubcategoryId = $lastSubcat.val();
                this.state.suggestionsGenerated = false;
            }
        },

        /**
         * Collect custom field values from the form
         * Extracts clean text values, handling various field types
         */
        collectCustomFields: function() {
            var customFields = {};

            // Helper to clean value (remove "id|" prefix if present)
            var cleanValue = function(value) {
                if (typeof value === 'string' && value.indexOf('|') !== -1) {
                    // Format is "id|text" - extract text part
                    return value.split('|').pop().trim();
                }
                return value ? value.trim() : '';
            };

            // Collect from button-set fields (Condition, Warranty, etc.)
            // These have active class on selected button
            $('.pretty input:checked, .btn-group .btn.active, [class*="button-set"] .active').each(function() {
                var $input = $(this);
                var name = $input.attr('name') || $input.closest('[data-field-name]').data('field-name');
                var value = '';

                // Get the label text for the selected option
                var $label = $input.closest('label, .pretty').find('.state label, span');
                if ($label.length) {
                    value = $label.first().text().trim();
                } else {
                    value = cleanValue($input.val());
                }

                if (name && value) {
                    var key = name.replace('ad_', '').replace('_adforest_tpl_field_', '').replace(/[\[\]]/g, '');
                    customFields[key] = value;
                }
            });

            // Collect standard input fields
            var standardFields = ['ad_price', 'ad_address', 'ad_year', 'ad_mileage'];
            standardFields.forEach(function(fieldName) {
                var $field = $('input[name="' + fieldName + '"]');
                if ($field.length && $field.val()) {
                    var key = fieldName.replace('ad_', '');
                    customFields[key] = cleanValue($field.val());
                }
            });

            // Collect from select dropdowns (use selected text, not value)
            $('select[name^="ad_"], select[name^="_adforest_tpl_field_"]').each(function() {
                var $select = $(this);
                var fieldName = $select.attr('name');
                var $selected = $select.find('option:selected');
                var selectedText = $selected.text().trim();

                // Skip empty/placeholder options
                if (selectedText && selectedText !== '' &&
                    selectedText.toLowerCase() !== 'select option' &&
                    selectedText.toLowerCase() !== 'choose option' &&
                    $selected.val() !== '') {
                    var key = fieldName.replace('ad_', '').replace('_adforest_tpl_field_', '').replace(/[\[\]]/g, '');
                    customFields[key] = selectedText;
                }
            });

            // Collect template-specific custom fields (inputs)
            $('input[name^="_adforest_tpl_field_"]').each(function() {
                var $field = $(this);
                var fieldName = $field.attr('name');
                var fieldValue = $field.val();

                if (fieldValue && $field.attr('type') !== 'hidden') {
                    var key = fieldName.replace('_adforest_tpl_field_', '').replace(/[\[\]]/g, '');
                    customFields[key] = cleanValue(fieldValue);
                }
            });

            return customFields;
        },

        /**
         * Get the deepest selected subcategory ID
         * Uses the same selector pattern as AdForest theme (.child-category-select)
         */
        getDeepestSubcategoryId: function() {
            var subcategoryId = null;

            // Method 1: Use the same pattern as AdForest theme's getLastSelectedCategory()
            // This iterates through all child-category-select elements
            $('.child-category-select').each(function() {
                var $select = $(this);
                if ($select.val() && $select.val() !== '') {
                    subcategoryId = $select.val();
                }
            });

            // Method 2: Fallback - check by ID pattern if class selector didn't find anything
            if (!subcategoryId) {
                for (var i = 6; i >= 1; i--) {
                    var $select = $('#ad_post_child_category_select_' + i);
                    if ($select.length && $select.val() && $select.val() !== '') {
                        subcategoryId = $select.val();
                        break;
                    }
                }
            }

            // Method 3: Check for any select inside #child-category-container
            if (!subcategoryId) {
                $('#child-category-container select').each(function() {
                    var $select = $(this);
                    if ($select.val() && $select.val() !== '') {
                        subcategoryId = $select.val();
                    }
                });
            }

            return subcategoryId;
        },

        /**
         * Collect intent field values (Ad Type, Condition, Warranty)
         *
         * These fields determine the language and tone of AI suggestions:
         * - Ad Type: Buy/Sell/Exchange/Lost & Found
         * - Condition: New/Used/Refurbished
         * - Warranty: Yes/No/etc.
         *
         * IMPORTANT: We never guess intent - only send what the user selected
         *
         * @return {Object} Intent field values
         */
        collectIntentFields: function() {
            var intent = {};

            // Ad Type (taxonomy: ad_type)
            // Common selectors: select[name="ad_type"], input[name="ad_type"]:checked
            var $adTypeSelect = $('select[name="ad_type"]');
            var $adTypeRadio = $('input[name="ad_type"]:checked');

            if ($adTypeSelect.length && $adTypeSelect.val()) {
                intent.ad_type = $adTypeSelect.val();
            } else if ($adTypeRadio.length && $adTypeRadio.val()) {
                intent.ad_type = $adTypeRadio.val();
            }

            // Condition (taxonomy: ad_condition)
            var $conditionSelect = $('select[name="ad_condition"]');
            var $conditionRadio = $('input[name="ad_condition"]:checked');
            var $conditionPretty = $('.ad-condition-field input:checked, [data-field="condition"] input:checked');

            if ($conditionSelect.length && $conditionSelect.val()) {
                intent.ad_condition = $conditionSelect.val();
            } else if ($conditionRadio.length && $conditionRadio.val()) {
                intent.ad_condition = $conditionRadio.val();
            } else if ($conditionPretty.length && $conditionPretty.val()) {
                intent.ad_condition = $conditionPretty.val();
            }

            // Warranty (taxonomy: ad_warranty)
            var $warrantySelect = $('select[name="ad_warranty"]');
            var $warrantyRadio = $('input[name="ad_warranty"]:checked');
            var $warrantyPretty = $('.ad-warranty-field input:checked, [data-field="warranty"] input:checked');

            if ($warrantySelect.length && $warrantySelect.val()) {
                intent.ad_warranty = $warrantySelect.val();
            } else if ($warrantyRadio.length && $warrantyRadio.val()) {
                intent.ad_warranty = $warrantyRadio.val();
            } else if ($warrantyPretty.length && $warrantyPretty.val()) {
                intent.ad_warranty = $warrantyPretty.val();
            }

            return intent;
        },

        /**
         * Generate AI suggestions via AJAX
         *
         * Uses direct DOM queries to ensure elements are found
         * regardless of cacheElements() timing.
         */
        generateSuggestions: function() {
            var self = this;

            aiDebugLog('[AI] generateSuggestions() called, isLoading:', this.state.isLoading);

            // Prevent double submission
            if (this.state.isLoading) {
                aiDebugLog('[AI] Blocked: already loading');
                return;
            }

            // Query elements directly for reliability
            var $categorySelect = $('#ad_post_category_select');
            var $titleField = $('#ad_title');
            var $descriptionField = $('#ad_description');

            // Validate category selection
            var categoryId = $categorySelect.val();
            aiDebugLog('[AI] Category:', categoryId);

            if (!categoryId) {
                aiDebugLog('[AI] Blocked: no category');
                this.showNotification(this.config.strings.selectCategory, 'warning');
                return;
            }

            // Get subcategory
            var subcategoryId = this.getDeepestSubcategoryId();

            // Check if fields have content and confirm overwrite
            var titleValue = $titleField.val();
            var descValue = $descriptionField.val();

            if ((titleValue && titleValue.trim() !== '') || (descValue && descValue.trim() !== '')) {
                if (!confirm(this.config.strings.confirmOverwrite)) {
                    return;
                }
            }

            // Collect custom fields
            var customFields = this.collectCustomFields();

            // Collect intent fields (Ad Type, Condition, Warranty)
            // These are critical for AI to generate appropriate language
            var intentFields = this.collectIntentFields();

            aiDebugLog('[AI] Making AJAX request...');

            // Set loading state
            this.setLoadingState(true);

            // Make AJAX request
            // Note: Intent fields are sent separately for clarity and proper processing
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'adforest_get_ai_suggestions',
                    security: this.config.nonce,
                    category_id: categoryId,
                    subcategory_id: subcategoryId || 0,
                    custom_fields: customFields,
                    ad_type: intentFields.ad_type || '',
                    ad_condition: intentFields.ad_condition || '',
                    ad_warranty: intentFields.ad_warranty || ''
                },
                success: function(response) {
                    self.setLoadingState(false);

                    if (response.success && response.data) {
                        aiDebugLog('[AI] Success:', response.data);
                        self.applySuggestions(response.data);
                        self.showNotification(self.config.strings.success, 'success');
                        self.state.suggestionsGenerated = true;
                    } else {
                        var errorMsg = response.data && response.data.message
                            ? response.data.message
                            : self.config.strings.error;
                        aiDebugLog('[AI] Error response:', errorMsg);
                        self.showNotification(errorMsg, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    self.setLoadingState(false);
                    self.showNotification(self.config.strings.error, 'error');
                    // Keep console.error for actual errors - acceptable in production
                    if (typeof console !== 'undefined' && console.error) {
                        console.error('AI Suggestions AJAX Error:', error);
                    }
                }
            });
        },

        /**
         * Apply AI suggestions to form fields
         *
         * Uses direct DOM queries for reliability.
         */
        applySuggestions: function(data) {
            var self = this;

            // Query elements directly
            var $titleField = $('#ad_title');
            var $descriptionField = $('#ad_description');

            // Apply title
            if (data.title && $titleField.length) {
                $titleField.val(data.title);
                this.highlightField($titleField);

                // Trigger validation
                $titleField.trigger('input').trigger('change');
            }

            // Apply description
            if (data.description && $descriptionField.length) {
                var descriptionSet = false;

                // Method 1: Check for jqte editor (jQuery Text Editor - used by AdForest)
                var $jqteEditor = $descriptionField.closest('.jqte').find('.jqte_editor');
                if ($jqteEditor.length) {
                    // Convert newlines to <br> for HTML editor
                    var htmlContent = data.description.replace(/\n/g, '<br>');
                    $jqteEditor.html(htmlContent);
                    // Also update the hidden textarea
                    $descriptionField.val(data.description);
                    descriptionSet = true;
                }

                // Method 2: Check for TinyMCE editor
                if (!descriptionSet && typeof tinymce !== 'undefined' && tinymce.get('ad_description')) {
                    var htmlContent = data.description.replace(/\n/g, '<br>');
                    tinymce.get('ad_description').setContent(htmlContent);
                    descriptionSet = true;
                }

                // Method 3: Fallback to plain textarea
                if (!descriptionSet) {
                    $descriptionField.val(data.description);
                }

                // Highlight the editor container
                var $editorContainer = $descriptionField.closest('.jqte, .ad_description_box_ad_post');
                if ($editorContainer.length) {
                    this.highlightField($editorContainer);
                } else {
                    this.highlightField($descriptionField);
                }

                // Trigger validation
                $descriptionField.trigger('input').trigger('change');
            }

            // Scroll to title field to show results
            if ($titleField.length) {
                $('html, body').animate({
                    scrollTop: $titleField.offset().top - 100
                }, 500);
            }
        },

        /**
         * Highlight a field to show it was updated
         */
        highlightField: function($field) {
            $field.addClass('ai-field-highlight');
            setTimeout(function() {
                $field.removeClass('ai-field-highlight');
            }, 1000);
        },

        /**
         * Set loading state
         *
         * Uses direct DOM queries to ensure button is found even if
         * cacheElements() hasn't run yet (defensive programming).
         */
        setLoadingState: function(isLoading) {
            this.state.isLoading = isLoading;

            // Query directly to ensure we find the button
            var $button = $('#adforest-get-ai-suggestions');
            var $loading = $('#ai-suggestions-loading');

            if ($button.length) {
                if (isLoading) {
                    $button
                        .prop('disabled', true)
                        .html('<i class="fa fa-spinner fa-spin"></i> ' + this.config.strings.buttonLoading);

                    if ($loading.length) {
                        $loading.show();
                    }
                } else {
                    $button
                        .prop('disabled', false)
                        .html('<i class="fa fa-magic"></i> ' + this.config.strings.buttonText);

                    if ($loading.length) {
                        $loading.hide();
                    }
                }
            }
        },

        /**
         * Show notification to user
         */
        showNotification: function(message, type) {
            // Use toastr if available
            if (typeof toastr !== 'undefined') {
                switch (type) {
                    case 'success':
                        toastr.success(message);
                        break;
                    case 'error':
                        toastr.error(message);
                        break;
                    case 'warning':
                        toastr.warning(message);
                        break;
                    default:
                        toastr.info(message);
                }
            } else {
                // Fallback to alert
                alert(message);
            }
        }
    };

    // Initialize the module
    aiDebugLog('[AI] Initializing module...');
    AiSuggestions.init();
    aiDebugLog('[AI] Module initialized');

})(jQuery);
