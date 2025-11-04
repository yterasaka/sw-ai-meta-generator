import template from "./ai-meta-generator-test-button.html.twig";

const { Component, Mixin } = Shopware;

Component.register("ai-meta-generator-test-button", {
  template,

  mixins: [Mixin.getByName("notification")],

  inject: ["systemConfigApiService", "aiMetaGeneratorApiService"],

  data() {
    return {
      testing: false,
      hasApiKey: false,
    };
  },

  async created() {
    await this.checkApiKey();
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

    async testConnection() {
      this.testing = true;

      try {
        const config = await this.systemConfigApiService.getValues(
          "AiMetaGenerator.config"
        );
        const apiKey = config["AiMetaGenerator.config.openaiApiKey"];

        if (!apiKey) {
          this.createNotificationError({
            message: this.$tc("ai-meta-generator.settings.apiKeyRequired"),
          });
          return;
        }

        const response = await this.aiMetaGeneratorApiService.testConnection({
          apiKey: apiKey,
        });

        if (response.success) {
          this.createNotificationSuccess({
            message: this.$tc("ai-meta-generator.settings.testSuccessful"),
          });
        } else {
          const errorMessage = this.getErrorMessage(response.statusCode);
          this.createNotificationError({
            message:
              this.$tc("ai-meta-generator.settings.testFailed") +
              ": " +
              errorMessage,
          });
        }
      } catch (error) {
        this.createNotificationError({
          message:
            this.$tc("ai-meta-generator.settings.testFailed") +
            ": " +
            this.$tc("ai-meta-generator.settings.connectionError"),
        });
      } finally {
        this.testing = false;
      }
    },

    getErrorMessage(statusCode) {
      switch (statusCode) {
        case 401:
          return this.$tc("ai-meta-generator.settings.invalidApiKey");
        case 429:
          return this.$tc("ai-meta-generator.settings.quotaExceeded");
        case 0:
          return this.$tc("ai-meta-generator.settings.connectionError");
        default:
          return this.$tc("ai-meta-generator.settings.unexpectedError", {
            statusCode,
          });
      }
    },
  },
});
