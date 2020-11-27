<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel;

use Discord\Helpers\Collection;
use Discord\Parts\Guild\Emoji;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use React\Promise\ExtendedPromiseInterface;

/**
 * Represents a reaction to a message by members(s).
 *
 * @property string $id The identifier of the reaction.
 * @property int $count Number of reactions.
 * @property bool $me Whether the current bot has reacted.
 * @property Emoji $emoji The emoji that was reacted with.
 * @property string $message_id The message ID the reaction is for.
 * @property Message|null $message The message the reaction is for.
 * @property string $channel_id The channel ID that the message belongs in.
 * @property Channel $channel The channel that the message belongs tol
 */
class Reaction extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['count', 'me', 'emoji', 'message_id', 'channel_id'];

    /**
     * Gets the emoji identifier, combination of `id` and `name`.
     *
     * @return string
     */
    protected function getIdAttribute(): string
    {
        return ":{$this->emoji->name}:{$this->emoji->id}";
    }

    /**
     * Gets the users that have used the reaction.
     *
     * @param array $options See https://discord.com/developers/docs/resources/channel#get-reactions
     *
     * @return ExtendedPromiseInterface<Collection|Users[]>
     */
    public function getUsers(array $options = []): ExtendedPromiseInterface
    {
        $content = http_build_query($options);
        $query = "channels/{$this->channel_id}/messages/{$this->message_id}/reactions/".urlencode($this->id).(empty($content) ? null : "?{$content}");

        return $this->http->get($query)
        ->then(function ($response) {
            $users = new Collection([], 'id', User::class);

            foreach ((array) $response as $user) {
                if ($user = $this->discord->users->get('id', $user->id)) {
                    $users->push($user);
                } else {
                    $users->push(new User($this->discord, (array) $user, true));
                }
            }

            return $users;
        });
    }

    /**
     * Gets the partial emoji attribute.
     *
     * @return Emoji
     * @throws \Exception
     */
    protected function getEmojiAttribute(): ?Part
    {
        if (isset($this->attributes['emoji'])) {
            return $this->factory->create(Emoji::class, $this->attributes['emoji'], true);
        }

        return null;
    }

    /**
     * Gets the message attribute.
     *
     * @return Message|null
     */
    protected function getMessageAttribute(): ?Message
    {
        if ($channel = $this->channel) {
            return $channel->messages->offsetGet($this->message_id);
        }

        return null;
    }

    /**
     * Gets the channel attribute.
     *
     * @return Channel
     */
    protected function getChannelAttribute(): Channel
    {
        return $this->discord->getChannel($this->channel_id);
    }
}
