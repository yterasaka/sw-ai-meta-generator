<?php declare(strict_types=1);

namespace AiMetaGenerator\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Psr\Log\LoggerInterface;

class MetaGeneratorService
{
    private OpenAiService $openAiService;
    private EntityRepository $languageRepository;
    private LoggerInterface $logger;
    private string $environment;

    public function __construct(
        OpenAiService    $openAiService,
        EntityRepository $languageRepository,
        LoggerInterface  $logger,
        string          $environment
    )
    {
        $this->openAiService = $openAiService;
        $this->languageRepository = $languageRepository;
        $this->logger = $logger;
        $this->environment = $environment;
    }

    public function generateMetadataFromData(string $productName, string $description, Context $context): array
    {
        $languageId = $context->getLanguageId();
        $locale = $this->getLocaleFromLanguageId($languageId, $context);

        if ($this->environment === 'dev') {
            $this->logger->info('AI Meta Generator: Language ID: ' . $languageId . ', Locale: ' . $locale);
        }

        if (!$productName) {
            throw new \RuntimeException('Product name is required for metadata generation');
        }

        $salesChannelId = null;
        $source = $context->getSource();
        if (method_exists($source, 'getSalesChannelId')) {
            $salesChannelId = $source->getSalesChannelId();
        }

        $generatedMetadata = $this->openAiService->generateMetadata(
            $productName,
            $description,
            $locale,
            $salesChannelId
        );

        return [
            'metaTitle' => $generatedMetadata['metaTitle'] ?? $productName,
            'metaDescription' => $generatedMetadata['metaDescription'] ?? $description,
            'keywords' => $generatedMetadata['keywords'] ?? ''
        ];
    }

    private function getLocaleFromLanguageId(string $languageId, Context $context): string
    {
        $criteria = new Criteria([$languageId]);
        $criteria->addAssociation('locale');

        $language = $this->languageRepository->search($criteria, $context)->first();

        if ($language && $language->getLocale()) {
            return $language->getLocale()->getCode();
        }

        return 'en-GB';
    }
}