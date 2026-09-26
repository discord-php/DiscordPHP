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

namespace Discord\Scripts;

use Discord\Http\Endpoint;

/**
 * Checks DiscordPHP against Discord's own OpenAPI description of its HTTP API.
 *
 * Discord publishes the description at https://github.com/discord/discord-api-spec. This reads its preview
 * edition, which also covers endpoints that are still being rolled out, and answers three questions:
 *
 * - which operations DiscordPHP never sends a request for;
 * - which routes have no {@see Endpoint} constant in discord-php/http; and
 * - what Discord has changed since the commit recorded in the baseline: its endpoints, their parameters
 *   and responses, and the schemas of the objects they carry, down to new enum values.
 *
 * The baseline, `scripts/openapi-baseline.json`, also lists the gaps already known, with the reason for
 * each, so that only something new fails the check. `scripts/openapi-check.php` runs it, as
 * `composer openapi`; `composer openapi:update` records the live spec and today's gaps as the new baseline.
 */
final class OpenApiCheck
{
    /** The repository Discord publishes the description in. */
    public const REPOSITORY = 'discord/discord-api-spec';

    /** The preview edition, within that repository. */
    public const FILE = 'specs/openapi_preview.json';

    /** The reason recorded for a gap the baseline has not explained yet. */
    public const UNEXPLAINED = 'Not implemented yet.';

    /** The request each key of a repository's `$endpoints` table sends, as AbstractRepositoryTrait uses it. */
    private const REPOSITORY_KEYS = ['all' => 'GET', 'get' => 'GET', 'create' => 'POST', 'update' => 'PATCH', 'delete' => 'DELETE'];

    private const METHODS = ['get', 'post', 'put', 'patch', 'delete'];

    /**
     * Runs the check, printing a report, and returns the exit status: 0 when there is nothing new since
     * the baseline, 1 when there is, and 2 when the check could not run.
     *
     * @param list<string> $arguments The command-line arguments, without the script's name.
     * @param string       $baseline  The baseline file.
     * @param string       $source    DiscordPHP's `src` directory.
     * @param string       $cache     Where downloaded editions of the spec are kept.
     */
    public static function run(array $arguments, string $baseline, string $source, string $cache): int
    {
        $options = ['all' => false, 'update' => false, 'spec' => null];

        foreach ($arguments as $argument) {
            if ('--all' === $argument || '--update' === $argument) {
                $options[substr($argument, 2)] = true;
            } elseif (str_starts_with($argument, '--spec=')) {
                $options['spec'] = substr($argument, 7);
            } else {
                fwrite(STDERR, ('--help' === $argument || '-h' === $argument ? '' : "Unknown option {$argument}.\n").self::usage());

                return '--help' === $argument || '-h' === $argument ? 0 : 2;
            }
        }

        if ($options['update'] && null !== $options['spec']) {
            fwrite(STDERR, "--update records the live spec's commit, so it cannot be used with --spec.\n");

            return 2;
        }

        try {
            return self::check($options, $baseline, $source, $cache);
        } catch (\RuntimeException|\JsonException $e) {
            fwrite(STDERR, $e->getMessage()."\n");

            return 2;
        }
    }

    /**
     * A route with its parameters blanked out, so that the spec's `{channel_id}` and an Endpoint's
     * `:channel_id` compare equal.
     */
    public static function route(string $path): string
    {
        return (string) preg_replace(['/\{[^}]+\}/', '/:[A-Za-z0-9_]+/'], '{}', trim($path, '/'));
    }

