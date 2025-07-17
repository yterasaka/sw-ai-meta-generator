import template from "./sw-product-seo-form.html.twig";

const { Component } = Shopware;

Component.override("sw-product-seo-form", {
  template,

  methods: {
    onGenerateMetadata() {
      console.log("Generate AI metadata clicked");
      alert("AI metadata generation functionality will be implemented here");
    },
  },
});
