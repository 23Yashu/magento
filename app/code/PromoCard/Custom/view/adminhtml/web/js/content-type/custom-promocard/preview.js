define([
    "Magento_PageBuilder/js/content-type/preview",
    "Magento_PageBuilder/js/content-type-toolbar",
    "Magento_PageBuilder/js/events",
    "Magento_PageBuilder/js/content-type-menu/hide-show-option",
    "Magento_PageBuilder/js/uploader",
    "Magento_PageBuilder/js/wysiwyg/factory",
    "Magento_PageBuilder/js/config"
], function(
    PreviewBase,
    Toolbar,
    events,
    hideShowOption,
    Uploader,
    WysiwygFactory,
    Config
) {
    "use strict";

    function Preview(parent, config, stageId) {
        PreviewBase.call(this, parent, config, stageId);
        this.toolbar = new Toolbar(this, this.getToolbarOptions());
    }

    var $super = PreviewBase.prototype;
    Preview.prototype = Object.create(PreviewBase.prototype);

    /**
     * Return items for promo card
     */
    Preview.prototype.getItems = function() {
        return this.contentType.dataStore.get('items') || [];
    };

    Preview.prototype.bindEvents = function() {
        PreviewBase.prototype.bindEvents.call(this);
    };

    Preview.prototype.getUploader = function() {
        var initialImageValue = this.contentType.dataStore.get(
            this.config.additional_data.uploaderConfig.dataScope,
            ""
        );

        return new Uploader(
            "imageuploader_" + this.contentType.id,
            this.config.additional_data.uploaderConfig,
            this.contentType.id,
            this.contentType.dataStore,
            initialImageValue
        );
    };

    Preview.prototype.initWysiwyg = function(element) {
        var self = this;
        var config = this.config.additional_data.wysiwygConfig.wysiwygConfigData;

        this.element = element;
        element.id = this.contentType.id + "-editor";

        config.adapter.settings.fixed_toolbar_container =
            "#" + this.contentType.id + " .promocard-description-text-content";

        WysiwygFactory(
            this.contentType.id,
            element.id,
            this.config.name,
            config,
            this.contentType.dataStore,
            "description",
            this.contentType.stageId
        ).then(function(wysiwyg) {
            self.wysiwyg = wysiwyg;
        });
    };

    Preview.prototype.retrieveOptions = function() {
        var options = $super.retrieveOptions.call(this, arguments);

        options.hideShow = new hideShowOption({
            preview: this,
            icon: hideShowOption.showIcon,
            title: hideShowOption.showText,
            action: this.onOptionVisibilityToggle,
            classes: ["hide-show-content-type"],
            sort: 40
        });

        return options;
    };

    Preview.prototype.getToolbarOptions = function() {
        return [
            {
                key: "text_align",
                type: "select",
                values: [
                    { value: "left", label: "Left", icon: "icon-pagebuilder-align-left" },
                    { value: "center", label: "Center", icon: "icon-pagebuilder-align-center" },
                    { value: "right", label: "Right", icon: "icon-pagebuilder-align-right" }
                ]
            },
            {
                key: "cta-style",
                type: "select",
                values: [
                    { value: "primary", label: "Primary Button", icon: "icon-pagebuilder-button" },
                    { value: "secondary", label: "Secondary Button", icon: "icon-pagebuilder-button" },
                    { value: "link", label: "Link Style", icon: "icon-pagebuilder-link" }
                ]
            }
        ];
    };

    return Preview;
});