    /**
     * The operations a spec describes, keyed by method and path, such as `POST /channels/{channel_id}/messages`.
     *
     * Each has its operation ID, the authorisations that may call it (`BotToken`, `OAuth2`, or `none`), and
     * short descriptions of its parameters, request body and responses, which are what two editions of the
     * spec are compared by.
     *
     * @param array<string, mixed> $spec A decoded OpenAPI document.
     *
     * @return array<string, array{method: string, path: string, route: string, id: string, auth: list<string>, deprecated: bool, parameters: array<string, string>, body: array<string, string>, responses: array<string, string>}>
     */
    public static function operations(array $spec): array
    {
        $operations = [];

        foreach ($spec['paths'] ?? [] as $path => $item) {
            foreach (self::METHODS as $method) {
                if (! isset($item[$method])) {
                    continue;
                }

                $operation = $item[$method];
                $parameters = [];

                // An operation's own parameters replace the path's shared ones of the same name.
                foreach ([...($item['parameters'] ?? []), ...($operation['parameters'] ?? [])] as $parameter) {
                    $parameter = self::resolve($spec, $parameter);
                    $label = ($parameter['in'] ?? 'unplaced').' parameter '.($parameter['name'] ?? 'unnamed');
                    $parameters[$label] = self::describe($parameter['schema'] ?? []).(empty($parameter['required']) ? '' : ', required');
                }

                $body = [];
                if (isset($operation['requestBody'])) {
                    $content = self::resolve($spec, $operation['requestBody'])['content'] ?? [];
                    $body['request body'] = self::describe(($content['application/json'] ?? reset($content) ?: [])['schema'] ?? []);
                }

                $responses = [];
                foreach ($operation['responses'] ?? [] as $status => $response) {
                    $content = self::resolve($spec, $response)['content'] ?? [];
                    $responses["response {$status}"] = [] === $content ? 'no content' : self::describe(($content['application/json'] ?? reset($content))['schema'] ?? []);
                }

                $auth = [];
                foreach ($operation['security'] ?? $spec['security'] ?? [] as $requirement) {
                    $auth[] = [] === $requirement ? 'none' : implode(' + ', array_keys($requirement));
                }

                $id = (string) ($operation['operationId'] ?? '');
                $operations[strtoupper($method).' '.$path] = [
                    'method' => strtoupper($method),
                    'path' => $path,
                    'route' => self::route($path),
                    'id' => $id,
                    'auth' => $auth,
                    // The spec marks none with `deprecated`; Discord names them instead.
                    'deprecated' => ! empty($operation['deprecated']) || str_starts_with($id, 'deprecated_'),
                    'parameters' => $parameters,
                    'body' => $body,
                    'responses' => $responses,
                ];
            }
        }

        return $operations;
    }

    /**
     * The routes discord-php/http has constants for.
     *
     * @return array<string, string> Each constant's name, and its route template.
     */
    public static function endpointConstants(): array
    {
        $constants = [];

        foreach ((new \ReflectionClass(Endpoint::class))->getConstants() as $name => $value) {
            if (is_string($value) && 'REGEX' !== $name) {
                $constants[$name] = $value;
            }
        }

        return $constants;
    }

    /**
     * The requests DiscordPHP sends to each Endpoint constant, found by reading its source.
     *
     * A request is recognised in a repository's `$endpoints` table, in a call such as
     * `->post(Endpoint::bind(Endpoint::X, …))`, or where the endpoint is kept in a variable and sent a few
     * lines later. A use whose request cannot be told, such as building an image's URL, is recorded as `?`.
     *
     * @param string $directory The source directory to read.
     *
     * @return array<string, list<string>> Each constant's name, and the methods it is sent with.
     */
    public static function endpointUses(string $directory): array
    {
        $uses = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if ('php' !== $file->getExtension()) {
                continue;
            }

            $source = self::withoutComments((string) file_get_contents($file->getPathname()));
            preg_match_all('/Endpoint::([A-Z][A-Z0-9_]*)\b/', $source, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[1] as $index => [$name]) {
                foreach (self::methodsAt($source, $matches[0][$index][1]) as $method) {
                    $uses[$name][$method] = true;
                }
            }
        }

        ksort($uses);

