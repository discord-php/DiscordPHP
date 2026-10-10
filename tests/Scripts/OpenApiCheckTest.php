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

use Discord\Scripts\OpenApiCheck;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../scripts/OpenApiCheck.php';

/**
 * The OpenAPI check behind `composer openapi`, run on small specs and sources written here, so it needs
 * no network.
 */
final class OpenApiCheckTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'openapi-check-'.bin2hex(random_bytes(4));
        mkdir($this->directory.DIRECTORY_SEPARATOR.'src', 0777, true);
    }

    protected function tearDown(): void
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->directory);
    }

    public function testRoutesCompareAcrossTheSpecsAndTheEndpointsTemplates()
    {
        $this->assertSame('channels/{}/messages/{}', OpenApiCheck::route('/channels/{channel_id}/messages/{message_id}'));
        $this->assertSame('channels/{}/messages/{}', OpenApiCheck::route('channels/:channel_id/messages/:message_id'));
        $this->assertSame('partner-sdk/dms/{}/{}', OpenApiCheck::route('partner-sdk/dms/:user_id_1/:user_id_2'));
        $this->assertSame('skus/{}/subscriptions', OpenApiCheck::route('/skus/:sku_id/subscriptions'));
    }

    public function testOperationsDescribeTheirParametersBodyResponsesAndAuthorisation()
    {
        $operations = OpenApiCheck::operations($this->spec());
        $post = $operations['POST /channels/{channel_id}/messages'];

        $this->assertSame(['GET /channels/{channel_id}/messages', 'POST /channels/{channel_id}/messages', 'GET /channels/{channel_id}/pins'], array_keys($operations));
        $this->assertSame('channels/{}/messages', $post['route']);
        $this->assertSame(['path parameter channel_id' => 'SnowflakeType, required'], $post['parameters'], 'shared path parameters apply to every method');
        $this->assertSame(['request body' => 'MessageCreateRequest'], $post['body']);
        $this->assertSame(['response 200' => 'MessageResponse', 'response 204' => 'no content'], $post['responses']);
        $this->assertSame(['BotToken'], $post['auth']);
        $this->assertSame('integer (int32)', $operations['GET /channels/{channel_id}/messages']['parameters']['query parameter limit'], 'a parameter can be a reference');
        $this->assertSame(['none', 'BotToken'], $operations['GET /channels/{channel_id}/messages']['auth']);
        $this->assertTrue($operations['GET /channels/{channel_id}/pins']['deprecated'], 'Discord names deprecated operations rather than marking them');
    }

    public function testRequestsAreFoundInTablesCallsAndVariablesButNotComments()
    {
        file_put_contents($this->directory.'/src/ThingRepository.php', <<<'PHP'
            <?php
            class ThingRepository
            {
                protected $endpoints = [
                    'all' => Endpoint::THINGS,
                    'create' => Endpoint::THINGS,
                    'leave' => Endpoint::THING_MEMBER,
                ];

                public function leave($id)
                {
                    return $this->http->delete(Endpoint::bind($this->endpoints['leave'], $id));
                }

                public function archived($id)
                {
                    $endpoint = Endpoint::THING_ARCHIVE;
                    $endpoint = Endpoint::bind($endpoint, $id);

                    return $this->http->get(Endpoint::bind((string) $endpoint));
                }

                public function toggle($id, $on)
                {
                    $endpoint = $on
                        ? new Endpoint(Endpoint::THING_ON)
                        : new Endpoint(Endpoint::THING_OFF);

                    return $this->http->put($endpoint);
                }

                public function badge($id)
                {
                    $endpoint = Endpoint::bind(Endpoint::THING_BADGE, $id);

                    return $this->http->getDriver()->runRequest(new Request(new Deferred(), 'get', $endpoint, ''));
                }

                public function banner($id)
                {
                    return $driver->runRequest(new Request(new Deferred(), 'get', Endpoint::bind(Endpoint::THING_BANNER, $id), ''));
                }

                /** Its picture, as at Endpoint::THING_DOCUMENTED. */
                public function image($id)
                {
                    return 'https://discord.com/api/'.Endpoint::bind(Endpoint::THING_IMAGE, $id);
                }
            }
            PHP);

        $this->assertSame([
            'THINGS' => ['GET', 'POST'],
            'THING_ARCHIVE' => ['GET'],
            'THING_BADGE' => ['GET'],
            'THING_BANNER' => ['GET'],
            'THING_IMAGE' => ['?'],
            'THING_MEMBER' => ['DELETE'],
            'THING_OFF' => ['PUT'],
            'THING_ON' => ['PUT'],
        ], OpenApiCheck::endpointUses($this->directory.'/src'));
    }

    public function testCoverageTellsSentFromUnsentFromRoutesWithoutAConstant()
    {
        $operations = OpenApiCheck::operations($this->spec());
        $coverage = OpenApiCheck::coverage($operations, ['CHANNEL_MESSAGES' => 'channels/:channel_id/messages'], ['CHANNEL_MESSAGES' => ['POST', '?']]);

        $this->assertSame('unimplemented', $coverage['GET /channels/{channel_id}/messages']['status']);
        $this->assertSame('implemented', $coverage['POST /channels/{channel_id}/messages']['status']);
        $this->assertSame(['constants' => ['CHANNEL_MESSAGES'], 'sent' => ['POST', '?']], array_slice($coverage['GET /channels/{channel_id}/messages'], 1));
        $this->assertSame('no constant', $coverage['GET /channels/{channel_id}/pins']['status']);
        $this->assertSame(['GATEWAY' => 'gateway'], OpenApiCheck::unlistedConstants($operations, ['GATEWAY' => 'gateway', 'CHANNEL_MESSAGES' => 'channels/:channel_id/messages']));
    }

    public function testChangesToEndpointsAreListed()
    {
        $before = $this->spec();
        $after = $this->spec();
        unset($after['paths']['/channels/{channel_id}/pins']);
        $after['paths']['/channels/{channel_id}/messages']['get']['parameters'][] = ['name' => 'around', 'in' => 'query', 'schema' => ['$ref' => '#/components/schemas/SnowflakeType']];
        $after['paths']['/channels/{channel_id}/messages']['post']['responses']['201'] = ['description' => 'created'];
        $after['paths']['/gateway']['get'] = ['operationId' => 'get_gateway', 'responses' => ['200' => ['description' => 'ok']]];

        $this->assertSame([
            '+ GET /gateway  get_gateway',
            '- GET /channels/{channel_id}/pins  deprecated_list_pins',
            '~ GET /channels/{channel_id}/messages: query parameter around added (SnowflakeType)',
            '~ POST /channels/{channel_id}/messages: response 201 added (no content)',
        ], OpenApiCheck::operationChanges(OpenApiCheck::operations($before), OpenApiCheck::operations($after)));
    }

    public function testChangesToSchemasAreListedDownToEnumValues()
    {
        $before = $this->spec();
        $after = $this->spec();
        $after['components']['schemas']['MessageResponse']['properties']['nonce'] = ['type' => ['string', 'null']];
        unset($after['components']['schemas']['MessageResponse']['properties']['tts']);
        $after['components']['schemas']['MessageResponse']['required'][] = 'content';
        $after['components']['schemas']['ChannelTypes']['oneOf'][] = ['title' => 'GUILD_FORUM', 'const' => 15];
        $after['components']['schemas']['PollResponse'] = ['type' => 'object', 'properties' => []];
        unset($after['components']['schemas']['MessageCreateRequest']);

        $this->assertSame([
            '+ PollResponse',
            '- MessageCreateRequest',
            '~ MessageResponse: property nonce added (string or null)',
            '~ MessageResponse: property tts removed',
            '~ MessageResponse: property content is now required',
            '~ ChannelTypes: value GUILD_FORUM = 15 added',
        ], OpenApiCheck::schemaChanges($before, $after));
    }

    public function testAReportFailsOnlyOnWhatTheBaselineDoesNotKnow()
    {
        file_put_contents($this->directory.'/spec.json', json_encode($this->spec()));
        file_put_contents($this->directory.'/src/Channel.php', '<?php return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_MESSAGES, $id), $body);');
        $baseline = $this->directory.'/baseline.json';
        file_put_contents($baseline, json_encode(['unimplemented' => [
            'GET /channels/{channel_id}/pins' => 'Deprecated.',
            'GET /channels/{channel_id}/messages' => 'Not implemented yet.',
        ]]));
        $run = fn (): int => OpenApiCheck::run(['--spec='.$this->directory.'/spec.json', '--all'], $baseline, $this->directory.'/src', $this->directory.'/cache');

        ob_start();
        $status = $run();
        $report = (string) ob_get_clean();

        $this->assertSame(0, $status, $report);
        $this->assertStringContainsString('3 operations: 1 sent by DiscordPHP, 2 not; 0 of those are new.', $report);
        $this->assertStringContainsString('GET /channels/{channel_id}/pins  deprecated_list_pins  (Endpoint::CHANNEL_PINS is never used, deprecated)', $report);
        $this->assertStringContainsString('    Deprecated.', $report, 'the baseline says why');

        file_put_contents($baseline, json_encode(['unimplemented' => ['GET /channels/{channel_id}/pins' => 'Deprecated.']]));
        ob_start();
        $status = $run();
        $report = (string) ob_get_clean();

        $this->assertSame(1, $status, $report);
        $this->assertStringContainsString("Not sent by DiscordPHP, and new since the baseline (1)\n  GET /channels/{channel_id}/messages  list_messages  (Endpoint::CHANNEL_MESSAGES is only sent POST)", $report);
    }

    public function testWhatOnlyThePreviewDescribesIsMarked()
    {
        $baseline = $this->spec();
        $stable = $this->spec();
        $stable['components']['schemas']['MessageResponse']['properties']['pinned'] = ['type' => 'boolean'];
        $preview = $stable;
        $preview['paths']['/guilds/{guild_id}/messages/search']['get'] = ['operationId' => 'search_guild_messages', 'responses' => ['200' => ['description' => 'ok']]];
        $preview['paths']['/channels/{channel_id}/messages']['get']['parameters'][] = ['name' => 'contents', 'in' => 'query', 'schema' => ['type' => 'string']];
        $preview['components']['schemas']['ChannelTypes']['oneOf'][] = ['title' => 'GUILD_MEDIA', 'const' => 16];
        $preview['components']['schemas']['MessageResponse']['properties']['tts'] = ['type' => ['boolean', 'null']];
        $preview['components']['schemas']['SearchResponse'] = ['type' => 'object', 'properties' => ['total' => ['type' => 'integer']]];
        unset($preview['components']['schemas']['MessageCreateRequest']);

        $previewOnly = OpenApiCheck::previewOnly($stable, $preview);
        $this->assertSame([
            'GET /guilds/{guild_id}/messages/search',
            'GET /channels/{channel_id}/messages: query parameter contents',
            'SearchResponse',
            'MessageResponse: property tts',
            'ChannelTypes: value GUILD_MEDIA = 16',
        ], array_keys($previewOnly), 'what the preview leaves out is not counted as only in the preview');

        // The schema the preview alone has changes too, which is still only in the preview.
        $later = $preview;
        $later['components']['schemas']['SearchResponse']['properties']['messages'] = ['type' => 'array'];

        $this->assertSame([
            '+ GET /guilds/{guild_id}/messages/search  search_guild_messages  [preview only]',
            '~ GET /channels/{channel_id}/messages: query parameter contents added (string)  [preview only]',
        ], OpenApiCheck::markPreview(OpenApiCheck::operationChanges(OpenApiCheck::operations($baseline), OpenApiCheck::operations($later)), OpenApiCheck::previewOnly($stable, $later)));
        $this->assertSame([
            '+ SearchResponse  [preview only]',
            '- MessageCreateRequest',
            '~ MessageResponse: property pinned added (boolean)',
            '~ MessageResponse: property tts changed from boolean to boolean or null  [preview only]',
            '~ ChannelTypes: value GUILD_MEDIA = 16 added  [preview only]',
        ], OpenApiCheck::markPreview(OpenApiCheck::schemaChanges($baseline, $later), OpenApiCheck::previewOnly($stable, $later)));
    }

    public function testWhatReachesTheStableEditionIsListedSoItsPreviewFlagCanGo()
    {
        $before = [
            'GET /gateway' => '+ GET /gateway  get_gateway',
            'ChannelTypes: value GUILD_MEDIA = 16' => '~ ChannelTypes: value GUILD_MEDIA = 16 added',
            'MessageResponse: property tts' => '~ MessageResponse: property tts added (boolean)',
            'SearchResponse' => '+ SearchResponse',
            'PollResponse' => '+ PollResponse',
        ];
        $after = ['PollResponse' => '+ PollResponse'];

        $this->assertSame(
            ['GET /gateway', 'ChannelTypes: value GUILD_MEDIA = 16'],
            OpenApiCheck::promoted($before, $after, ['~ MessageResponse: property tts changed from boolean to string', '- SearchResponse']),
            'what the preview changed or removed is already reported',
        );
    }

    public function testAReportMarksWhatIsOnlyInThePreviewAndSaysNotToRelyOnIt()
    {
        $preview = $this->spec();
        $preview['paths']['/guilds/{guild_id}/messages/search']['get'] = ['operationId' => 'search_guild_messages', 'responses' => ['200' => ['description' => 'ok']]];
        file_put_contents($this->directory.'/stable.json', json_encode($this->spec()));
        file_put_contents($this->directory.'/preview.json', json_encode($preview));
        file_put_contents($this->directory.'/src/Channel.php', '<?php return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_MESSAGES, $id), $body);');
        $baseline = $this->directory.'/baseline.json';
        file_put_contents($baseline, json_encode(['unimplemented' => [
            'GET /channels/{channel_id}/pins' => 'Deprecated.',
            'GET /channels/{channel_id}/messages' => 'Not implemented yet.',
        ]]));

        ob_start();
        $status = OpenApiCheck::run(['--spec='.$this->directory.'/preview.json', '--stable='.$this->directory.'/stable.json'], $baseline, $this->directory.'/src', $this->directory.'/cache');
        $report = (string) ob_get_clean();

        $this->assertSame(1, $status, $report);
        $this->assertStringContainsString('1 of the operations, and 0 other details, are only in the preview, not the stable edition.', $report);
        $this->assertStringContainsString('GET /guilds/{guild_id}/messages/search  search_guild_messages  (Endpoint::GUILD_MESSAGES_SEARCH is never used)  [preview only]', $report);
        $this->assertStringContainsString('[preview only] marks what Discord describes only in the preview (specs/openapi_preview.json), not the stable edition (specs/openapi.json). It may change or be removed at any time', $report);

        ob_start();
        OpenApiCheck::run(['--spec='.$this->directory.'/stable.json'], $baseline, $this->directory.'/src', $this->directory.'/cache');
        $report = (string) ob_get_clean();

        $this->assertStringNotContainsString('[preview only]', $report, 'without the stable edition, nothing can be told to be only in the preview');
    }

    /**
     * A small spec in the shape of Discord's.
     *
     * @return array<string, mixed>
     */
    private function spec(): array
    {
        return [
            'openapi' => '3.1.0',
            'paths' => [
                '/channels/{channel_id}/messages' => [
                    'parameters' => [['name' => 'channel_id', 'in' => 'path', 'required' => true, 'schema' => ['$ref' => '#/components/schemas/SnowflakeType']]],
                    'get' => [
                        'operationId' => 'list_messages',
                        'parameters' => [['$ref' => '#/components/parameters/Limit']],
                        'responses' => ['200' => ['description' => 'ok', 'content' => ['application/json' => ['schema' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/MessageResponse']]]]]],
                        'security' => [[], ['BotToken' => []]],
                    ],
                    'post' => [
                        'operationId' => 'create_message',
                        'requestBody' => ['content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/MessageCreateRequest']]]],
                        'responses' => [
                            '200' => ['description' => 'ok', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/MessageResponse']]]],
                            '204' => ['description' => 'nothing'],
                        ],
                        'security' => [['BotToken' => []]],
                    ],
                ],
                '/channels/{channel_id}/pins' => [
                    'get' => ['operationId' => 'deprecated_list_pins', 'responses' => ['200' => ['description' => 'ok']]],
                ],
            ],
            'components' => [
                'parameters' => ['Limit' => ['name' => 'limit', 'in' => 'query', 'schema' => ['type' => 'integer', 'format' => 'int32']]],
                'schemas' => [
                    'SnowflakeType' => ['type' => 'string'],
                    'MessageResponse' => ['type' => 'object', 'properties' => ['id' => ['$ref' => '#/components/schemas/SnowflakeType'], 'content' => ['type' => 'string'], 'tts' => ['type' => 'boolean']], 'required' => ['id']],
                    'MessageCreateRequest' => ['type' => 'object', 'properties' => ['content' => ['type' => 'string']]],
                    'ChannelTypes' => ['type' => 'integer', 'oneOf' => [['title' => 'GUILD_TEXT', 'const' => 0], ['title' => 'DM', 'const' => 1]]],
                ],
            ],
        ];
    }
}
