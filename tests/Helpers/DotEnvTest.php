<?php

declare(strict_types=1);

use Discord\Helpers\DotEnv;

it('returns null when file does not exist', function () {
    expect(DotEnv::load('/nonexistent/path/.env'))->toBeNull();
});

it('loads key=value pairs into environment', function () {
    $file = tempnam(sys_get_temp_dir(), 'dotenv_');
    file_put_contents($file, "DOTENV_TEST_BASIC=hello\n");

    putenv('DOTENV_TEST_BASIC'); // unset

    $count = DotEnv::load($file);
    unlink($file);

    expect($count)->toBe(1)
        ->and(getenv('DOTENV_TEST_BASIC'))->toBe('hello');

    putenv('DOTENV_TEST_BASIC'); // cleanup
});

it('strips surrounding double quotes', function () {
    $file = tempnam(sys_get_temp_dir(), 'dotenv_');
    file_put_contents($file, 'DOTENV_TEST_DQ="quoted value"'."\n");

    putenv('DOTENV_TEST_DQ');

    DotEnv::load($file);
    unlink($file);

    expect(getenv('DOTENV_TEST_DQ'))->toBe('quoted value');

    putenv('DOTENV_TEST_DQ');
});

it('strips surrounding single quotes', function () {
    $file = tempnam(sys_get_temp_dir(), 'dotenv_');
    file_put_contents($file, "DOTENV_TEST_SQ='single quoted'\n");

    putenv('DOTENV_TEST_SQ');

    DotEnv::load($file);
    unlink($file);

    expect(getenv('DOTENV_TEST_SQ'))->toBe('single quoted');

    putenv('DOTENV_TEST_SQ');
});

it('ignores comment lines', function () {
    $file = tempnam(sys_get_temp_dir(), 'dotenv_');
    file_put_contents($file, "# this is a comment\nDOTENV_TEST_COMMENT=yes\n");

    putenv('DOTENV_TEST_COMMENT');

    DotEnv::load($file);
    unlink($file);

    expect(getenv('DOTENV_TEST_COMMENT'))->toBe('yes');

    putenv('DOTENV_TEST_COMMENT');
});

it('ignores lines without equals sign', function () {
    $file = tempnam(sys_get_temp_dir(), 'dotenv_');
    file_put_contents($file, "INVALID_LINE\nDOTENV_TEST_VALID=yes\n");

    putenv('DOTENV_TEST_VALID');

    $count = DotEnv::load($file);
    unlink($file);

    expect($count)->toBe(1)
        ->and(getenv('DOTENV_TEST_VALID'))->toBe('yes');

    putenv('DOTENV_TEST_VALID');
});

it('does not override already-set environment variables', function () {
    $file = tempnam(sys_get_temp_dir(), 'dotenv_');
    file_put_contents($file, "DOTENV_TEST_EXISTING=from_file\n");

    putenv('DOTENV_TEST_EXISTING=already_set');

    $count = DotEnv::load($file);
    unlink($file);

    expect($count)->toBe(0)
        ->and(getenv('DOTENV_TEST_EXISTING'))->toBe('already_set');

    putenv('DOTENV_TEST_EXISTING');
});

it('handles empty values', function () {
    $file = tempnam(sys_get_temp_dir(), 'dotenv_');
    file_put_contents($file, "DOTENV_TEST_EMPTY=\n");

    putenv('DOTENV_TEST_EMPTY');

    DotEnv::load($file);
    unlink($file);

    expect(getenv('DOTENV_TEST_EMPTY'))->toBe('');

    putenv('DOTENV_TEST_EMPTY');
});

it('handles values containing equals signs', function () {
    $file = tempnam(sys_get_temp_dir(), 'dotenv_');
    file_put_contents($file, "DOTENV_TEST_EQ=a=b=c\n");

    putenv('DOTENV_TEST_EQ');

    DotEnv::load($file);
    unlink($file);

    expect(getenv('DOTENV_TEST_EQ'))->toBe('a=b=c');

    putenv('DOTENV_TEST_EQ');
});
