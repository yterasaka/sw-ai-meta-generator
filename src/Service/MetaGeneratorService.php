<?php declare(strict_types=1);

namespace AiMetaGenerator\Service;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class MetaGeneratorService
{
    private OpenAiService $openAiService;
    private EntityRepository $productRepository;
    private EntityRepository $languageRepository;

    public function __construct(
        OpenAiService    $openAiService,
        EntityRepository $productRepository,
        EntityRepository $languageRepository
    )
    {
        $this->openAiService = $openAiService;
        $this->productRepository = $productRepository;
        $this->languageRepository = $languageRepository;
    }

    public function generateMetadataForProduct(string $productId, Context $context): array
    {
        $product = $this->getProduct($productId, $context);

        if (!$product) {
            throw new \RuntimeException('Product not found');
        }

        $languageId = $context->getLanguageId();
        $locale = $this->getLocaleFromLanguageId($languageId, $context);

        $productName = $product->getTranslated()['name'] ?? $product->getName();
        $description = $product->getTranslated()['description'] ?? $product->getDescription();

        if (!$productName) {
            throw new \RuntimeException('Product name is required for metadata generation');
        }

        return $this->openAiService->generateMetadata(
            $productName,
            $description ?? '',
            $locale,
            $context->getSource()->getSalesChannelId()
        );
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

        return $language?->getLocale()?->getCode() ?? 'en-GB';
    }

    public function generateMetadataFromData(string $productName, string $description, Context $context): array
    {
        $languageId = $context->getLanguageId();
        $locale = $this->getLocaleFromLanguageId($languageId, $context);

        if (!$productName) {
            throw new \RuntimeException('Product name is required for metadata generation');
        }

        $salesChannelId = null;
        $source = $context->getSource();
        if (method_exists($source, 'getSalesChannelId')) {
            $salesChannelId = $source->getSalesChannelId();
        }

        return $this->openAiService->generateMetadata(
            $productName,
            $description,
            $locale,
            $salesChannelId
        );
    }
}