        return array_map(static fn (array $methods): array => array_keys($methods), $uses);
    }

    /**
     * How DiscordPHP covers each operation: `implemented` when it sends the operation's method to a constant
     * for its route, `unimplemented` when it has a constant but never sends that, and `no constant` when
     * discord-php/http has none for the route.
     *
     * @param array<string, array{method: string, route: string}> $operations From {@see OpenApiCheck::operations()}.
     * @param array<string, string>                               $constants  From {@see OpenApiCheck::endpointConstants()}.
     * @param array<string, list<string>>                         $uses       From {@see OpenApiCheck::endpointUses()}.
     *
     * @return array<string, array{status: string, constants: list<string>, sent: list<string>}>
     */
    public static function coverage(array $operations, array $constants, array $uses): array
    {
        $byRoute = [];
        foreach ($constants as $name => $template) {
            $byRoute[self::route($template)][] = $name;
        }

        $coverage = [];
        foreach ($operations as $key => $operation) {
            $names = $byRoute[$operation['route']] ?? [];
            $sent = [];

            foreach ($names as $name) {
                foreach ($uses[$name] ?? [] as $method) {
                    $sent[$method] = true;
                }
            }

            $coverage[$key] = [
                'status' => [] === $names ? 'no constant' : (isset($sent[$operation['method']]) ? 'implemented' : 'unimplemented'),
                'constants' => $names,
                'sent' => array_keys($sent),
            ];
        }

        return $coverage;
    }

    /**
     * Endpoint constants for routes the spec does not describe: removed from the API, or never documented.
     *
     * @param array<string, array{route: string}> $operations From {@see OpenApiCheck::operations()}.
     * @param array<string, string>               $constants  From {@see OpenApiCheck::endpointConstants()}.
     *
     * @return array<string, string> Each constant's name, and its route template.
     */
    public static function unlistedConstants(array $operations, array $constants): array
    {
        $routes = array_flip(array_column($operations, 'route'));

        return array_filter($constants, static fn (string $template): bool => ! isset($routes[self::route($template)]));
    }

    /**
     * What changed in the operations between two editions of the spec, one line each: `+` for an operation
     * added, `-` for one removed, and `~` for a change to one.
     *
     * @param array<string, array> $before From {@see OpenApiCheck::operations()}, for the older edition.
     * @param array<string, array> $after  The same for the newer one.
     *
     * @return list<string>
     */
    public static function operationChanges(array $before, array $after): array
    {
        $lines = [];

        foreach (array_diff_key($after, $before) as $key => $operation) {
            $lines[] = "+ {$key}  {$operation['id']}";
        }

        foreach (array_diff_key($before, $after) as $key => $operation) {
            $lines[] = "- {$key}  {$operation['id']}";
        }

        foreach (array_intersect_key($after, $before) as $key => $operation) {
            $was = $before[$key];
            $differences = [];

            foreach (['parameters', 'body', 'responses'] as $field) {
                $differences = [...$differences, ...self::mapChanges($was[$field], $operation[$field])];
            }

            if ($operation['auth'] !== $was['auth']) {
                $differences[] = 'authorisation changed from '.implode(', ', $was['auth']).' to '.implode(', ', $operation['auth']);
            }

            if ($operation['deprecated'] && ! $was['deprecated']) {
                $differences[] = 'deprecated';
            }

            if ($operation['id'] !== $was['id']) {
                $differences[] = "renamed from {$was['id']} to {$operation['id']}";
            }

            foreach ($differences as $difference) {
                $lines[] = "~ {$key}: {$difference}";
            }
        }

        return $lines;
    }

    /**
     * What changed in the schemas of the objects the API carries between two editions of the spec, one line
     * each: schemas added and removed, properties added, removed, retyped or made required or optional,
     * enum values added and removed, and other changes of shape.
     *
     * @param array<string, mixed> $before The older edition.
     * @param array<string, mixed> $after  The newer one.
     *
     * @return list<string>
     */
    public static function schemaChanges(array $before, array $after): array
    {
        $old = $before['components']['schemas'] ?? [];
        $new = $after['components']['schemas'] ?? [];
        $lines = [];

        foreach (array_diff_key($new, $old) as $name => $schema) {
            $lines[] = "+ {$name}";
        }

        foreach (array_diff_key($old, $new) as $name => $schema) {
            $lines[] = "- {$name}";
        }

        foreach (array_intersect_key($new, $old) as $name => $schema) {
            foreach (self::schemaDifferences($old[$name], $schema) as $difference) {
                $lines[] = "~ {$name}: {$difference}";
            }
        }

        return $lines;
    }

    /**
     * A short description of a schema: the name of the one it refers to, or its type.
     *
     * @param mixed $schema A schema from the spec.
     */
    public static function describe(mixed $schema): string
    {
        if (! is_array($schema) || [] === $schema) {
            return 'anything';
        }

        if (isset($schema['$ref'])) {
            return substr((string) strrchr('/'.$schema['$ref'], '/'), 1);
        }

        if (null !== ($values = self::enumValues($schema))) {
            return 'one of '.implode(', ', array_map(static fn ($value): string => (string) json_encode($value), $values));
        }

        foreach (['oneOf' => ' or ', 'anyOf' => ' or ', 'allOf' => ' and '] as $key => $glue) {
            if (isset($schema[$key]) && is_array($schema[$key])) {
                return implode($glue, array_map(self::describe(...), $schema[$key]));
            }
        }

        $type = $schema['type'] ?? 'anything';
        $type = is_array($type) ? implode(' or ', $type) : (string) $type;

        if ('array' === $type) {
            return self::describe($schema['items'] ?? []).'[]';
        }

        if ('object' === $type && isset($schema['properties'])) {
            return 'object {'.implode(', ', array_keys($schema['properties'])).'}';
        }

        return isset($schema['format']) ? "{$type} ({$schema['format']})" : $type;
    }

    /**
     * Compares and reports.
     *
     * @param array{all: bool, update: bool, spec: ?string} $options
     */
    private static function check(array $options, string $baselineFile, string $source, string $cache): int
    {
        $baseline = is_file($baselineFile) ? self::decode((string) file_get_contents($baselineFile), $baselineFile) : [];
        $known = $baseline['unimplemented'] ?? [];

        if (null !== $options['spec']) {
            if (! is_file($options['spec'])) {
                throw new \RuntimeException("There is no file {$options['spec']}.");
            }

            $latest = null;
            $spec = self::decode((string) file_get_contents($options['spec']), $options['spec']);
            echo "Discord's OpenAPI description, from {$options['spec']}\n";
        } else {
            $latest = self::latestCommit();
            $spec = self::spec($latest['sha'], $cache);
            echo "Discord's OpenAPI description (preview): commit ".substr($latest['sha'], 0, 7)." of {$latest['date']}\n";
        }

        $operations = self::operations($spec);
        $constants = self::endpointConstants();
        $coverage = self::coverage($operations, $constants, self::endpointUses($source));
        $gaps = array_filter($coverage, static fn (array $entry): bool => 'implemented' !== $entry['status']);
        $newGaps = array_diff_key($gaps, $known);
        $closed = array_diff_key($known, $gaps);

        $changes = null;
        if (isset($baseline['commit'])) {
            echo 'Baseline: commit '.substr($baseline['commit'], 0, 7).' of '.($baseline['date'] ?? 'an unknown date')."\n";

            if ($baseline['commit'] !== ($latest['sha'] ?? null)) {
                $old = self::spec($baseline['commit'], $cache);
                $changes = [
                    'endpoints' => self::operationChanges(self::operations($old), $operations),
                    'schemas' => self::schemaChanges($old, $spec),
                ];
            }
        } else {
            echo "Baseline: none yet, so every gap counts as new. Run composer openapi:update to record one.\n";
        }

        $sent = count($operations) - count($gaps);
        echo "\n".count($operations)." operations: {$sent} sent by DiscordPHP, ".count($gaps).' not; '.count($newGaps)." of those are new.\n";

        if (null === $changes) {
            echo isset($baseline['commit']) ? "The spec has not changed since the baseline.\n" : '';
        } elseif ([] === $changes['endpoints'] && [] === $changes['schemas']) {
            echo "The spec has had commits since the baseline, but its endpoints and schemas are unchanged.\n";
        } else {
            self::section('Endpoints changed since the baseline', $changes['endpoints']);
            self::section('Schemas changed since the baseline', $changes['schemas']);
        }

        self::section('Not sent by DiscordPHP, and new since the baseline', self::gapLines($newGaps, $operations));
        self::section('In the baseline, but now sent by DiscordPHP or gone from the spec', array_keys(self::sorted($closed)));

        if ($options['all']) {
            self::section('Not sent by DiscordPHP, as the baseline knows', self::gapLines(array_intersect_key($gaps, $known), $operations, $known));

            $unlisted = [];
            foreach (self::unlistedConstants($operations, $constants) as $name => $template) {
                $unlisted[] = "Endpoint::{$name}  {$template}";
            }
            self::section('Endpoint constants for routes the spec does not describe', $unlisted);
        }

        if ($options['update']) {
            self::writeBaseline($baselineFile, $latest, $gaps, $known);
            echo "\nRecorded commit ".substr($latest['sha'], 0, 7).' and its '.count($gaps)." gaps in {$baselineFile}.\n";

            return 0;
        }

        $new = [] !== $newGaps || (null !== $changes && ([] !== $changes['endpoints'] || [] !== $changes['schemas']));
        if ($new) {
            echo "\nOnce these are dealt with, composer openapi:update records them in the baseline.\n";
        }

        return $new ? 1 : 0;
    }

    /**
     * The lines listing unimplemented operations, each with why it counts as one, and the baseline's reason
     * where there is one.
     *
     * @param array<string, array{status: string, constants: list<string>, sent: list<string>}> $gaps
     * @param array<string, array{id: string, auth: list<string>}>                              $operations
     * @param array<string, string>                                                             $reasons
     *
     * @return list<string>
     */
    private static function gapLines(array $gaps, array $operations, array $reasons = []): array
    {
        $lines = [];

        foreach (self::sorted($gaps) as $key => $entry) {
            $operation = $operations[$key];
            $why = match (true) {
                [] === $entry['constants'] => 'no Endpoint constant',
                [] !== array_diff($entry['sent'], ['?']) => self::names($entry['constants']).' is only sent '.implode(', ', array_diff($entry['sent'], ['?'])),
                in_array('?', $entry['sent'], true) => self::names($entry['constants']).' is used, but not to send a request',
                default => self::names($entry['constants']).' is never used',
            };
            $auth = ['OAuth2'] === $operation['auth'] ? ', OAuth2 only' : '';
            $deprecated = $operation['deprecated'] ? ', deprecated' : '';
            $lines[] = "{$key}  {$operation['id']}  ({$why}{$auth}{$deprecated})";

            if (isset($reasons[$key]) && self::UNEXPLAINED !== $reasons[$key]) {
                $lines[] = '    '.$reasons[$key];
            }
        }

        return $lines;
    }

    /**
     * The latest commit that changed the preview spec.
     *
     * @return array{sha: string, date: string}
     */
    private static function latestCommit(): array
    {
        $commits = self::decode(self::download('https://api.github.com/repos/'.self::REPOSITORY.'/commits?per_page=1&path='.rawurlencode(self::FILE), true), 'GitHub\'s list of commits');

        if (! isset($commits[0]['sha']) || ! is_string($commits[0]['sha'])) {
            throw new \RuntimeException('GitHub did not name the latest commit of '.self::FILE.'.');
        }

        return ['sha' => $commits[0]['sha'], 'date' => substr((string) ($commits[0]['commit']['committer']['date'] ?? ''), 0, 10)];
    }

    /**
     * The spec as of a commit. Each edition is downloaded once, then read from the cache.
     *
     * @return array<string, mixed>
     */
    private static function spec(string $sha, string $cache): array
    {
        if (1 !== preg_match('/^[0-9a-f]{40}$/', $sha)) {
            throw new \RuntimeException("{$sha} is not a commit SHA.");
        }

        $file = $cache.DIRECTORY_SEPARATOR.$sha.'.json';

        if (! is_file($file)) {
            $json = self::download('https://raw.githubusercontent.com/'.self::REPOSITORY."/{$sha}/".self::FILE);
            self::decode($json, 'the spec at commit '.substr($sha, 0, 7));

            if (! is_dir($cache) && ! @mkdir($cache, 0777, true) && ! is_dir($cache)) {
                throw new \RuntimeException("Could not create {$cache}.");
            }

            // Written aside and moved into place, so an interrupted download never leaves half a spec behind.
            $partial = $file.'.'.getmypid().'.part';
            file_put_contents($partial, $json);
            rename($partial, $file);
        }

        return self::decode((string) file_get_contents($file), $file);
    }

    /**
     * Fetches a URL's body.
     *
     * @param bool $api Whether it is GitHub's API, which may be given a token from `GITHUB_TOKEN` for a
     *                  higher rate limit than the 60 requests an hour it allows without one.
     */
    private static function download(string $url, bool $api = false): string
    {
        $headers = ['User-Agent: DiscordPHP-openapi-check'];

        if ($api) {
            $headers[] = 'Accept: application/vnd.github+json';

            if (is_string($token = getenv('GITHUB_TOKEN')) && '' !== $token) {
                $headers[] = "Authorization: Bearer {$token}";
            }
        }

        $context = stream_context_create(['http' => ['header' => implode("\r\n", $headers), 'timeout' => 60, 'ignore_errors' => true]]);
        $body = @file_get_contents($url, false, $context);
        // PHP 8.4 added the function to replace the variable. PHP only fills the variable in a scope that
        // names it, so it has to be named here for 8.1 to 8.3.
        $response = function_exists('http_get_last_response_headers') ? http_get_last_response_headers() : ($http_response_header ?? null);
        $status = preg_match('{^HTTP/\S+ (\d{3})}', (string) ($response[0] ?? ''), $match) ? (int) $match[1] : 0;

        if (false === $body || 200 !== $status) {
            $limited = $api && (403 === $status || 429 === $status) ? ' Set GITHUB_TOKEN to raise GitHub\'s rate limit.' : '';

            throw new \RuntimeException("Could not download {$url}".(0 === $status ? '' : " (HTTP {$status})").'.'.$limited);
        }

        return $body;
    }

    /**
     * @return array<mixed>
     */
    private static function decode(string $json, string $what): array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException("{$what} is not valid JSON: {$e->getMessage()}", 0, $e);
        }

        if (! is_array($decoded)) {
            throw new \RuntimeException("{$what} is not a JSON object.");
        }

        return $decoded;
    }

    /**
     * Records the live spec's commit, and every current gap with the reason already recorded for it.
     *
     * @param array{sha: string, date: string} $latest
     * @param array<string, array>             $gaps
     * @param array<string, string>            $known
     */
    private static function writeBaseline(string $file, array $latest, array $gaps, array $known): void
    {
        $unimplemented = [];
        foreach (array_keys(self::sorted($gaps)) as $key) {
            $unimplemented[$key] = $known[$key] ?? self::UNEXPLAINED;
        }

        $baseline = [
            'spec' => 'https://github.com/'.self::REPOSITORY.'/blob/'.$latest['sha'].'/'.self::FILE,
            'commit' => $latest['sha'],
            'date' => $latest['date'],
            'unimplemented' => $unimplemented,
        ];

        file_put_contents($file, json_encode($baseline, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");
    }

    /**
     * PHP source with its comments blanked out, so a docblock's `@see Endpoint::X` does not count as a use.
     * Every other character stays where it was.
     */
    private static function withoutComments(string $source): string
    {
        $code = '';

        foreach (token_get_all($source) as $token) {
            if (is_array($token) && (T_COMMENT === $token[0] || T_DOC_COMMENT === $token[0])) {
                $code .= (string) preg_replace('/[^\n]/', ' ', $token[1]);
            } else {
                $code .= is_array($token) ? $token[1] : $token;
            }
        }

        return $code;
    }

    /**
     * The request methods sent with the endpoint named at `$offset` of a source file.
     *
     * @return list<string> `?` alone when none can be told.
     */
    private static function methodsAt(string $source, int $offset): array
    {
        $lineStart = strrpos(substr($source, 0, $offset), "\n");
        $line = substr($source, false === $lineStart ? 0 : $lineStart + 1, $offset - (false === $lineStart ? 0 : $lineStart + 1));

        // A repository's endpoint table: 'create' => Endpoint::X.
        if (preg_match("/'(\\w+)'\\s*=>\\s*$/", $line, $key)) {
            if (isset(self::REPOSITORY_KEYS[$key[1]])) {
                return [self::REPOSITORY_KEYS[$key[1]]];
            }

            // Any other key is sent by a method of the same repository, through $this->endpoints['key'].
            $methods = [];
            preg_match_all("/endpoints\\['".preg_quote($key[1], '/')."'\\]/", $source, $uses, PREG_OFFSET_CAPTURE);
            foreach ($uses[0] as [, $at]) {
                $methods = [...$methods, ...self::requestsAt($source, $at)];
            }

            return [] === $methods ? ['?'] : array_values(array_unique($methods));
        }

        return self::requestsAt($source, $offset) ?: ['?'];
    }

    /**
     * The request methods the statement around `$offset` sends: by a call in the statement itself, or,
     * when the statement assigns to a variable, by calls later on that pass the variable.
     *
     * @return list<string>
     */
    private static function requestsAt(string $source, int $offset): array
    {
        $before = substr($source, max(0, $offset - 400), min(400, $offset));
        $boundaries = array_filter([strrpos($before, ';'), strrpos($before, '{'), strrpos($before, '}')], static fn ($at): bool => false !== $at);
        $statement = substr($before, [] === $boundaries ? 0 : max($boundaries) + 1);

        if (preg_match_all('/->(get|post|put|patch|delete)\s*\(/i', $statement, $calls)) {
            return [strtoupper(end($calls[1]))];
        }

        if (preg_match('/^\s*(\$\w+)\s*=/', $statement, $assigned)) {
            // Only as far as the next method, which may reuse the name for another endpoint.
            $after = substr($source, $offset, 4000);
            if (preg_match('/\bfunction\s+\w+\s*\(/', $after, $next, PREG_OFFSET_CAPTURE)) {
                $after = substr($after, 0, $next[0][1]);
            }

            // The variable may be wrapped on the way, as in ->get(Endpoint::bind((string) $endpoint, …)).
            if (preg_match_all('/->(get|post|put|patch|delete)\s*\([^;]{0,160}?'.preg_quote($assigned[1], '/').'\b/i', $after, $sent)) {
                return array_values(array_unique(array_map('strtoupper', $sent[1])));
            }
        }

        return [];
    }

    /**
     * How one map of descriptions differs from another: entries added, removed and changed.
     *
     * @param array<string, string> $before
     * @param array<string, string> $after
     *
     * @return list<string>
     */
    private static function mapChanges(array $before, array $after): array
    {
        $changes = [];

        foreach (array_diff_key($after, $before) as $label => $description) {
            $changes[] = "{$label} added ({$description})";
        }

        foreach (array_diff_key($before, $after) as $label => $description) {
            $changes[] = "{$label} removed";
        }

        foreach (array_intersect_key($after, $before) as $label => $description) {
            if ($description !== $before[$label]) {
                $changes[] = "{$label} changed from {$before[$label]} to {$description}";
            }
        }

        return $changes;
    }

    /**
     * How a schema differs from its older edition.
     *
     * @param array<string, mixed> $old
     * @param array<string, mixed> $new
     *
     * @return list<string>
     */
    private static function schemaDifferences(array $old, array $new): array
    {
        $oldValues = self::enumValues($old);
        $newValues = self::enumValues($new);

        if (null !== $oldValues && null !== $newValues) {
            $differences = [];

            foreach (array_diff_key($newValues, $oldValues) as $title => $value) {
                $differences[] = "value {$title} = ".json_encode($value).' added';
            }

            foreach (array_diff_key($oldValues, $newValues) as $title => $value) {
                $differences[] = "value {$title} = ".json_encode($value).' removed';
            }

            foreach (array_intersect_key($newValues, $oldValues) as $title => $value) {
                if ($value !== $oldValues[$title]) {
                    $differences[] = "value {$title} changed from ".json_encode($oldValues[$title]).' to '.json_encode($value);
                }
            }

            return $differences;
        }

        $oldProperties = $old['properties'] ?? [];
        $newProperties = $new['properties'] ?? [];

        if ([] === $oldProperties && [] === $newProperties) {
            [$was, $now] = [self::describe($old), self::describe($new)];

            return $was === $now ? [] : ["changed from {$was} to {$now}"];
        }

        $describe = static fn (array $properties): array => array_map(self::describe(...), $properties);
        $differences = self::mapChanges(
            array_combine(array_map(static fn ($name): string => "property {$name}", array_keys($oldProperties)), $describe($oldProperties)) ?: [],
            array_combine(array_map(static fn ($name): string => "property {$name}", array_keys($newProperties)), $describe($newProperties)) ?: [],
        );

        $oldRequired = $old['required'] ?? [];
        $newRequired = $new['required'] ?? [];

        foreach (array_diff($newRequired, $oldRequired) as $property) {
            $differences[] = "property {$property} is now required";
        }

        foreach (array_diff($oldRequired, $newRequired) as $property) {
            if (isset($newProperties[$property])) {
                $differences[] = "property {$property} is now optional";
            }
        }

        return $differences;
    }

    /**
     * An enum's values by name, or null when the schema is not an enum. Discord writes an enum as a `oneOf`
     * of titled constants; a plain `enum` list is read too.
     *
     * @param array<string, mixed> $schema
     *
     * @return ?array<string, mixed>
     */
    private static function enumValues(array $schema): ?array
    {
        if (isset($schema['enum']) && is_array($schema['enum'])) {
            return array_combine(array_map(static fn ($value): string => (string) json_encode($value), $schema['enum']), $schema['enum']) ?: null;
        }

        $options = $schema['oneOf'] ?? null;
        if (! is_array($options) || [] === $options) {
            return null;
        }

        $values = [];
        foreach ($options as $option) {
            if (! is_array($option) || ! array_key_exists('const', $option)) {
                return null;
            }

            $values[(string) ($option['title'] ?? json_encode($option['const']))] = $option['const'];
        }

        return $values;
    }

    /**
     * Follows a `$ref` to the part of the document it names.
     *
     * @param array<string, mixed> $spec
     *
     * @return array<string, mixed>
     */
    private static function resolve(array $spec, mixed $node): array
    {
        for ($hops = 0; is_array($node) && isset($node['$ref']) && str_starts_with((string) $node['$ref'], '#/') && $hops < 16; ++$hops) {
            $target = $spec;
            foreach (explode('/', substr($node['$ref'], 2)) as $segment) {
                $target = is_array($target) ? ($target[str_replace(['~1', '~0'], ['/', '~'], $segment)] ?? null) : null;
            }
            $node = $target;
        }

        return is_array($node) ? $node : [];
    }

    /**
     * Operations keyed `METHOD /path`, ordered by path, then method.
     *
     * @template T
     *
     * @param array<string, T> $keyed
     *
     * @return array<string, T>
     */
    private static function sorted(array $keyed): array
    {
        uksort($keyed, static function (string $a, string $b): int {
            [$methodA, $pathA] = explode(' ', $a, 2);
            [$methodB, $pathB] = explode(' ', $b, 2);

            return [$pathA, array_search(strtolower($methodA), self::METHODS, true)] <=> [$pathB, array_search(strtolower($methodB), self::METHODS, true)];
        });

        return $keyed;
    }

    /**
     * @param list<string> $constants
     */
    private static function names(array $constants): string
    {
        return 'Endpoint::'.implode(' and Endpoint::', $constants);
    }

    /**
     * Prints a titled list, unless it is empty.
     *
     * @param list<string> $lines
     */
    private static function section(string $title, array $lines): void
    {
        if ([] === $lines) {
            return;
        }

        echo "\n{$title} (".count(array_filter($lines, static fn (string $line): bool => ! str_starts_with($line, '    '))).")\n";
        foreach ($lines as $line) {
            echo "  {$line}\n";
        }
    }

    private static function usage(): string
    {
        return <<<'USAGE'
            Checks DiscordPHP against the live preview of Discord's OpenAPI description.

              composer openapi                 Report, and fail on anything new since scripts/openapi-baseline.json.
              composer openapi -- --all        Also list the gaps the baseline knows, and Endpoint constants
                                               for routes the spec does not describe.
              composer openapi -- --spec=FILE  Check a local copy of the spec instead of the live one.
              composer openapi:update          Record the live spec's commit and today's gaps as the baseline.

            It exits with 0 when nothing is new since the baseline, 1 when something is, and 2 when it cannot run.
            Set GITHUB_TOKEN for more than GitHub's 60 unauthenticated API requests an hour.

            USAGE;
    }
}
