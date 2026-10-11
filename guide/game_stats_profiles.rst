=========================
Game stats profiles
=========================

The existing ``$application->identities`` repository stores game stats for a linked
player's external account. The player must authorize ``application_identities.write``;
publishing uses the application's bot token. Configure the widget in the Developer
Portal. DiscordPHP handles REST data; the native Social SDK and widget editor remain
separate integrations.

Read typed stats
===============

.. code-block:: php

   use Discord\Parts\Application\Identity\ApplicationIdentityProfile;

   $application->identities->getProfile($discordUserId, $externalAccountId)
       ->then(function (ApplicationIdentityProfile $profile) {
           echo $profile->data?->primary?->rank_name ?? 'Unranked';
           foreach ($profile->data?->dynamic ?? [] as $field) {
               // DynamicStringField, DynamicNumberField, or DynamicMediaField.
               // A media field's value is a ProfileMedia with a url property.
               echo $field->name;
           }
       });

``data`` and ``primary`` are Parts; ``dynamic`` is an unkeyed typed collection.
Repeated names retain their order. Array-offset reads such as
``$profile->data['primary']['rank_name']`` continue to work, but
``is_array($profile->data)`` now returns false. Absent/null data read as null;
present empty data is a ProfileData. Unknown dynamic types use DynamicField and
retain their raw value.

Typed reads create views without replacing raw attributes. Editing a nested view
does not edit its parent; use the builder or assign complete raw data back.
``getRawAttributes()`` and profile JSON serialization retain the original payload.

Publish a complete snapshot
===========================

.. code-block:: php

   use Discord\Builders\ApplicationIdentityProfileBuilder;
   use Discord\Parts\Application\Identity\DynamicField;

   ApplicationIdentityProfileBuilder::new()
       ->setUsername('lancelot')
       ->setData([
           'primary' => ['rank_name' => 'Gold', 'playtime_hours' => 69.41],
           'dynamic' => [
               ['type' => DynamicField::TYPE_STRING, 'name' => 'title', 'value' => 'Champion'],
               ['type' => DynamicField::TYPE_NUMBER, 'name' => 'wins', 'value' => 57],
               ['type' => DynamicField::TYPE_MEDIA, 'name' => 'portrait',
                'value' => ['url' => 'https://example.com/player.png']],
           ],
       ])
       ->publish($application->identities, $discordUserId, $externalAccountId)
       ->then(function () {
           echo 'Stats published';
       });

Every included ``data`` object replaces all previous stats. To retain fetched
fields, start with ``ApplicationIdentityProfileBuilder::fromPart($profile)``,
change the array from ``getData()``, then call ``setData($completeData)``.
Response-only metadata is excluded. For a username-only update, use setUsername()
without setData(), or call omitData() on a copied builder.

The existing ``updateProfile($user, $externalId, $array)`` call still works and
also accepts the builder. Validation covers 30 dynamic fields, 100-character stat
values and keys, a 1024-character username and 10 KB serialized data using the HTTP
client's JSON encoding. The 256-character Custom String limit applies to widget
configuration, not dynamic payload values. Media requires an HTTP(S) URL reachable
by Discord. Validation does not fetch URLs.

Nullable response data and username survive fromPart(). Raw array writes and builder
null values retain their wire representation; the documentation describes object
writes and does not establish null as a clearing operation. Use a complete object,
including an empty setData([]) object when intended. Publishing preserves the
repository's response contract, including a 204 with no profile body.

Verified sources
================

Reviewed on 2026-10-10 against:

* `Application Identity Profile resource <https://docs.discord.com/developers/resources/application-identity-profile>`_
  and `Sending Game Data <https://docs.discord.com/developers/social-layer/game-stats-widgets/sending-game-data>`_.
* Documentation source ``37148620d2324292b76047541d5f8331f81ea77a``
  in discord/discord-api-docs.
* OpenAPI **preview** ``1ff2dee3677fabef7b49f942bbae93d06c0c9f2c`` in
  discord/discord-api-spec. It lacks application identity profile routes and schemas;
  published resource documentation supplies this milestone's contract. Preview
  coverage cannot prove runtime behavior.
* DiscordPHP base ``ee73786f2ee8cd94120abd3f8f666cf13544b60b`` and the isolated
  Composer resolution of discord-php/http 10.9.8. This library has no tracked lockfile;
  the primary checkout's intentional path dependencies were untouched.

Dedicated offline tests verify heterogeneous typed reads, absent/null data,
builder round trips, limits and recorded PATCH bodies. No live writes were performed.
