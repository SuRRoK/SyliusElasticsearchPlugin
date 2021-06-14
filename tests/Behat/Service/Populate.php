<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.shop and write us
 * an email on mikolaj.krol@bitbag.pl.
 */

declare(strict_types=1);

namespace Tests\BitBag\SyliusElasticsearchPlugin\Behat\Service;

use Elastica\Exception\Bulk\ResponseException as BulkResponseException;
use FOS\ElasticaBundle\Command\ProgressClosureBuilder;
use FOS\ElasticaBundle\Event\PostIndexPopulateEvent;
use FOS\ElasticaBundle\Event\PreIndexPopulateEvent;
use FOS\ElasticaBundle\Index\IndexManager;
use FOS\ElasticaBundle\Index\Resetter;
use FOS\ElasticaBundle\Persister\Event\OnExceptionEvent;
use FOS\ElasticaBundle\Persister\Event\PostAsyncInsertObjectsEvent;
use FOS\ElasticaBundle\Persister\Event\PostInsertObjectsEvent;
use FOS\ElasticaBundle\Persister\PagerPersisterInterface;
use FOS\ElasticaBundle\Persister\PagerPersisterRegistry;
use FOS\ElasticaBundle\Provider\PagerProviderRegistry;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class Populate
{
    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var IndexManager */
    private $indexManager;

    /** @var PagerProviderRegistry */
    private $pagerProviderRegistry;

    /** @var PagerPersisterRegistry */
    private $pagerPersisterRegistry;

    /** @var PagerPersisterInterface */
    private $pagerPersister;

    /** @var Resetter */
    private $resetter;

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param IndexManager $indexManager
     * @param PagerProviderRegistry $pagerProviderRegistry
     * @param PagerPersisterRegistry $pagerPersisterRegistry
     * @param Resetter $resetter
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        IndexManager $indexManager,
        PagerProviderRegistry $pagerProviderRegistry,
        PagerPersisterRegistry $pagerPersisterRegistry,
        Resetter $resetter
    ) {
        $this->dispatcher = $dispatcher;
        $this->indexManager = $indexManager;
        $this->pagerProviderRegistry = $pagerProviderRegistry;
        $this->pagerPersisterRegistry = $pagerPersisterRegistry;
        $this->resetter = $resetter;
    }

    public function populateIndex(): void
    {
        $this->pagerPersister = $this->pagerPersisterRegistry->getPagerPersister('in_place');

        $indexes = array_keys($this->indexManager->getAllIndexes());

        $options = [
            'delete' => true,
            'reset' => true,
        ];

        foreach ($indexes as $index) {
            $this->dispatcher->dispatch($event = new PreIndexPopulateEvent($index, $options['reset'], $options));

            if ($reset = $event->isReset()) {
                $this->resetter->resetIndex($index, true);
            }

            $provider = $this->pagerProviderRegistry->getProvider($index);
            $pager = $provider->provide($options);

            $this->pagerPersister->insert($pager, array_merge($options, ['indexName' => $index]));

            $this->dispatcher->dispatch(new PostIndexPopulateEvent($index, $reset, $options));

            $this->refreshIndex($index);
        }
    }

    private function refreshIndex(string $index): void
    {
        $this->indexManager->getIndex($index)->refresh();
    }
}
