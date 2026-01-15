<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Guild;

use Discord\Http\Endpoint;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\GuildSearch;
use Discord\Repository\AbstractRepository;
use React\Promise\PromiseInterface;

use function React\Promise\reject;

/**
 * Returns a list of messages without the `reactions` key that match a search query in the guild. Requires the `READ_MESSAGE_HISTORY` permission.
 *
 * If the entity you are searching is not yet indexed, the endpoint will return a 202 accepted response.
 * The response body will not contain any search results, and will look similar to an error response.
 *
 * Due to speed optimizations, search may return slightly less results than the limit specified when messages have not been accessed for a long time.
 * Clients should not rely on the length of the `messages` array to paginate results.
 *
 * Additionally, when messages are actively being created or deleted, the `total_results` field may not be accurate.
 *
 * @see \Discord\Parts\Guild\GuildSearch
 *
 * @since 10.19.0
 *
 * @method Message|null get(string $discrim, $key)
 * @method Message|null pull(string|int $key, $default = null)
 * @method Message|null first()
 * @method Message|null last()
 * @method Message|null find(callable $callback)
 *
 * @return PromiseInterface<static>
 * @throws \Exception
 */
class MessageRepository extends AbstractRepository
{
    /**
     * The collection discriminator.
     *
     * @var string Discriminator.
     */
    protected $discrim = 'analytics_id';

    /**
     * @inheritDoc
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_MESSAGES_SEARCH,
    ];

    /**
     * @inheritDoc
     */
    protected $class = GuildSearch::class;

    /**
     * Freshens the repository cache.
     *
     * @param array  $queryparams                         Query string params to add to the request (no validation).
     * @param int    $queryparams['limit']                Integer, minimum 1, maximum 25 (default 25).
     * @param int    $queryparams['offset']               Integer, minimum 0, maximum 9975.
     * @param string $queryparams['max_id']               Maximum message ID (snowflake).
     * @param string $queryparams['min_id']               Minimum message ID (snowflake).
     * @param int    $queryparams['slop']                 Integer, minimum 0, maximum 100 (default 2).
     * @param string $queryparams['content']              Message content to search for (string, max 1024 chars).
     * @param array  $queryparams['channel_id']           Array of snowflakes, max 500 unique items.
     * @param array  $queryparams['author_type']          Array of strings, filter by author type.
     * @param array  $queryparams['author_id']            Array of snowflakes, filter by authors.
     * @param array  $queryparams['mentions']             Array of snowflakes, filter messages that mention these users.
     * @param bool   $queryparams['mention_everyone']     Boolean, filter messages that do or do not mention @everyone.
     * @param bool   $queryparams['pinned']               Boolean, filter messages by whether they are or are not pinned.
     * @param array  $queryparams['has']                  Array of strings, filter messages by whether they have specific things.
     * @param array  $queryparams['embed_type']           Array of strings, filter messages by embed type.
     * @param array  $queryparams['embed_provider']       Array of strings, filter by embed provider (case-sensitive, max 256 chars).
     * @param array  $queryparams['link_hostname']        Array of strings, filter by link hostname (case-sensitive).
     * @param array  $queryparams['attachment_filename']  Array of strings, filter by attachment filename (max 1024 chars).
     * @param array  $queryparams['attachment_extension'] Array of strings, filter by attachment extension.
     * @param string $queryparams['sort_by']              String, the sorting algorithm to use.
     * @param string $queryparams['sort_order']           String, the sort direction (asc or desc, default desc).
     * @param bool   $queryparams['include_nsfw']         Boolean, whether to include results from NSFW channels (default false).
     *
     * @return PromiseInterface<static>
     *
     * @throws \Exception
     */
    public function freshen(array $queryparams = []): PromiseInterface
    {
        if (empty($queryparams)) {
            return reject(new \InvalidArgumentException('Query parameters are required.'));
        }

        $endpoint = new Endpoint($this->endpoints['all']);
        $endpoint->bindAssoc($this->vars);

        foreach ($queryparams as $query => $param) {
            $endpoint->addQuery($query, $param);
        }

        return $this->http->get($endpoint)->then(function ($response) {
            $part = $this->factory->part($this->class, (array) $response, true);
            $promise = $this->cacheFreshen($part);
            $this->discord->emit('GuildSearch', [$part]);

            return $promise;
        });
    }

    /**
     * @param object $response
     *
     * @return PromiseInterface<static>
     */
    protected function cacheFreshen($response): PromiseInterface
    {
        return $this->cache->set($response->{$this->discrim}, $response)->then(fn ($success) => $this);
    }
}
