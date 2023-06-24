<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Permissions;

/**
 * Represents a set of permissions for a given channel overwrite.
 *
 * @since 2.1.3
 *
 * @property bool $priority_speaker         Allows for using priority speaker in a voice channel
 * @property bool $stream                   Allows the user to go live
 * @property bool $connect                  Allows for joining of a voice channel
 * @property bool $speak                    Allows for speaking in a voice channel
 * @property bool $mute_members             Allows for muting members in a voice channel
 * @property bool $deafen_members           Allows for deafening of members in a voice channel
 * @property bool $move_members             Allows for moving of members between voice channels
 * @property bool $use_vad                  Allows for using voice-activity-detection in a voice channel
 * @property bool $request_to_speak         Allows for requesting to speak in stage channels. (*This permission is under active development and may be changed or removed.*)
 * @property bool $manage_events            Allows for creating, editing, and deleting scheduled events
 * @property bool $manage_threads           Allows for deleting and archiving threads, and viewing all private threads
 * @property bool $create_public_threads    Allows for creating public and announcement threads
 * @property bool $create_private_threads   Allows for creating private threads
 * @property bool $send_messages_in_threads Allows for sending messages in threads
 * @property bool $use_embedded_activities  Allows for using Activities (applications with the `EMBEDDED` flag) in a voice channel
 * @property bool $use_soundboard           Allows for using soundboard in a voice channel
 * @property bool $use_external_sounds      Allows the usage of custom soundboard sounds from other servers
 */
class ChannelPermission extends Permission
{
    /**
     * {@inheritDoc}
     */
    public static function getPermissions(): array
    {
        return array_merge(parent::ALL_PERMISSIONS, parent::TEXT_PERMISSIONS, parent::VOICE_PERMISSIONS, parent::STAGE_PERMISSIONS);
    }
}
