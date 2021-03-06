<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Hal\Serializer;

use ApiPlatform\Core\Serializer\AbstractCollectionNormalizer;
use ApiPlatform\Core\Util\IriHelper;
use Hateoas\Factory\LinksFactory;

/**
 * Normalizes collections in the HAL format.
 *
 * @author Kevin Dunglas <dunglas@gmail.com>
 * @author Hamza Amrouche <hamza@les-tilleuls.coop>
 */
final class CollectionNormalizer extends AbstractCollectionNormalizer
{
    const FORMAT = 'jsonhal';

    private $linkFactory;

    /**
     * {@inheritdoc}
     */
    protected function getPaginationData($object, array $context = []): array
    {
        list($paginator, $paginated, $currentPage, $itemsPerPage, $lastPage, $pageTotalItems, $totalItems) = $this->getPaginationConfig($object, $context);
        $parsed = IriHelper::parseIri($context['request_uri'] ?? '/', $this->pageParameterName);

        $data = [
            '_links' => [
                'self' => ['href' => IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $paginated ? $currentPage : null)],
            ],
        ];

        if ($paginated) {
            if (null !== $lastPage) {
                $data['_links']['first']['href'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, 1.);
                $data['_links']['last']['href'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $lastPage);
            }

            if (1. !== $currentPage) {
                $data['_links']['prev']['href'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $currentPage - 1.);
            }

            if ((null !== $lastPage && $currentPage !== $lastPage) || (null === $lastPage && $pageTotalItems >= $itemsPerPage)) {
                $data['_links']['next']['href'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $currentPage + 1.);
            }
        }

        if (null !== $totalItems) {
            $data['totalItems'] = $totalItems;
        }

        if ($paginator) {
            $data['itemsPerPage'] = (int) $itemsPerPage;
        }

        $links = ItemNormalizer::serializeLinks($this->linksFactory->create($object, $context));

        return empty($links)? $data:  array_merge_recursive($data, ['_links' => $links]);
    }

    public function setLinksFactory(LinksFactory $linkFactory)
    {
        $this->linksFactory = $linkFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function getItemsData($object, string $format = null, array $context = []): array
    {
        $data = [];

        foreach ($object as $obj) {
            $item = $this->normalizer->normalize($obj, $format, $context);

            $data['_embedded']['item'][] = $item;
        }

        return $data;
    }
}
