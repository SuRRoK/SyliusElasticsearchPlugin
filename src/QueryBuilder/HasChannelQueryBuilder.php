<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.shop and write us
 * an email on mikolaj.krol@bitbag.pl.
 */

declare(strict_types=1);

namespace BitBag\SyliusElasticsearchPlugin\QueryBuilder;

use Elastica\Query\AbstractQuery;
use Elastica\Query\Terms;
use Sylius\Component\Channel\Context\ChannelContextInterface;

final class HasChannelQueryBuilder implements QueryBuilderInterface
{
    /** @var ChannelContextInterface */
    private $channelContext;

    /** @var string */
    private $channelsProperty;

    public function __construct(ChannelContextInterface $channelContext, string $channelsProperty)
    {
        $this->channelContext = $channelContext;
        $this->channelsProperty = $channelsProperty;
    }

    public function buildQuery(array $data): ?AbstractQuery
    {
        return new Terms($this->channelsProperty, [strtolower($this->channelContext->getChannel()->getCode())]);
    }
}
