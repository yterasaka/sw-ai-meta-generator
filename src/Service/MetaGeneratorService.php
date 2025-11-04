<?php declare(strict_types=1);

namespace AiMetaGenerator\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class MetaGeneratorService
{
    private OpenAiService $openAiService;
    private EntityRepository $languageRepository;

    public function __construct(
        OpenAiService    $openAiService,
        EntityRepository $languageRepository,
    )
    {
        $this->openAiService = $openAiService;
        $this->languageRepository = $languageRepository;
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
        if ($source instanceof SalesChannelContextSource) {
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