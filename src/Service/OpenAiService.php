<?php declare(strict_types=1);

namespace AiMetaGenerator\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class OpenAiService
{
    private Client $httpClient;
    private SystemConfigService $systemConfigService;
    private LoggerInterface $logger;

    public function __construct(
        Client              $httpClient,
        SystemConfigService $systemConfigService,
        LoggerInterface     $logger
    )
    {
        $this->httpClient = $httpClient;
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
    }

    public function generateMetadata(string $productName, string $description, string $locale, ?string $salesChannelId = null): array
    {
        $apiKey = $this->systemConfigService->get('AiMetaGenerator.config.openaiApiKey', $salesChannelId);

        if (!$apiKey) {
            throw new \RuntimeException('OpenAI API key is not configured');
        }

        $prompt = $this->buildPrompt($productName, $description, $locale);

        try {
            $response = $this->httpClient->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4.1-mini',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an SEO expert. Generate meta title and meta description for e-commerce products.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'max_tokens' => 500,
                    'temperature' => 0.7,
                ]
            ]);

            $responseBody = $response->getBody()->getContents();
            $data = json_decode($responseBody, true);

            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Failed to decode OpenAI API response', [
                    'json_error' => json_last_error_msg(),
                    'response_body' => $responseBody
                ]);
                throw new \RuntimeException('Invalid JSON response from OpenAI API: ' . json_last_error_msg());
            }

            if (isset($data['error'])) {
                $errorMessage = $data['error']['message'] ?? 'Unknown API error';
                $this->logger->error('OpenAI API returned error', ['error' => $data['error']]);
                throw new \RuntimeException('OpenAI API error: ' . $errorMessage);
            }

            return $this->parseResponse($data);

        } catch (GuzzleException $e) {
            $this->logger->error('OpenAI API request failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to generate metadata: ' . $e->getMessage());
        }
    }

    private function buildPrompt(string $productName, string $description, string $locale): string
    {
        $prompt = sprintf(
            "Generate SEO-optimized meta title, meta description, and keywords for this product in the language for locale '%s':\n\n" .
            "Product Name: %s\n" .
            "Description: %s\n\n" .
            "Requirements:\n" .
            "- Meta title: 50-60 characters, include main keywords\n" .
            "- Meta description: 150-160 characters, compelling and descriptive\n" .
            "- Keywords: 3-5 relevant SEO keywords separated by commas\n" .
            "- Use natural, native language appropriate for locale %s\n" .
            "- Follow SEO best practices for the target market\n\n" .
            "Return the result in JSON format:\n" .
            '{"metaTitle": "...", "metaDescription": "...", "keywords": "..."}',
            $locale,
            $productName,
            strip_tags($description),
            $locale
        );
        
        return $prompt;
    }

    private function parseResponse(array $data): array
    {
        $content = $data['choices'][0]['message']['content'] ?? '';
        $parsed = json_decode($content, true);

        if (!$parsed || !isset($parsed['metaTitle']) || !isset($parsed['metaDescription'])) {
            throw new \RuntimeException('Invalid response format from OpenAI API');
        }

        return [
            'metaTitle' => $parsed['metaTitle'],
            'metaDescription' => $parsed['metaDescription'],
            'keywords' => $parsed['keywords'] ?? ''
        ];
    }
}