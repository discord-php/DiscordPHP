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

use Discord\OAuth2\AccessToken;

final class AccessTokenTest extends DiscordTestCase
{
    public function testOriginControlsRenewalWithoutGuessingFromScopes(): void
    {
        $response = ['access_token' => 'abc', 'refresh_token' => 'def', 'scope' => 'sdk.social_layer', 'origin' => AccessToken::ORIGIN_OAUTH2];
        foreach ([
            AccessToken::ORIGIN_PROVISIONAL_BOT,
            AccessToken::ORIGIN_PROVISIONAL_EXTERNAL,
            AccessToken::ORIGIN_PROVISIONAL_PUBLIC,
            AccessToken::ORIGIN_CHILD,
        ] as $origin) {
            $token = AccessToken::fromResponse($response, 1000, $origin);
            $this->assertTrue($token->requiresReacquisition());
            $this->assertFalse($token->isRefreshable());
            $this->assertSame($origin, AccessToken::fromArray($token->jsonSerialize())->origin);
        }

        foreach ([AccessToken::ORIGIN_UNKNOWN, AccessToken::ORIGIN_OAUTH2] as $origin) {
            $token = AccessToken::fromResponse($response, origin: $origin);
            $this->assertFalse($token->requiresReacquisition());
            $this->assertTrue($token->isRefreshable());
        }

        $this->assertSame(AccessToken::ORIGIN_UNKNOWN, AccessToken::fromResponse($response)->origin);
    }

    public function testLegacyStoredTokensHaveUnknownOrigin(): void
    {
        $token = AccessToken::fromArray(['access_token' => 'old', 'refresh_token' => 'refresh', 'scopes' => ['sdk.social_layer']]);
        $this->assertSame(AccessToken::ORIGIN_UNKNOWN, $token->origin);
        $this->assertTrue($token->isRefreshable());
        $this->assertFalse($token->requiresReacquisition());
    }

    public function testUnknownOriginValuesAreRefusedRatherThanEnablingRefresh(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        AccessToken::fromArray(['access_token' => 'abc', 'refresh_token' => 'def', 'origin' => 'future-provisional']);
    }

    public function testResponseExpiryIsUsedForEveryIssuanceOrigin(): void
    {
        foreach ([3600, 604800] as $lifetime) {
            $token = AccessToken::fromResponse(['access_token' => 'abc', 'expires_in' => $lifetime], 1000, AccessToken::ORIGIN_PROVISIONAL_EXTERNAL);
            $this->assertFalse($token->isExpired(60, 1000 + $lifetime - 61));
            $this->assertTrue($token->isExpired(60, 1000 + $lifetime - 60));
            $this->assertTrue($token->isExpired(0, 1000 + $lifetime));
        }
    }

    public function testATokenResponseRecordsWhenItExpires(): void
    {
        $token = AccessToken::fromResponse((object) [
            'access_token' => 'abc',
            'token_type' => 'Bearer',
            'expires_in' => 604800,
            'refresh_token' => 'def',
            'scope' => 'identify sdk.social_layer',
        ], 1000);

        $this->assertSame(1000 + 604800, $token->expires_at);
        $this->assertSame(['identify', 'sdk.social_layer'], $token->scopes);
        $this->assertSame('Bearer abc', $token->authorization());
        $this->assertTrue($token->isRefreshable());
    }

    public function testExpiryAllowsForTheRequestAboutToBeMade(): void
    {
        $token = new AccessToken('abc', expires_at: 1000);

        $this->assertFalse($token->isExpired(60, 900));
        // Within the leeway: treated as expired, so a request made now does not race it.
        $this->assertTrue($token->isExpired(60, 950));
        $this->assertTrue($token->isExpired(60, 1000));
        $this->assertFalse((new AccessToken('abc'))->isExpired(), 'a token with no known expiry never expires');
    }

    public function testATokenSurvivesStorage(): void
    {
        $token = new AccessToken('abc', 'Bearer', 'def', 1000, ['identify'], 'jwt');

        $this->assertEquals($token, AccessToken::fromArray(json_decode(json_encode($token), true)));
    }

    public function testTheSecretsAreNeverDumped(): void
    {
        $dumped = print_r(new AccessToken('abc123', 'Bearer', 'def456', 1000, [], 'jwt789'), true);

        foreach (['abc123', 'def456', 'jwt789'] as $secret) {
            $this->assertStringNotContainsString($secret, $dumped);
        }
    }

    public function testAResponseWithoutATokenIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        AccessToken::fromResponse(['error' => 'invalid_grant']);
    }
}
