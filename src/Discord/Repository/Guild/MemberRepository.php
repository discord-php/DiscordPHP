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
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\OAuth2\AccessToken;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\Repository\AbstractRepository;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

use function React\Promise\reject;

/**
 * Contains members of a guild.
 *
 * @since 4.0.0
 *
 * @see Member
 * @see \Discord\Parts\Guild\Guild
 *
 * @method Member|null get(string $discrim, $key)
 * @method Member|null pull(string|int $key, $default = null)
 * @method Member|null first()
 * @method Member|null last()
 * @method Member|null find(callable $callback)
 */
class MemberRepository extends AbstractRepository
{
    /**
     * @inheritDoc
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_MEMBERS,
        'get' => Endpoint::GUILD_MEMBER,
        'update' => Endpoint::GUILD_MEMBER,
        'delete' => Endpoint::GUILD_MEMBER,
    ];

    /**
     * @inheritDoc
     */
    protected $class = Member::class;

    /**
     * Adds a user to the guild with their OAuth2 access token, which must have the `guilds.join` scope and
     * come from the bot's own application. The bot must be in the guild, with the create_instant_invite
     * permission. A user who is already a member is left as they are.
     *
     * @link https://docs.discord.com/developers/resources/guild#add-guild-member
     *
     * @param User|Member|string  $user             The user to add.
     * @param AccessToken|string  $access_token     The user's access token.
     * @param array               $options
     * @param ?string             $options['nick']  Their nickname. Requires the manage_nicknames permission.
     * @param ?array<Role|string> $options['roles'] Roles to give them. Requires the manage_roles permission.
     * @param ?bool               $options['mute']  Whether they are muted in voice channels. Requires the mute_members permission.
     * @param ?bool               $options['deaf']  Whether they are deafened in voice channels. Requires the deafen_members permission.
     *
     * @throws NoPermissionsException Missing create_instant_invite permission.
     *
     * @return PromiseInterface<Member> The new member, or the existing one if the user was already in the guild.
     *
     * @since 10.60.0
     */
    public function add($user, $access_token, array $options = []): PromiseInterface
    {
        $user_id = $user instanceof Part ? $user->id : (string) $user;

        $guild = $this->discord->guilds->get('id', $this->vars['guild_id']);
        if ($botperms = $guild?->getBotPermissions()) {
            if (! $botperms->create_instant_invite) {
                return reject(new NoPermissionsException("You do not have permission to add members to the guild {$this->vars['guild_id']}."));
            }
        }

        $payload = ['access_token' => $access_token instanceof AccessToken ? $access_token->access_token : (string) $access_token]
            + array_intersect_key($options, array_flip(['nick', 'roles', 'mute', 'deaf']));

        if (isset($payload['roles'])) {
            $payload['roles'] = array_map(static fn ($role): string => $role instanceof Role ? $role->id : (string) $role, $payload['roles']);
        }

        return $this->http->put(Endpoint::bind(Endpoint::GUILD_MEMBER, $this->vars['guild_id'], $user_id), $payload)
            ->then(function ($response) use ($user_id) {
                // No content means the user was already a member.
                if (null === $response) {
                    return $this->fetch($user_id);
                }

                $member = $this->factory->part(Member::class, array_merge($this->vars, (array) $response), true);

                return $this->cache->set($user_id, $member)->then(fn () => $member);
            });
    }

    /**
     * Returns a guild member object for the current user.
     *
     * @param Guild|string $guild
     *
     * @return PromiseInterface<Member>
     *
     * @since 10.32.0
     */
    public function getCurrentUserGuildMember($guild, bool $fresh = false): PromiseInterface
    {
        if (! is_string($guild)) {
            $guild = $guild->id;
        }

        if ($fresh) {
            return $this->__getCurrentUserGuildMember($guild);
        }

        return $this->cache->get($guild)->then(function ($part) use ($guild) {
            if ($part !== null) {
                return $part;
            }

            return $this->__getCurrentUserGuildMember($guild);
        });
    }

