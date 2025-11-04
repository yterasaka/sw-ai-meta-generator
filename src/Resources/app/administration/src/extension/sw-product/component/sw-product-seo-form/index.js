import template from "./sw-product-seo-form.html.twig";

const { Component, Mixin } = Shopware;

Component.override("sw-product-seo-form", {
  template,

  inject: ["aiMetaGeneratorApiService", "systemConfigApiService"],

  mixins: [Mixin.getByName("notification")],

  data() {
    return {
      isGenerating: false,
      hasApiKey: false,
    };
  },

  async created() {
    await this.checkApiKey();
  },

  computed: {
    canGenerateMetadata() {
      return (
        this.hasApiKey &&
        this.product &&
        this.product.name &&
        this.product.name.trim() !== "" &&
        this.product.description &&
        this.product.description.trim() !== ""
      );
    },
  },

  methods: {
    async checkApiKey() {
      try {
        const config = await this.systemConfigApiService.getValues(
          "AiMetaGenerator.config"
        );
        const apiKey = config["AiMetaGenerator.config.openaiApiKey"];
        this.hasApiKey = apiKey && apiKey.trim() !== "";
      } catch (error) {
        this.hasApiKey = false;
      }
    },

    async onGenerateMetadata() {
      if (!this.product) {
        this.createNotificationError({
          message: this.$tc("sw-product.seoForm.errorProductNotFound"),
        });
        return;
      }

      if (!this.canGenerateMetadata) {
        this.createNotificationError({
          message: this.$tc("sw-product.seoForm.errorMissingRequiredFields"),
        });
        return;
      }

      if (!this.hasApiKey) {
        this.createNotificationError({
          message: this.$tc("sw-product.seoForm.errorApiKeyNotConfigured"),
        });
        return;
      }

      this.isGenerating = true;

      try {
        const currentLanguageId = Shopware.Context.api.languageId;

        const requestData = {
          productId: this.product.id,
          productName: this.product.name,
          description: this.product.description,
          languageId: currentLanguageId,
        };

        const response = await this.aiMetaGeneratorApiService.generateMetadata(
          requestData
        );

        if (response.success) {
          this.updateProductMetadata(response.data);
          this.createNotificationSuccess({
            message: this.$tc("sw-product.seoForm.metadataGeneratedSuccess"),
          });
        } else {
          throw new Error(response.error || "Unknown error occurred");
        }
      } catch (error) {
        let errorMessage = error.message;

        if (
          error.response &&
          error.response.data &&
          error.response.data.error
        ) {
          errorMessage = error.response.data.error;
        }

        this.createNotificationError({
          message:
            this.$tc("sw-product.seoForm.errorGeneratingMetadata") +
            ": " +
            errorMessage,
        });
      } finally {
        this.isGenerating = false;
      }
    },

    updateProductMetadata(metadata) {
      if (!this.product) {
        console.error("Product not found");
        return;
      }

      if (metadata.metaTitle) {
        this.product.metaTitle = metadata.metaTitle;
      }

      if (metadata.metaDescription) {
        this.product.metaDescription = metadata.metaDescription;
      }

      if (metadata.keywords) {
        this.product.keywords = metadata.keywords;
      }

      this.$emit("product-changed");
    },
  },
});
