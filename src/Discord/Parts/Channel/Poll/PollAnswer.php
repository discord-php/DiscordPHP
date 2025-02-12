<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel\Poll;

use Discord\Helpers\Collection;
use Discord\Http\Endpoint;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\Thread\Thread;
use Discord\Parts\User\User;
use React\Promise\PromiseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function Discord\normalizePartId;

/**
 * An answer to a poll.
 *
 * @link https://discord.com/developers/docs/resources/poll#poll-answer-object
 *
 * @since 10.0.0
 *
 * @property int        $answer_id   The ID of the answer. Only sent as part of responses from Discord's API/Gateway.
 * @property PollMedia  $poll_media  The data of the answer
 *
 * @property      string         $user_id    The user ID that voted for the answer.
 * @property-read User           $user       The user that voted for the answer.
 * @property      string         $channel_id The channel ID that the poll belongs to.
 * @property-read Channel|Thread $channel    The channel that the poll belongs to.
 * @property      string         $message_id The message ID that the poll belongs to.
 * @property      Message|null   $message    The message the poll belongs to.
 * @property      string|null    $guild_id   The guild ID of the guild that owns the channel the poll message is in.
 * @property-read Guild|null     $guild      The guild that owns the channel the poll belongs in.
 */
class PollAnswer extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'answer_id',
        'poll_media',

        // events
        'user_id',
        'channel_id',
        'message_id',
        'guild_id',
    ];

    /**
     * Gets the user attribute.
     *
     * @return User
     */
    protected function getUserAttribute(): User
    {
        return $this->discord->users->get('id', $this->user_id);
    }

    /**
     * Gets the channel attribute.
     *
     * @return Channel|Thread
     */
    protected function getChannelAttribute(): Part
    {
        if ($guild = $this->guild) {
            $channels = $guild->channels;

            if ($channel = $channels->get('id', $this->channel_id)) {
                return $channel;
            }

            foreach ($channels as $parent) {
                if ($thread = $parent->threads->get('id', $this->channel_id)) {
                    return $thread;
                }
            }
        }

        // @todo potentially slow
        if ($channel = $this->discord->getChannel($this->channel_id)) {
            return $channel;
        }

        return $this->factory->part(Channel::class, [
            'id' => $this->channel_id,
            'type' => Channel::TYPE_DM,
        ], true);
    }

    /**
     * Gets the message attribute.
     *
     * @return Message|null
     */
    protected function getMessageAttribute(): ?Message
    {
        if ($channel = $this->channel) {
            return $channel->messages->get('id', $this->message_id);
        }

        return $this->attributes['message'] ?? null;
    }

    /**
     * Returns the guild that owns the channel the message was sent in.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        if (! isset($this->guild_id)) {
            return null;
        }

        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Returns the users that voted for the answer.
     *
     * @param array       $options          An array of options. All fields are optional.
     * @param string|null $options['after'] Get users after this user ID.
     * @param int|null    $options['limit'] Max number of users to return (1-100).
     *
     * @link https://discord.com/developers/docs/resources/poll#get-answer-voters
     *
     * @throws \OutOfRangeException
     *
     * @return PromiseInterface<Collection|User[]>
     */
    public function getVoters(array $options = []): PromiseInterface
    {
        $query = Endpoint::bind(Endpoint::MESSAGE_POLL_ANSWER, $this->channel_id, $this->message_id, $this->answer_id);

        $resolver = new OptionsResolver();
        $resolver
            ->setDefined(['after', 'limit'])
            ->setAllowedTypes('after', ['int', 'string', User::class])
            ->setAllowedTypes('limit', 'int')
            ->setNormalizer('after', normalizePartId())
            ->setAllowedValues('limit', fn ($value) => ($value >= 1 && $value <= 100));

        $options = $resolver->resolve($options);

        foreach ($options as $key => $value) {
            $query->addQuery($key, $value);
        }

        return $this->http->get($query)
            ->then(function ($response) {
                $users = Collection::for(User::class);

                foreach ($response->users ?? [] as $user) {
                    if (! $part = $this->discord->users->get('id', $user->id)) {
                        $part = $this->discord->users->create($user, true);

                        $this->discord->users->pushItem($part);
                    }

                    $users->pushItem($part);
                }

                return $users;
            });
    }
}
