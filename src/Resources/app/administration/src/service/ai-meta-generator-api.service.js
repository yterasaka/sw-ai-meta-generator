const { Application } = Shopware;

class AiMetaGeneratorApiService {
  constructor(httpClient, loginService) {
    this.httpClient = httpClient;
    this.loginService = loginService;
  }

  generateMetadata(productData) {
    const headers = this.getBasicHeaders();

    return this.httpClient
      .post("_action/ai-meta-generator/generate", productData, { headers })
      .then((response) => {
        return response.data;
      })
      .catch((error) => {
        throw error;
      });
  }

  getBasicHeaders() {
    return {
      Accept: "application/vnd.api+json",
      Authorization: `Bearer ${this.loginService.getToken()}`,
      "Content-Type": "application/json",
    };
  }

  testConnection(data) {
    const headers = this.getBasicHeaders();

    return this.httpClient
      .post("_action/ai-meta-generator/test-connection", data, { headers })
      .then((response) => {
        return response.data;
      })
      .catch((error) => {
        throw error;
      });
  }
}

Application.addServiceProvider("aiMetaGeneratorApiService", (container) => {
  const initContainer = Application.getContainer("init");

  return new AiMetaGeneratorApiService(
    initContainer.httpClient,
    container.loginService
  );
});
