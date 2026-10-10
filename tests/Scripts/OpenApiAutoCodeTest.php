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

use Discord\Scripts\OpenApiAutoCode;
use Discord\Scripts\OpenApiAutoCodePatcher;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../scripts/OpenApiAutoCode.php';

final class OpenApiAutoCodeTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'openapi-autocode-'.bin2hex(random_bytes(4));
        mkdir($this->directory.DIRECTORY_SEPARATOR.'Parts', 0o777, true);
    }

    protected function tearDown(): void
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->directory);
    }

    public function testItAddsOnlyNewUnformattedScalarResponseFields(): void
    {
        file_put_contents($this->directory.'/Parts/Message.php', <<<'PHP'
            <?php
            /**
             * Message.
             *
             * @property string $id The identifier.
             */
            class Message extends Part
            {
                protected $fillable = [
                    'id',

                    // @internal
                    'channel_id',
                ];
            }
            PHP);

        $before = ['components' => ['schemas' => ['MessageResponse' => [
            'type' => 'object',
            'properties' => ['id' => ['type' => 'string']],
        ]]]];
        $after = ['components' => ['schemas' => ['MessageResponse' => [
            'type' => 'object',
            'required' => ['id', 'pinned'],
            'properties' => [
                'id' => ['type' => 'string'],
                'pinned' => ['type' => 'boolean'],
                'future_field' => ['type' => ['integer', 'null']],
                'child' => ['$ref' => '#/components/schemas/ChildResponse'],
                'children' => ['type' => 'array', 'items' => ['type' => 'string']],
                'kind' => ['type' => 'string', 'enum' => ['a', 'b']],
                'timestamp' => ['type' => 'string', 'format' => 'date-time'],
            ],
        ]]]];

        $result = OpenApiAutoCode::generate($before, $after, $this->directory, [
            'MessageResponse' => ['file' => 'Parts/Message.php'],
        ]);

        $this->assertSame(['Parts/Message.php::$future_field', 'Parts/Message.php::$pinned'], $result['added']);
        $this->assertCount(4, $result['skipped']);
        $this->assertMatchesRegularExpression('/@property bool\s+\$pinned/', $result['files']['Parts/Message.php']);
        $this->assertStringContainsString('@property int|null $future_field', $result['files']['Parts/Message.php']);
        $this->assertStringContainsString("'pinned',", $result['files']['Parts/Message.php']);
        $this->assertStringContainsString("'future_field',", $result['files']['Parts/Message.php']);
        $this->assertStringContainsString('// @internal', $result['files']['Parts/Message.php']);
        $this->assertSame(1, substr_count($result['files']['Parts/Message.php'], "'future_field'"));
    }

    public function testItDoesNotOverwriteExistingCustomModelFieldsOrRepeatChanges(): void
    {
        $source = <<<'PHP'
            <?php
            /**
             * Message.
             *
             * @property string|null $extra Existing custom behavior.
             */
            class Message extends Part
            {
                protected $fillable = [
                    'extra',
                ];
            }
            PHP;
        file_put_contents($this->directory.'/Parts/Message.php', $source);
        $before = ['components' => ['schemas' => ['MessageResponse' => ['type' => 'object', 'properties' => []]]]];
        $after = ['components' => ['schemas' => ['MessageResponse' => ['type' => 'object', 'properties' => ['extra' => ['type' => 'string']]]]]];

        $result = OpenApiAutoCode::generate($before, $after, $this->directory, [
            'MessageResponse' => ['file' => 'Parts/Message.php'],
        ]);

        $this->assertSame([], $result['files']);
        $this->assertSame(['MessageResponse.extra: already documented or fillable; left for a human to reconcile'], $result['skipped']);
        $this->assertSame($source, file_get_contents($this->directory.'/Parts/Message.php'));
    }

    public function testItAddsFieldsWhenTheFillableArrayHasNoInternalMarker(): void
    {
        $source = <<<'PHP'
            <?php
            /**
             * Message.
             *
             * @property string $id The identifier.
             */
            class Message extends Part
            {
                protected $fillable = [
                    'id',
                ];
            }
            PHP;

        $patched = OpenApiAutoCodePatcher::patchSource($source, ['new_field' => 'string|null']);

        $this->assertStringContainsString("'id',\n        'new_field',\n", $patched);
        $this->assertStringContainsString('@property string|null $new_field', $patched);
        $this->assertSame(1, substr_count($patched, "'new_field'"));
    }

    public function testItRejectsMappingsOutsideTheSourceTree(): void
    {
        $spec = ['components' => ['schemas' => ['MessageResponse' => ['type' => 'object', 'properties' => ['extra' => ['type' => 'string']]]]]];
        $result = OpenApiAutoCode::generate([], $spec, $this->directory, [
            'MessageResponse' => ['file' => '../outside.php'],
        ]);

        $this->assertSame([], $result['files']);
        $this->assertSame(['MessageResponse: mapped source file is missing or outside src/'], $result['skipped']);
    }
}
