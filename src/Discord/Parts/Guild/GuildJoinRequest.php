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

namespace Discord\Parts\Guild;

use Carbon\Carbon;
use Discord\Helpers\ExCollectionInterface;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use Discord\Repository\Guild\GuildJoinRequestRepository;
use React\Promise\PromiseInterface;

use function React\Promise\reject;

/**
 * Represents a guild join request.
 *
 * @link TODO
 *
 * @since 10.55.0
 *
 * @property string                                                       $id                 ID of the join request.
 * @property Carbon|null                                                  $created_at         When the applicant started the join request.
 * @property Carbon|null                                                  $reviewed_at        When the join request was approved or rejected.
 * @property string|null                                                  $application_status Application status (STARTED, SUBMITTED, APPROVED, REJECTED).
 * @property string|null                                                  $rejection_reason   Reason the join request was rejected (only set when REJECTED).
 * @property string                                                       $guild_id           ID of the guild the applicant is applying to.
 * @property string                                                       $user_id            ID of the applicant.
 * @property ?User|null                                                   $user               The applicant user object.
 * @property ExCollectionInterface<FormFieldResponse>|FormFieldResponse[] $form_responses     Applicant's responses to the guild's verification form.
 * @property ?User|null                                                   $actioned_by_user   User who approved or rejected the join request.
 */
class GuildJoinRequest extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
        'created_at',
        'reviewed_at',
        'application_status',
        'rejection_reason',
        'guild_id',
        'user_id',
        'user',
        'form_responses',
        'actioned_by_user',
    ];

    /**
     * Approve or reject this join request.
     *
     * @param bool|string $action          Either 'APPROVED' or 'REJECTED'.
     * @param string|null $rejectionReason Optional rejection reason.
     *
     * @return PromiseInterface<GuildJoinRequest>
     */
    public function action(bool|string $action, ?string $rejectionReason = null): PromiseInterface
    {
        $action = is_bool($action) ? ($action ? 'APPROVED' : 'REJECTED') : $action;

        /** @var Guild $guild */
        $guild = $this->guild ?? $this->factory->part(Guild::class, ['id' => $this->attributes['guild_id']], true);

        if ($botperms = $guild->getBotPermissions()) {
            if (! $botperms->kick_members) {
                return reject(new NoPermissionsException("You do not have permission to action join requests in the guild {$this->guild_id}."));
            }
        }

        return $guild->join_requests->action($this, $action, $rejectionReason)->then(function (GuildJoinRequest $new) {
            $this->fill((array) $new);

            return $this;
        });
    }

    /**
     * Approve the join request.
     *
     * @return PromiseInterface<GuildJoinRequest>
     */
    public function approve(): PromiseInterface
    {
        return $this->action('APPROVED');
    }

    /**
     * Reject the join request.
     *
     * @param string|null $reason Reason for rejection.
     *
     * @return PromiseInterface<GuildJoinRequest>
     */
    public function reject(?string $reason = null): PromiseInterface
    {
        return $this->action('REJECTED', $reason);
    }

    protected function getCreatedAtAttribute(): ?Carbon
    {
        return $this->attributeCarbonHelper('created_at');
    }

    protected function getReviewedAtAttribute(): ?Carbon
    {
        return $this->attributeCarbonHelper('reviewed_at');
    }

    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    protected function getUserAttribute(): ?User
    {
        return $this->attributePartHelper('user', User::class);
    }

    /**
     * Returns form responses as an array of FormFieldResponse parts.
     *
     * @return ExCollectionInterface<FormFieldResponse>
     */
    protected function getFormResponsesAttribute(): ExCollectionInterface
    {
        return $this->attributeTypedCollectionHelper(FormFieldResponse::class, 'form_responses');
    }

    protected function getActionedByUserAttribute(): ?User
    {
        return $this->attributePartHelper('actioned_by_user', User::class);
    }

    /**
     * Returns the originating repository of the part.
     *
     * @since 10.55.0
     *
     * @throws \Exception If the part does not have an originating repository.
     *
     * @return GuildJoinRequestRepository|null The repository, or null if required part data is missing.
     */
    public function getRepository(): GuildJoinRequestRepository|null
    {
        if (! isset($this->attributes['guild_id'])) {
            return null;
        }

        /** @var Guild $guild */
        $guild = $this->guild ?? $this->factory->part(Guild::class, ['id' => $this->attributes['guild_id']], true);

        return $guild->join_requests;
    }

    /**
     * @inheritDoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'join_request_id' => $this->id,
            'id' => $this->id,
        ];
    }
}
