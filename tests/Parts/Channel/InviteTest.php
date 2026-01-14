<?php

declare(strict_types=1);

use Discord\Discord;
use Discord\Parts\Channel\Invite;
use Discord\Http\Endpoint;
use PHPUnit\Framework\TestCase;

use function React\Promise\resolve;

final class InviteTest extends TestCase
{
    public function testUpdateTargetUsersSendsMultipartPut()
    {
        $csvContent = "Users\n123\n456\n";

        $discordMock = $this->getMockBuilder(Discord::class)
            ->disableOriginalConstructor()
            ->getMock();

        $httpMock = $this->getMockBuilder(\Discord\Http\Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $factoryMock = $this->getMockBuilder(\Discord\Factory\Factory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $discordMock->method('getHttpClient')->willReturn($httpMock);
        $discordMock->method('getFactory')->willReturn($factoryMock);

        $invite = new Invite($discordMock, ['code' => 'abc123'], true);

        $httpMock->expects($this->once())
            ->method('put')
            ->with(
                $this->isInstanceOf(Endpoint::class),
                $this->callback(fn ($body) => is_string($body) && strpos($body, $csvContent) !== false),
                $this->callback(fn ($headers) => is_array($headers) && array_key_exists('Content-Type', $headers) && strpos($headers['Content-Type'], 'multipart/form-data') !== false)
            )
            ->willReturn(resolve(null));

        $invite->updateTargetUsers($csvContent);
    }
}
