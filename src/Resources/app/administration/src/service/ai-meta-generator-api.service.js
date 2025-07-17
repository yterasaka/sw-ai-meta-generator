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
        console.log("HTTP request successful:", response);
        return response.data;
      })
      .catch((error) => {
        console.error("HTTP request failed:", error);
        console.error("Error response:", error.response);
        console.error("Error status:", error.response?.status);
        console.error("Error data:", error.response?.data);
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
}

Application.addServiceProvider("aiMetaGeneratorApiService", (container) => {
  const initContainer = Application.getContainer("init");

  return new AiMetaGeneratorApiService(
    initContainer.httpClient,
    container.loginService
  );
});