    /**
     * Returns a guild member object for the current user.
     * Requires the guilds.members.read OAuth2 scope.
     *
     * @link https://docs.discord.com/developers/resources/user#get-current-user-guild-member
     *
     * @param string $guild
     *
     * @return PromiseInterface<Member>
     *
     * @since 10.32.0
     */
    protected function __getCurrentUserGuildMember($guild): PromiseInterface
    {
        return $this->http->get(Endpoint::bind(Endpoint::USER_CURRENT_MEMBER, $guild))->then(function ($response) {
            $part = $this->factory->part($this->class, $response, true);

            return $this->cache->set($part->{$this->discrim}, $part)->then(fn ($success) => $part);
        });
    }

    /**
     * Modifies the current member (no validation).
     *
     * @link https://docs.discord.com/developers/resources/guild#modify-current-member-json-params
     *
     * @param Guild|string $guild            The guild or guild ID.
     * @param array        $params           The parameters to modify.
     * @param ?string|null $params['nick']   Value to set user's nickname to.
     * @param ?string|null $params['banner'] Data URI base64 encoded banner image.
     * @param ?string|null $params['avatar'] Data URL base64 encoded avatar image.
     * @param ?string|null $params['bio']    Guild member bio.
     * @param string|null  $reason           Reason for Audit Log.
     *
     * @return PromiseInterface<Member>
     *
     * @since 10.30.0
     */
    public function modifyCurrentMember($guild, array $params, ?string $reason = null): PromiseInterface
    {
        if (! is_string($guild)) {
            $guild = $guild->id;
        }

        static $allowed = ['nick', 'banner', 'avatar', 'bio'];
        $params = array_filter(
            $params,
            fn ($key) => in_array($key, $allowed, true),
            ARRAY_FILTER_USE_KEY
        );

        if (empty($params)) {
            return reject(new \InvalidArgumentException('No valid parameters to modify.'));
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_MEMBER_SELF, $guild), $params, $headers)->then(function ($response) {
            $part = $this->factory->part(Member::class, (array) $response, true);

            return $this->cache->set($part->{$this->discrim}, $part)->then(fn ($success) => $part);
        });
    }

    /**
     * Alias for `$member->delete()`.
     *
     * @link https://docs.discord.com/developers/resources/guild#remove-guild-member
     *
     * @param Member      $member The member to kick.
     * @param string|null $reason Reason for Audit Log.
     *
     * @return PromiseInterface
     */
    public function kick(Member $member, ?string $reason = null): PromiseInterface
    {
        return $this->delete($member, $reason);
    }

    /**
     * @inheritDoc
     *
     * @param array $queryparams Query string params to add to the request, leave null to paginate all members (Warning: Be careful to use this on very large guild)
     */
    public function freshen(?array $queryparams = null): PromiseInterface
    {
        if (isset($queryparams)) {
            return parent::freshen($queryparams);
        }

        $endpoint = new Endpoint($this->endpoints['all']);
        $endpoint->bindAssoc($this->vars);

        $deferred = new Deferred();

        ($paginate = function ($afterId = 0) use (&$paginate, $deferred, $endpoint) {
            $endpoint->addQuery('limit', 1000);
            $endpoint->addQuery('after', $afterId);

            $this->http->get($endpoint)->then(function ($response) use ($paginate, $deferred, $afterId) {
                if (empty($response)) {
                    $deferred->resolve($this);

                    return;
                } elseif (! $afterId) {
                    $this->items = [];
                }

                foreach ($response as $value) {
                    $lastValueId = $value->user->id;
                }

                $this->cacheFreshen($response)->then(function () use ($paginate, $lastValueId) {
                    $paginate($lastValueId);
                });
            }, [$deferred, 'reject']);
        })();

        return $deferred->promise();
    }
}
