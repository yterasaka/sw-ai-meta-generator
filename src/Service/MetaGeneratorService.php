<?php declare(strict_types=1);

namespace AiMetaGenerator\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Content\Product\ProductEntity;
use Psr\Log\LoggerInterface;

class MetaGeneratorService
{
    private OpenAiService $openAiService;
    private EntityRepository $productRepository;
    private EntityRepository $languageRepository;
    private LoggerInterface $logger;

    public function __construct(
        OpenAiService    $openAiService,
        EntityRepository $productRepository,
        EntityRepository $languageRepository,
        LoggerInterface  $logger
    )
    {
        $this->openAiService = $openAiService;
        $this->productRepository = $productRepository;
        $this->languageRepository = $languageRepository;
        $this->logger = $logger;
    }

    public function generateMetadataForProduct(string $productId, Context $context): array
    {
        $product = $this->getProduct($productId, $context);

        if (!$product) {
            throw new \RuntimeException('Product not found');
        }

        $productName = $product->getTranslated()['name'] ?? $product->getName();
        $description = $product->getTranslated()['description'] ?? $product->getDescription();

        if (!$productName) {
            throw new \RuntimeException('Product name is required for metadata generation');
        }

        return $this->generateMetadataFromData($productName, $description ?? '', $context);
    }

    public function generateMetadataFromData(string $productName, string $description, Context $context): array
    {
        $languageId = $context->getLanguageId();
        $locale = $this->getLocaleFromLanguageId($languageId, $context);

        if ($_ENV['APP_ENV'] === 'dev') {
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

    private function getProduct(string $productId, Context $context): ?ProductEntity
    {
        $criteria = new Criteria([$productId]);
        $criteria->addAssociation('translations');

        $result = $this->productRepository->search($criteria, $context);
        return $result->first();
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