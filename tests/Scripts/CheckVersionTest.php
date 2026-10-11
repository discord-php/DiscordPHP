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

use PHPUnit\Framework\Attributes\TestWith;

/**
 * Exercises the CLI in temporary repositories without network access or changing real release tags.
 */
final class CheckVersionTest extends DiscordTestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'discord-version-'.bin2hex(random_bytes(8));
        mkdir($this->directory.'/scripts', 0777, true);
        mkdir($this->directory.'/src/Discord', 0777, true);
        copy(__DIR__.'/../../scripts/check-version.php', $this->directory.'/scripts/check-version.php');
        $this->writeVersion('v10.66.3');
        $this->git(['init', '--quiet']);
        $this->git(['-c', 'user.name=Version Test', '-c', 'user.email=version-test@example.invalid', '-c', 'commit.gpgsign=false', 'commit', '--allow-empty', '--quiet', '-m', 'Fixture']);
    }

    protected function tearDown(): void
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            // Git objects can be read-only on Windows.
            chmod($file->getPathname(), 0777);
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->directory);
    }

    #[TestWith(['v10.66.3', ['v10.66.3'], 0, 'not older than the latest release, v10.66.3', false], 'current version')]
    #[TestWith(['v10.64.0', ['v10.66.3'], 1, 'older than the latest release, v10.66.3', false], 'stale version')]
    #[TestWith(['v10.10.0', ['v10.9.0', 'v10.66.3'], 1, 'latest release, v10.66.3', false], 'numeric sorting')]
    #[TestWith(['v10.67.0', ['v10.66.3', 'v11.0.0-RC.1', 'v99-invalid'], 0, 'latest release, v10.66.3', false], 'future version and ignored tags')]
    #[TestWith(['v10.66.3', ['v10.67.0'], 1, 'latest release, v10.67.0', true], 'another working directory')]
    #[TestWith(['v10.66.3', ['v11.0.0-RC.1'], 2, 'No stable release tags', false], 'only prerelease tags')]
    #[TestWith(['v10.66.3', [], 2, 'No stable release tags', false], 'no tags')]
    public function testStableTagChecks(string $version, array $tags, int $expectedStatus, string $expectedMessage, bool $elsewhere): void
    {
        $this->writeVersion($version);
        foreach ($tags as $tag) {
            $this->git(['tag', $tag]);
        }
        [$status, $output] = $this->check([], $elsewhere ? sys_get_temp_dir() : null);
        $this->assertSame($expectedStatus, $status, $output);
        $this->assertStringContainsString($expectedMessage, $output);
    }

    public function testPublishedReleaseMustMatchExactly(): void
    {
        [$status, $output] = $this->check(['v10.66.3']);
        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString('matches the release, v10.66.3', $output);
        [$status, $output] = $this->check(['v10.67.0']);
        $this->assertSame(1, $status, $output);
        $this->assertStringContainsString('this release is v10.67.0', $output);
    }

    public function testMissingVersionConstantCannotPass(): void
    {
        file_put_contents($this->directory.'/src/Discord/Discord.php', '<?php class Discord {}');
        [$status, $output] = $this->check(['v10.66.3']);
        $this->assertSame(2, $status, $output);
        $this->assertStringContainsString('Could not find VERSION', $output);
    }

    private function writeVersion(string $version): void
    {
        file_put_contents($this->directory.'/src/Discord/Discord.php', "<?php class Discord { public const VERSION = '{$version}'; }");
    }

    private function git(array $arguments): void
    {
        [$status, $output] = $this->runCommand(array_merge(['git', '-C', $this->directory, '-c', 'core.hooksPath='.$this->directory.'/no-hooks'], $arguments));
        $this->assertSame(0, $status, $output);
    }

    private function check(array $arguments = [], ?string $cwd = null): array
    {
        return $this->runCommand(array_merge([PHP_BINARY, $this->directory.'/scripts/check-version.php'], $arguments), $cwd);
    }

    private function runCommand(array $command, ?string $cwd = null): array
    {
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd ?? $this->directory);
        $this->assertIsResource($process);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $output];
    }
}
