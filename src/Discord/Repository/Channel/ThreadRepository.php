<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Channel;

use Discord\Helpers\Collection;
use Discord\Http\Endpoint;
use Discord\Parts\Thread\Thread;
use Discord\Repository\AbstractRepository;
use React\Promise\ExtendedPromiseInterface;

use function React\Promise\resolve;

/**
 * Contains threads on a channel.
 *
 * @see Thread
 *
 * @since 7.0.0
 *
 * @method Thread|null get(string $discrim, $key)
 * @method Thread|null pull(string|int $key, $default = null)
 * @method Thread|null first()
 * @method Thread|null last()
 * @method Thread|null find(callable $callback)
 */
class ThreadRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_THREADS_ACTIVE,
        'get' => Endpoint::THREAD,
        'update' => Endpoint::THREAD,
        'delete' => Endpoint::THREAD,
        'create' => Endpoint::CHANNEL_THREADS,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Thread::class;

    /**
     * {@inheritDoc}
     */
    protected function cacheFreshen($response): ExtendedPromiseInterface
    {
        foreach ($response->threads as $value) {
            $value = array_merge($this->vars, (array) $value);
            /** @var Thread */
            $part = $this->factory->create($this->class, $value, true);
            $items[$part->{$this->discrim}] = $part;
        }

        if (empty($items)) {
            return resolve($this);
        }

        $members = $response->members;

        return $this->cache->setMultiple($items)->then(function ($success) use ($items, $members) {
            foreach ($items as $thread) {
                foreach ($members as $member) {
                    if ($member->id == $thread->id) {
                        $thread->members->cache->set($member->id, $thread->members->create((array) $member + ['guild_id' => $thread->guild_id], true));
                        break;
                    }
                }
            }

            return $this;
        });
    }

    /**
     * Fetches all the active threads on the channel.
     *
     * @link https://discord.com/developers/docs/resources/channel#list-active-threads
     *
     * @return ExtendedPromiseInterface<Collection<Thread>>
     */
    public function active(): ExtendedPromiseInterface
    {
        return $this->http->get(Endpoint::bind(Endpoint::GUILD_THREADS_ACTIVE, $this->vars['guild_id']))
            ->then(function ($response) {
                return $this->handleThreadPaginationResponse($response);
            });
    }

    /**
     * Fetches archived threads based on a set of options.
     *
     * @link https://discord.com/developers/docs/resources/channel#list-public-archived-threads
     * @link https://discord.com/developers/docs/resources/channel#list-private-archived-threads
     * @link https://discord.com/developers/docs/resources/channel#list-joined-private-archived-threads
     *
     * @param bool               $private Whether we are fetching archived private threads.
     * @param bool               $joined  Whether we are fetching private threads that we have joined. Note `private` cannot be false while `joined` is true.
     * @param int|null           $limit   The number of threads to return, null to return all.
     * @param Thread|string|null $before  Retrieve threads before this thread. Takes a thread object or a thread ID.
     *
     * @throws \InvalidArgumentException
     *
     * @return ExtendedPromiseInterface<Collection<Thread>>
     */
    public function archived(bool $private = false, bool $joined = false, ?int $limit = null, $before = null): ExtendedPromiseInterface
    {
        if ($joined) {
            if (! $private) {
                throw new \InvalidArgumentException('You cannot fetch threads that the bot has joined but are not private.');
            }

            $endpoint = Endpoint::CHANNEL_THREADS_ARCHIVED_PRIVATE_ME;
        } else {
            if ($private) {
                $endpoint = Endpoint::CHANNEL_THREADS_ARCHIVED_PRIVATE;
            } else {
                $endpoint = Endpoint::CHANNEL_THREADS_ARCHIVED_PUBLIC;
            }
        }

        $endpoint = Endpoint::bind($endpoint, $this->vars['channel_id']);

        if ($limit != null) {
            $endpoint->addQuery('limit', $limit);
        }

        if ($before != null) {
            if ($before instanceof Thread) {
                $before = $before->id;
            }

            $endpoint->addQuery('before', $before);
        }

        return $this->http->get(Endpoint::bind($endpoint, $this->vars['channel_id']))
            ->then(function ($response) {
                return $this->handleThreadPaginationResponse($response);
            });
    }

    /**
     * Handles a response from one of the thread pagination endpoints.
     *
     * @param object $response
     */
    private function handleThreadPaginationResponse(object $response)
    {
        $collection = Collection::for(Thread::class);

        foreach ($response->threads as $thread) {
            /** @var Thread */
            $thread = $this->factory->part(Thread::class, (array) $thread, true);

            foreach ($response->members as $member) {
                if ($member->id == $thread->id) {
                    $thread->members->pushItem($thread->members->create($member, true));
                }
            }

            $collection->pushItem($thread);
        }

        return $collection;
    }
}
