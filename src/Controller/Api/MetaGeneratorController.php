<?php declare(strict_types=1);

namespace AiMetaGenerator\Controller\Api;

use AiMetaGenerator\Service\MetaGeneratorService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\ApiRouteScope;
use Shopware\Core\PlatformRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

#[Route(defaults: [PlatformRequest::ATTRIBUTE_ROUTE_SCOPE => [ApiRouteScope::ID]])]
class MetaGeneratorController extends AbstractController
{
    private MetaGeneratorService $metaGeneratorService;
    private LoggerInterface $logger;

    public function __construct(
        MetaGeneratorService $metaGeneratorService,
        LoggerInterface $logger
    ) {
        $this->metaGeneratorService = $metaGeneratorService;
        $this->logger = $logger;
    }

    #[Route(path: '/api/_action/ai-meta-generator/generate', name: 'api.action.ai-meta-generator.generate', methods: ['POST'])]
    public function generateMetadata(Request $request, Context $context): JsonResponse
    {
        
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Invalid JSON data'
            ]);
        }
        
        $productId = $data['productId'] ?? null;
        $productName = $data['productName'] ?? null;
        $description = $data['description'] ?? '';
        $requestLanguageId = $data['languageId'] ?? null;
        
        if (!$productId || !$productName) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Product ID and name are required' 
            ]); 
        }
        
        if ($requestLanguageId && $requestLanguageId !== $context->getLanguageId()) {
            $context = new Context(
                $context->getSource(),
                $context->getRuleIds(),
                $context->getCurrencyId(),
                [$requestLanguageId],
                $context->getVersionId(),
                $context->getCurrencyFactor(),
                $context->considerInheritance(),
                $context->getTaxState(),
                $context->getRounding()
            );
        }

        try {
            $metadata = $this->metaGeneratorService->generateMetadataFromData(
                $productName,
                $description,
                $context
            );

            return new JsonResponse([
                'success' => true,
                'data' => $metadata
            ]);

        } catch (\RuntimeException $e) {
            $this->logger->error('Metadata generation failed', ['error' => $e->getMessage()]);
            
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error in metadata generation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'error' => 'An unexpected error occurred'
            ]);
        }
    }
}