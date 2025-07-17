import template from "./sw-product-seo-form.html.twig";

const { Component, Mixin } = Shopware;

Component.override("sw-product-seo-form", {
  template,

  inject: ["aiMetaGeneratorApiService"],

  mixins: [Mixin.getByName("notification")],

  data() {
    return {
      isGenerating: false,
    };
  },

  methods: {
    async onGenerateMetadata() {
      if (!this.product) {
        this.createNotificationError({
          message: this.$tc("sw-product.seoForm.errorProductNotFound"),
        });
        return;
      }

      this.isGenerating = true;
      console.log("Starting metadata generation...");

      try {
        const currentLanguageId = Shopware.Context.api.languageId;

        const requestData = {
          productId: this.product.id,
          productName: this.product.name,
          description: this.product.description,
          languageId: currentLanguageId,
        };

        console.log("Calling API service with data:", requestData);

        const response = await this.aiMetaGeneratorApiService.generateMetadata(
          requestData
        );

        console.log("API service response:", response);

        if (response.success) {
          this.updateProductMetadata(response.data);
          this.createNotificationSuccess({
            message: this.$tc("sw-product.seoForm.metadataGeneratedSuccess"),
          });
        } else {
          throw new Error(response.error || "Unknown error occurred");
        }
      } catch (error) {
        console.error("Error generating metadata:", error);
        this.createNotificationError({
          message:
            this.$tc("sw-product.seoForm.errorGeneratingMetadata") +
            ": " +
            error.message,
        });
      } finally {
        this.isGenerating = false;
        console.log("Metadata generation completed");
      }
    },

    updateProductMetadata(metadata) {
      if (!this.product) {
        console.error("Product not found");
        return;
      }

      if (metadata.metaTitle) {
        this.$set(this.product, "metaTitle", metadata.metaTitle);
      }

      if (metadata.metaDescription) {
        this.$set(this.product, "metaDescription", metadata.metaDescription);
      }

      if (metadata.keywords) {
        this.$set(this.product, "keywords", metadata.keywords);
      }

      this.$emit("product-changed");

      console.log("Product metadata updated:", {
        metaTitle: this.product.metaTitle,
        metaDescription: this.product.metaDescription,
        keywords: this.product.keywords,
      });
    },
  },
});
