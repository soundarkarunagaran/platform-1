<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectSubresources;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Initializes sub-resources for all API resources based on API configuration and metadata.
 */
class InitializeSubresources implements ProcessorInterface
{
    /** @var ConfigProvider */
    protected $configProvider;

    /** @var MetadataProvider */
    protected $metadataProvider;

    /**
     * @param ConfigProvider   $configProvider
     * @param MetadataProvider $metadataProvider
     */
    public function __construct(ConfigProvider $configProvider, MetadataProvider $metadataProvider)
    {
        $this->configProvider = $configProvider;
        $this->metadataProvider = $metadataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CollectSubresourcesContext $context */

        $subresources = $context->getResult();
        if (!$subresources->isEmpty()) {
            // already initialized
            return;
        }

        $version = $context->getVersion();
        $requestType = $context->getRequestType();
        $configExtras = $this->getConfigExtras();
        $metadataExtras = $this->getMetadataExtras();

        $resources = $context->getResources();
        foreach ($resources as $resource) {
            $subresources->add(
                $this->createEntitySubresources(
                    $resource,
                    $version,
                    $requestType,
                    $configExtras,
                    $metadataExtras
                )
            );
        }
    }

    /**
     * @return ConfigExtraInterface[]
     */
    protected function getConfigExtras()
    {
        return [new EntityDefinitionConfigExtra()];
    }

    /**
     * @return MetadataExtraInterface[]
     */
    protected function getMetadataExtras()
    {
        return [];
    }

    /**
     * @param ApiResource              $resource
     * @param string                   $version
     * @param RequestType              $requestType
     * @param ConfigExtraInterface[]   $configExtras
     * @param MetadataExtraInterface[] $metadataExtras
     *
     * @return ApiResourceSubresources
     */
    protected function createEntitySubresources(
        ApiResource $resource,
        $version,
        RequestType $requestType,
        array $configExtras,
        array $metadataExtras
    ) {
        $entityClass = $resource->getEntityClass();
        $config = $this->configProvider->getConfig(
            $entityClass,
            $version,
            $requestType,
            $configExtras
        );
        $metadata = $this->metadataProvider->getMetadata(
            $entityClass,
            $version,
            $requestType,
            $metadataExtras,
            $config->getDefinition()
        );

        $resourceExcludedActions = $resource->getExcludedActions();
        $subresourceExcludedActions = !empty($resourceExcludedActions)
            ? $this->getSubresourceExcludedActions($resourceExcludedActions)
            : [];

        $entitySubresources = new ApiResourceSubresources($entityClass);
        $associations = $metadata->getAssociations();
        foreach ($associations as $associationName => $association) {
            $subresource = $entitySubresources->addSubresource($associationName);
            $subresource->setTargetClassName($association->getTargetClassName());
            $subresource->setAcceptableTargetClassNames($association->getAcceptableTargetClassNames());
            $subresource->setIsCollection($association->isCollection());
            if (!$association->isCollection()) {
                $excludedActions = $subresourceExcludedActions;
                if (!in_array(ApiActions::ADD_RELATIONSHIP, $excludedActions, true)) {
                    $excludedActions[] = ApiActions::ADD_RELATIONSHIP;
                }
                if (!in_array(ApiActions::DELETE_RELATIONSHIP, $excludedActions, true)) {
                    $excludedActions[] = ApiActions::DELETE_RELATIONSHIP;
                }
                $subresource->setExcludedActions($excludedActions);
            } elseif (!empty($subresourceExcludedActions)) {
                $subresource->setExcludedActions($subresourceExcludedActions);
            }
        }

        return $entitySubresources;
    }

    /**
     * @param string[] $resourceExcludedActions
     *
     * @return string[]
     */
    protected function getSubresourceExcludedActions(array $resourceExcludedActions)
    {
        $result = array_intersect(
            $resourceExcludedActions,
            [
                ApiActions::GET_SUBRESOURCE,
                ApiActions::GET_RELATIONSHIP,
                ApiActions::UPDATE_RELATIONSHIP,
                ApiActions::ADD_RELATIONSHIP,
                ApiActions::DELETE_RELATIONSHIP
            ]
        );

        if (in_array(ApiActions::UPDATE, $resourceExcludedActions, true)) {
            $result = array_unique(
                array_merge(
                    $result,
                    [
                        ApiActions::UPDATE_RELATIONSHIP,
                        ApiActions::ADD_RELATIONSHIP,
                        ApiActions::DELETE_RELATIONSHIP
                    ]
                )
            );
        }

        return array_values($result);
    }
}
