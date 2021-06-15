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
use Discord\Parts\Thread\Member;
use Discord\Parts\Thread\Thread;
use Discord\Repository\AbstractRepository;
use InvalidArgumentException;
use React\Promise\ExtendedPromiseInterface;

/**
 * Contains threads that belong to a channel.
 *
 * @method Thread|null get(string $discrim, $key)  Gets an item from the collection.
 * @method Thread|null first()                     Returns the first element of the collection.
 * @method Thread|null pull($key, $default = null) Pulls an item from the repository, removing and returning the item.
 * @method Thread|null find(callable $callback)    Runs a filter callback over the repository.
 */
class ThreadRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected $endpoints = [
        'all' => 'channels/:channel_id/threads/active',
        'get' => 'channels/:thread_id',
        'update' => 'channels/:thread_id',
        'delete' => 'channels/:thread_id',
    ];

    /**
     * {@inheritdoc}
     */
    protected $class = Thread::class;

    /**
     * Fetches all the active threads on the channel.
     *
     * @return ExtendedPromiseInterface<Collection<Thread>>
     */
    public function active(): ExtendedPromiseInterface
    {
        return $this->http->get(Endpoint::bind('channels/:channel_id/threads/active', $this->vars['channel_id']))
            ->then(function ($response) {
                return $this->handleThreadPaginationResponse($response);
            });
    }

    /**
     * Fetches archived threads based on a set of options.
     *
     * @param bool               $private Whether we are fetching archived private threads.
     * @param bool               $joined  Whether we are fetching private threads that we have joined. Note `private` cannot be false while `joined` is true.
     * @param int|null           $limit   The number of threads to return, null to return all.
     * @param Thread|string|null $before  Retrieve threads before this thread. Takes a thread object or a thread ID.
     *
     * @return ExtendedPromiseInterface<Collection<Thread>>
     */
    public function archived(bool $private = false, bool $joined = false, ?int $limit = null, $before = null): ExtendedPromiseInterface
    {
        if ($joined) {
            if (! $private) {
                throw new InvalidArgumentException('You cannot fetch threads that the bot has joined but are not private.');
            }

            $endpoint = 'channels/:channel_id/users/@me/threads/archived/private';
        } else {
            if ($private) {
                $endpoint = 'channels/:channel_id/threads/archived/private';
            } else {
                $endpoint = 'channels/:channel_id/threads/archived/public';
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
            $thread = $this->factory->create(Thread::class, $thread, true);

            foreach ($response->members as $member) {
                if ($member->id == $thread->id) {
                    $thread->members->push($this->factory->create(Member::class, $member, true));
                    break;
                }
            }

            $collection->push($thread);
        }

        return $collection;
    }
}
