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

use Discord\Parts\Channel\Message\Embed\Embed;
use Discord\Parts\Channel\Message\Embed\Field;
use Discord\Parts\Channel\Message\Message;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Parts that moved to follow the API routes (#943) keep working under their old names: in type hints,
 * instanceof, Factory::part(), subclasses, and data cached (serialised) before the move.
 */
final class MovedPartsAliasesTest extends DiscordTestCase
{
    public static function moves(): array
    {
        return [
            'Channel\AnnouncementThread' => ['Discord\Parts\Channel\AnnouncementThread', 'Discord\Parts\Channel\Thread\AnnouncementThread'],
            'Channel\Attachment' => ['Discord\Parts\Channel\Attachment', 'Discord\Parts\Channel\Message\Attachment'],
            'Channel\Invite' => ['Discord\Parts\Channel\Invite', 'Discord\Parts\Invite\Invite'],
            'Channel\InviteJobStatus' => ['Discord\Parts\Channel\InviteJobStatus', 'Discord\Parts\Invite\InviteJobStatus'],
            'Channel\Message' => ['Discord\Parts\Channel\Message', 'Discord\Parts\Channel\Message\Message'],
            'Channel\Poll' => ['Discord\Parts\Channel\Poll', 'Discord\Parts\Channel\Message\Poll\Poll'],
            'Channel\Poll\Poll' => ['Discord\Parts\Channel\Poll\Poll', 'Discord\Parts\Channel\Message\Poll\PollCreateRequest'],
            'Channel\Poll\PollAnswer' => ['Discord\Parts\Channel\Poll\PollAnswer', 'Discord\Parts\Channel\Message\Poll\PollAnswer'],
            'Channel\Poll\PollAnswerCount' => ['Discord\Parts\Channel\Poll\PollAnswerCount', 'Discord\Parts\Channel\Message\Poll\PollAnswerCount'],
            'Channel\Poll\PollCreateRequest' => ['Discord\Parts\Channel\Poll\PollCreateRequest', 'Discord\Parts\Channel\Message\Poll\PollCreateRequest'],
            'Channel\Poll\PollMedia' => ['Discord\Parts\Channel\Poll\PollMedia', 'Discord\Parts\Channel\Message\Poll\PollMedia'],
            'Channel\Poll\PollResults' => ['Discord\Parts\Channel\Poll\PollResults', 'Discord\Parts\Channel\Message\Poll\PollResults'],
            'Channel\PrivateThread' => ['Discord\Parts\Channel\PrivateThread', 'Discord\Parts\Channel\Thread\PrivateThread'],
            'Channel\PublicThread' => ['Discord\Parts\Channel\PublicThread', 'Discord\Parts\Channel\Thread\PublicThread'],
            'Channel\Reaction' => ['Discord\Parts\Channel\Reaction', 'Discord\Parts\Channel\Message\Reaction'],
            'Channel\ReactionCountDetails' => ['Discord\Parts\Channel\ReactionCountDetails', 'Discord\Parts\Channel\Message\ReactionCountDetails'],
            'Channel\StageInstance' => ['Discord\Parts\Channel\StageInstance', 'Discord\Parts\StageInstance\StageInstance'],
            'Channel\ThreadMetadata' => ['Discord\Parts\Channel\ThreadMetadata', 'Discord\Parts\Channel\Thread\ThreadMetadata'],
            'Channel\Webhook' => ['Discord\Parts\Channel\Webhook', 'Discord\Parts\Webhook\Webhook'],
            'Embed\Author' => ['Discord\Parts\Embed\Author', 'Discord\Parts\Channel\Message\Embed\Author'],
            'Embed\Embed' => ['Discord\Parts\Embed\Embed', 'Discord\Parts\Channel\Message\Embed\Embed'],
            'Embed\EmbedArticle' => ['Discord\Parts\Embed\EmbedArticle', 'Discord\Parts\Channel\Message\Embed\EmbedArticle'],
            'Embed\EmbedGifv' => ['Discord\Parts\Embed\EmbedGifv', 'Discord\Parts\Channel\Message\Embed\EmbedGifv'],
            'Embed\EmbedImage' => ['Discord\Parts\Embed\EmbedImage', 'Discord\Parts\Channel\Message\Embed\EmbedImage'],
            'Embed\EmbedLink' => ['Discord\Parts\Embed\EmbedLink', 'Discord\Parts\Channel\Message\Embed\EmbedLink'],
            'Embed\EmbedPollResult' => ['Discord\Parts\Embed\EmbedPollResult', 'Discord\Parts\Channel\Message\Embed\EmbedPollResult'],
            'Embed\EmbedRich' => ['Discord\Parts\Embed\EmbedRich', 'Discord\Parts\Channel\Message\Embed\EmbedRich'],
            'Embed\EmbedVideo' => ['Discord\Parts\Embed\EmbedVideo', 'Discord\Parts\Channel\Message\Embed\EmbedVideo'],
            'Embed\Field' => ['Discord\Parts\Embed\Field', 'Discord\Parts\Channel\Message\Embed\Field'],
            'Embed\Footer' => ['Discord\Parts\Embed\Footer', 'Discord\Parts\Channel\Message\Embed\Footer'],
            'Embed\Image' => ['Discord\Parts\Embed\Image', 'Discord\Parts\Channel\Message\Embed\Image'],
            'Embed\Provider' => ['Discord\Parts\Embed\Provider', 'Discord\Parts\Channel\Message\Embed\Provider'],
            'Embed\Thumbnail' => ['Discord\Parts\Embed\Thumbnail', 'Discord\Parts\Channel\Message\Embed\Thumbnail'],
            'Embed\Video' => ['Discord\Parts\Embed\Video', 'Discord\Parts\Channel\Message\Embed\Video'],
            'Guild\Account' => ['Discord\Parts\Guild\Account', 'Discord\Parts\Guild\Integration\Account'],
            'Guild\Colors' => ['Discord\Parts\Guild\Colors', 'Discord\Parts\Guild\Role\Colors'],
            'Guild\CommandPermissions' => ['Discord\Parts\Guild\CommandPermissions', 'Discord\Parts\Application\Command\CommandPermissions'],
            'Guild\EntityMetadata' => ['Discord\Parts\Guild\EntityMetadata', 'Discord\Parts\Guild\ScheduledEvent\EntityMetadata'],
            'Guild\FormFieldResponse' => ['Discord\Parts\Guild\FormFieldResponse', 'Discord\Parts\Guild\JoinRequest\FormFieldResponse'],
            'Guild\GuildJoinRequest' => ['Discord\Parts\Guild\GuildJoinRequest', 'Discord\Parts\Guild\JoinRequest\GuildJoinRequest'],
            'Guild\GuildJoinRequests' => ['Discord\Parts\Guild\GuildJoinRequests', 'Discord\Parts\Guild\JoinRequest\GuildJoinRequests'],
            'Guild\GuildSearch' => ['Discord\Parts\Guild\GuildSearch', 'Discord\Parts\Guild\Member\GuildSearch'],
            'Guild\Integration' => ['Discord\Parts\Guild\Integration', 'Discord\Parts\Guild\Integration\Integration'],
            'Guild\MultipleChoiceFormFieldResponse' => ['Discord\Parts\Guild\MultipleChoiceFormFieldResponse', 'Discord\Parts\Guild\JoinRequest\MultipleChoiceFormFieldResponse'],
            'Guild\NewMemberAction' => ['Discord\Parts\Guild\NewMemberAction', 'Discord\Parts\Guild\NewMemberWelcome\NewMemberAction'],
            'Guild\Onboarding' => ['Discord\Parts\Guild\Onboarding', 'Discord\Parts\Guild\Onboarding\Onboarding'],
            'Guild\OnboardingPrompt' => ['Discord\Parts\Guild\OnboardingPrompt', 'Discord\Parts\Guild\Onboarding\OnboardingPrompt'],
            'Guild\OnboardingPromptOption' => ['Discord\Parts\Guild\OnboardingPromptOption', 'Discord\Parts\Guild\Onboarding\OnboardingPromptOption'],
            'Guild\ParagraphFormFieldResponse' => ['Discord\Parts\Guild\ParagraphFormFieldResponse', 'Discord\Parts\Guild\JoinRequest\ParagraphFormFieldResponse'],
            'Guild\Profile' => ['Discord\Parts\Guild\Profile', 'Discord\Parts\Invite\Profile'],
            'Guild\RecurrenceRule' => ['Discord\Parts\Guild\RecurrenceRule', 'Discord\Parts\Guild\ScheduledEvent\RecurrenceRule'],
            'Guild\ResourceChannel' => ['Discord\Parts\Guild\ResourceChannel', 'Discord\Parts\Guild\NewMemberWelcome\ResourceChannel'],
            'Guild\Role' => ['Discord\Parts\Guild\Role', 'Discord\Parts\Guild\Role\Role'],
            'Guild\RoleTags' => ['Discord\Parts\Guild\RoleTags', 'Discord\Parts\Guild\Role\RoleTags'],
            'Guild\ScheduledEvent' => ['Discord\Parts\Guild\ScheduledEvent', 'Discord\Parts\Guild\ScheduledEvent\ScheduledEvent'],
            'Guild\ScheduledEventException' => ['Discord\Parts\Guild\ScheduledEventException', 'Discord\Parts\Guild\ScheduledEvent\ScheduledEventException'],
            'Guild\ScheduledEventUser' => ['Discord\Parts\Guild\ScheduledEventUser', 'Discord\Parts\Guild\ScheduledEvent\ScheduledEventUser'],
            'Guild\ServerGuide' => ['Discord\Parts\Guild\ServerGuide', 'Discord\Parts\Guild\NewMemberWelcome\ServerGuide'],
            'Guild\TermsFormFieldResponse' => ['Discord\Parts\Guild\TermsFormFieldResponse', 'Discord\Parts\Guild\JoinRequest\TermsFormFieldResponse'],
            'Guild\TextInputFormFieldResponse' => ['Discord\Parts\Guild\TextInputFormFieldResponse', 'Discord\Parts\Guild\JoinRequest\TextInputFormFieldResponse'],
            'Guild\WelcomeChannel' => ['Discord\Parts\Guild\WelcomeChannel', 'Discord\Parts\Guild\WelcomeScreen\WelcomeChannel'],
            'Guild\WelcomeMessage' => ['Discord\Parts\Guild\WelcomeMessage', 'Discord\Parts\Guild\NewMemberWelcome\WelcomeMessage'],
            'Guild\WelcomeScreen' => ['Discord\Parts\Guild\WelcomeScreen', 'Discord\Parts\Guild\WelcomeScreen\WelcomeScreen'],
            'Interactions\Command\Choice' => ['Discord\Parts\Interactions\Command\Choice', 'Discord\Parts\Application\Command\Choice'],
            'Interactions\Command\Command' => ['Discord\Parts\Interactions\Command\Command', 'Discord\Parts\Application\Command\Command'],
            'Interactions\Command\Option' => ['Discord\Parts\Interactions\Command\Option', 'Discord\Parts\Application\Command\Option'],
            'Interactions\Command\Permission' => ['Discord\Parts\Interactions\Command\Permission', 'Discord\Parts\Application\Command\Permission'],
            'Monetization\Entitlement' => ['Discord\Parts\Monetization\Entitlement', 'Discord\Parts\Application\Entitlement'],
            'Monetization\SKU' => ['Discord\Parts\Monetization\SKU', 'Discord\Parts\Application\SKU'],
            'Monetization\Subscription' => ['Discord\Parts\Monetization\Subscription', 'Discord\Parts\SKU\Subscription'],
            'OAuth\ActivityInstance' => ['Discord\Parts\OAuth\ActivityInstance', 'Discord\Parts\Application\ActivityInstance\ActivityInstance'],
            'OAuth\ActivityLocation' => ['Discord\Parts\OAuth\ActivityLocation', 'Discord\Parts\Application\ActivityInstance\ActivityLocation'],
            'OAuth\Application' => ['Discord\Parts\OAuth\Application', 'Discord\Parts\Application\Application'],
            'OAuth\ApplicationIdentity' => ['Discord\Parts\OAuth\ApplicationIdentity', 'Discord\Parts\Application\Identity\ApplicationIdentity'],
            'OAuth\ApplicationIdentityProfile' => ['Discord\Parts\OAuth\ApplicationIdentityProfile', 'Discord\Parts\Application\Identity\ApplicationIdentityProfile'],
            'OAuth\ApplicationRoleConnectionMetadata' => ['Discord\Parts\OAuth\ApplicationRoleConnectionMetadata', 'Discord\Parts\Application\ApplicationRoleConnectionMetadata'],
            'OAuth\InstallParams' => ['Discord\Parts\OAuth\InstallParams', 'Discord\Parts\Application\InstallParams'],
            'OAuth\Team' => ['Discord\Parts\OAuth\Team', 'Discord\Parts\Application\Team\Team'],
            'OAuth\TeamMember' => ['Discord\Parts\OAuth\TeamMember', 'Discord\Parts\Application\Team\TeamMember'],
            'Thread\Member' => ['Discord\Parts\Thread\Member', 'Discord\Parts\Channel\Thread\Member'],
            'Thread\Thread' => ['Discord\Parts\Thread\Thread', 'Discord\Parts\Channel\Thread\Thread'],
            'Thread\ThreadSearch' => ['Discord\Parts\Thread\ThreadSearch', 'Discord\Parts\Channel\Thread\ThreadSearch'],
            'User\Member' => ['Discord\Parts\User\Member', 'Discord\Parts\Guild\Member\Member'],
        ];
    }

    #[DataProvider('moves')]
    public function testTheOldNameIsTheSameClass(string $old, string $new)
    {
        // Registered when Composer loads, so nothing has to mention the old name first.
        $this->assertTrue(class_exists($old, false), "$old is registered without autoloading");
        $this->assertSame($new, (new ReflectionClass($old))->getName());
    }

    #[DataProvider('moves')]
    public function testAPartMadeUnderEitherNameIsBoth(string $old, string $new)
    {
        $factory = getMockDiscord()->getFactory();

        foreach ([$factory->part($old), $factory->part($new)] as $part) {
            $this->assertInstanceOf($old, $part);
            $this->assertInstanceOf($new, $part);
        }
    }

    public function testOldTypeHintsAcceptTheMovedParts()
    {
        $takesOld = static fn (\Discord\Parts\Channel\Message $message, \Discord\Parts\Embed\Embed $embed): string => $message::class.' '.$embed::class;

        $factory = getMockDiscord()->getFactory();

        $this->assertSame(Message::class.' '.Embed::class, $takesOld($factory->part(Message::class), $factory->part(Embed::class)));
    }

    public function testAClassExtendingAnOldNameStillWorks()
    {
        $subclass = new class(getMockDiscord(), ['title' => 'Hi']) extends \Discord\Parts\Embed\Embed {
        };

        $this->assertInstanceOf(Embed::class, $subclass);
        $this->assertSame('Hi', $subclass->title);
    }

    public function testDataSerialisedUnderAnOldNameStillUnserialises()
    {
        // What a persistent cache holds from before the move: the old class name in the payload.
        $field = getMockDiscord()->getFactory()->part(Field::class, ['name' => 'a', 'value' => 'b']);
        $serialised = str_replace(
            's:'.strlen(Field::class).':"'.Field::class.'"',
            's:'.strlen('Discord\Parts\Embed\Field').':"Discord\Parts\Embed\Field"',
            str_replace('O:'.strlen(Field::class).':"'.Field::class.'"', 'O:'.strlen('Discord\Parts\Embed\Field').':"Discord\Parts\Embed\Field"', serialize($field)),
        );
        $this->assertStringContainsString('"Discord\Parts\Embed\Field"', $serialised);

        $restored = unserialize($serialised);

        $this->assertInstanceOf(Field::class, $restored);
    }
}
