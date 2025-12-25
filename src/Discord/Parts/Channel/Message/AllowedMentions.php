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

namespace Discord\Parts\Channel\Message;

use JsonSerializable;

/**
 * The allowed mention field allows for more granular control over mentions without various hacks to the message.
 * This will always validate against the message and components to avoid phantom pings
 * (e.g. to ping everyone, you must still have @everyone in the message), and check against user/bot permissions.
 *
 * @link https://discord.com/developers/docs/resources/message#allowed-mentions-object
 *
 * @since 10.10.1
 *
 * @property ?string[]|null $parse        An array of allowed mention types to parse from the content.
 * @property ?string[]|null $roles        Array of role_ids to mention (Max size of 100).
 * @property ?string[]|null $users        Array of user_ids to mention (Max size of 100).
 * @property bool           $replied_user For replies, whether to mention the author of the message being replied to (default false).
 */
class AllowedMentions implements JsonSerializable
{
    /** Controls role mentions. */
    public const TYPE_ROLE = 'roles';
    /** Controls user mentions. */
    public const TYPE_USER = 'users';
    /** Controls @everyone and @here mentions. */
    public const TYPE_EVERYONE = 'everyone';

    /**
     * An array of allowed mention types to parse from the content.
     *
     * @var string[]|null
     */
    protected $parse;
    /**
     * 	Array of role_ids to mention (Max size of 100).
     *
     * @var array|null
     */
    protected $roles;
    /**
     * Array of user_ids to mention (Max size of 100).
     *
     * @var array|null
     */
    protected $users;
    /**
     * For replies, whether to mention the author of the message being replied to (default false).
     *
     * @var ?bool
     */
    protected $replied_user;

    /**
     * Creates a new allowed mention.
     *
     * @return static
     */
    public static function new(): self
    {
        return new static();
    }

    /**
     * Creates a new allowed mention with all mention types disallowed.
     *
     * @return static
     */
    public static function none(): self
    {
        $self = new static();
        $self->disallowAllMentions();

        return $self;
    }

    /**
     * Sets the list of current allowed mention types to a new, empty array.
     * This effectively disallows all mentions on the message.
     *
     * @return self
     */
    public function disallowAllMentions(): self
    {
        $this->parse = [];
        $this->clearRoles();
        $this->clearUsers();

        return $this;
    }

    /**
     * Sets the list of allowed mentioned types.
     *
     * @param string[]|null $items
     *
     * @throws \InvalidArgumentException Allowed mention type must be one of: roles, users, everyone
     *
     * @return self
     */
    public function setParse($items): self
    {
        $this->clearParse();

        foreach ($items as $item) {
            $this->addParse($item);
        }

        return $this;
    }

    /**
     * Adds a type to the list of allowed mentioned types.
     *
     * @param string $items
     *
     * @throws \InvalidArgumentException Type must be one of: roles, users, everyone
     *
     * @return self
     */
    public function addParse(...$items): self
    {
        static $allowed = [self::TYPE_ROLE, self::TYPE_USER, self::TYPE_EVERYONE];

        foreach ($items as &$item) {
            if (! is_string($item)) {
                throw new \InvalidArgumentException('Allowed mention type must be a string.');
            }
            if (! in_array($item, $allowed, true)) {
                throw new \InvalidArgumentException('Allowed mention type must be one of: roles, users, everyone');
            }
        }

        if (! isset($this->parse)) {
            $this->parse = [];
        }

        foreach ($items as $item) {
            if (! in_array($item, $this->parse, true)) {
                $this->parse[] = $item;
            }
        }

        $this->parse = array_unique($this->parse);

        return $this;
    }

    /**
     * Removes a specific type from the list of allowed mentioned types.
     *
     * @param  string $parse
     * @return self
     */
    public function removeParse(...$parse): self
    {
        foreach ($parse as $item) {
            if (isset($this->parse) && ($idx = array_search($item, $this->parse, true)) !== false) {
                array_splice($this->parse, $idx, 1);
            }
        }

        return $this;
    }

    /**
     * Clears the list of allowed mentioned types.
     *
     * @return self
     */
    public function clearParse(): self
    {
        $this->parse = null;

        return $this;
    }

    /**
     * Retrieves the list of allowed mentioned types.
     *
     * @return string[]|null
     */
    public function getParse(): ?array
    {
        return $this->parse ?? null;
    }

    /**
     * Sets the list of allowed mentioned roles.
     *
     * @param string[]|null $items
     *
     * @throws \InvalidArgumentException Allowed mention type must be one of: roles, users, everyone
     *
     * @return self
     */
    public function setRoles($items): self
    {
        $this->clearRoles();

        foreach ($items as $item) {
            $this->addRole($item);
        }

        $this->roles = array_unique($this->roles);

        return $this;
    }

