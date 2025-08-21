<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
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
use WeakReference;

use function React\Promise\reject;

/**
 * Used only to search messages sent in a guild.
 *
 * All bots with the message content intent can use it, but it's not considered stable yet, and Discord might make changes or remove bot access if necessary.
 *
 * @see Message
 * @see \Discord\Parts\Channel\Channel
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
    * @param array $queryparams Query string params to add to the request (no validation).
    *                           Supported parameters:
    *                             - sort_by: Sorting mode. See SortingMode schema.
    *                             - sort_order: Sorting order. See SortingOrder schema.
    *                             - content: Message content to search for (string, max 1024 chars).
    *                             - slop: Integer, minimum 0, maximum 100.
    *                             - contents: Array of message contents to search for (string|null, max 1024 chars each, up to 100 items).
    *                             - author_id: Author ID (SnowflakeType|null, up to 1521 unique items).
    *                             - author_type: Author type (AuthorType, up to 1521 unique items).
    *                             - mentions: Mentioned user ID (SnowflakeType|null, up to 1521 unique items).
    *                             - mention_everyone: Boolean, whether to include messages mentioning everyone.
    *                             - min_id: Minimum message ID (SnowflakeType).
    *                             - max_id: Maximum message ID (SnowflakeType).
    *                             - limit: Integer, minimum 1, maximum 25.
    *                             - offset: Integer, minimum 0, maximum 9975.
    *                             - cursor: Cursor for pagination (ScoreCursor or TimestampCursor).
    *                             - has: Message feature (HasOption, up to 1521 unique items).
    *                             - link_hostname: Link hostname (string|null, max 152133 chars each, up to 1521 unique items).
    *                             - embed_provider: Embed providers (string|null, max 256 chars each, up to 1521 unique items).
    *                             - embed_type: Embed type (SearchableEmbedType|null, up to 1521 unique items).
    *                             - attachment_extension: Attachment extension (string|null, max 152133 chars each, up to 1521 unique items).
    *                             - attachment_filename: Attachment filename (string, max 1024 chars).
    *                             - pinned: Boolean, whether to include pinned messages.
    *                             - command_id: Command ID (SnowflakeType).
    *                             - command_name: Command name (string, max 32 chars).
    *                             - include_nsfw: Boolean, whether to include NSFW messages.
    *                             - channel_id: Channel IDs (SnowflakeType, up to 500 unique items).
     *
     * @return PromiseInterface<static>
     *
     * @throws \Exception
     */
    public function freshen(array $queryparams = []): PromiseInterface
    {
        $endpoint = new Endpoint($this->endpoints['all']);
        $endpoint->bindAssoc($this->vars);

        foreach ($queryparams as $query => $param) {
            $endpoint->addQuery($query, $param);
        }

        return $this->http->get($endpoint)->then(function ($response) {
            $part = $this->factory->create($this->class, $response, true);
            return $this->cacheFreshen($part);
        });
    }
}