    /**
     * Adds a role to the list of allowed mentioned roles.
     *
     * @param string $items
     *
     * @throws \InvalidArgumentException Allowed mention role must be a numeric snowflake.
     *
     * @return self
     */
    public function addRole(...$items): self
    {
        foreach ($items as &$item) {
            if (! is_numeric($item)) {
                throw new \InvalidArgumentException('Allowed mention role must be a numeric snowflake.');
            }
            $item = (string) $item;
        }

        if (! isset($this->roles)) {
            $this->roles = [];
        }

        foreach ($items as $item) {
            if (! in_array($item, $this->roles, true)) {
                $this->roles[] = $item;
            }
        }

        $this->roles = array_unique($this->roles);

        return $this;
    }

    /**
     * Removes a specific role from the list of allowed mentioned roles.
     *
     * @param  string $roles
     * @return self
     */
    public function removeRoles(...$roles): self
    {
        foreach ($roles as $item) {
            if (isset($this->roles) && ($idx = array_search($item, $this->roles, true)) !== false) {
                array_splice($this->roles, $idx, 1);
            }
        }

        return $this;
    }

    /**
     * Clears the list of allowed mentioned roles.
     *
     * @return self
     */
    public function clearRoles(): self
    {
        $this->roles = null;

        return $this;
    }

    /**
     * Retrieves the list of allowed mentioned roles.
     *
     * @return string[]|null
     */
    public function getRoles(): ?array
    {
        return $this->roles ?? null;
    }

    /**
     * Sets the list of allowed mentioned users.
     *
     * @param string[]|null $items
     *
     * @throws \InvalidArgumentException Allowed mention type must be one of: users, users, everyone
     *
     * @return self
     */
    public function setUsers($items): self
    {
        $this->clearUsers();

        foreach ($items as $item) {
            $this->addUser($item);
        }

        return $this;
    }

    /**
     * Adds a user to the list of allowed mentioned users.
     *
     * @param string $items
     *
     * @throws \InvalidArgumentException Allowed mention role must be a numeric snowflake.
     *
     * @return self
     */
    public function addUser(...$items): self
    {
        foreach ($items as &$item) {
            if (! is_numeric($item)) {
                throw new \InvalidArgumentException('Allowed mention user must be a numeric snowflake.');
            }
            $item = (string) $item;
        }

        if (! isset($this->users)) {
            $this->users = [];
        }

        foreach ($items as $item) {
            if (! in_array($item, $this->users, true)) {
                $this->users[] = $item;
            }
        }

        $this->users = array_unique($this->users);

        return $this;
    }

    /**
     * Removes a specific user from the list of allowed mentioned users.
     *
     * @param  string $users
     * @return self
     */
    public function removeUser(...$users): self
    {
        foreach ($users as $item) {
            if (isset($this->users) && ($idx = array_search($item, $this->users, true)) !== false) {
                array_splice($this->users, $idx, 1);
            }
        }

        return $this;
    }

    /**
     * Clears the list of allowed mentioned users.
     *
     * @return self
     */
    public function clearUsers(): self
    {
        $this->users = null;

        return $this;
    }

    /**
     * Retrieves the list of allowed mentioned users.
     *
     * @return string[]|null
     */
    public function getUsers(): ?array
    {
        return $this->users ?? null;
    }

    /**
     * Sets whether to mention the author of the message being replied to (default false).
     *
     * @param  bool $replied_user
     * @return self
     */
    public function setRepliedUser(bool $replied_user = true): self
    {
        $this->replied_user = $replied_user;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $data = [];

        // Remove invalid configurations
        if (isset($this->roles) && in_array(self::TYPE_ROLE, $this->parse, true)) {
            unset($this->roles);
        }
        if (isset($this->users) && in_array(self::TYPE_USER, $this->parse, true)) {
            unset($this->users);
        }

        if (isset($this->parse)) {
            $data['parse'] = $this->parse;
        }

        if (isset($this->roles)) {
            $data['roles'] = $this->roles;
        }

        if (isset($this->users)) {
            $data['users'] = $this->users;
        }

        if (isset($this->replied_user)) {
            $data['replied_user'] = $this->replied_user;
        }

        // Remove invalid configurations
        if (isset($this->roles) && in_array(self::TYPE_ROLE, $this->parse, true)) {
            $this->removeParse(self::TYPE_ROLE);
        }
        if (isset($this->users) && in_array(self::TYPE_USER, $this->parse, true)) {
            $this->removeParse(self::TYPE_USER);
        }

        return $data;
    }
}
