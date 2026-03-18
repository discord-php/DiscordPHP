<?php

declare(strict_types=1);

use Discord\Discord;
use Discord\Helpers\Collection;
use Discord\Parts\Guild\Sticker;
use Discord\Parts\StickerPack;
use Discord\Repository\StickerPackRepository;

final class StickerPackTest extends DiscordTestCase
{
    /**
     * @covers \Discord\Parts\User\Client::getSticker
     */
    public function testCanGetSticker()
    {
        return wait(function (Discord $discord, $resolve) {
            $discord->sticker_packs->freshen()
                ->then(function (StickerPackRepository $sticker_packs) {
                    $this->assertGreaterThan(0, $sticker_packs->count());
                    $message = 'Sticker pack: '.$sticker_packs->first()->name.' ('.$sticker_packs->first()->id.')';
                    $this->channel()->sendMessage($message);
                })
                ->then($resolve, $resolve);
        });
    }
    public function testCanBuildStickerPackWithStickerInstances()
    {
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
        $discordMock->method('getCollectionClass')->willReturn(Collection::class);

        $sticker1 = new Sticker($discordMock, ['id' => 's1', 'name' => 'One', 'format_type' => Sticker::FORMAT_TYPE_PNG], true);
        $sticker2 = new Sticker($discordMock, ['id' => 's2', 'name' => 'Two', 'format_type' => Sticker::FORMAT_TYPE_PNG], true);

        $pack = new StickerPack($discordMock, [
            'id' => 'pack1',
            'name' => 'Pack',
            'stickers' => [$sticker1, $sticker2],
            'sku_id' => 'sku',
            'description' => 'desc',
            'cover_sticker_id' => null,
            'banner_asset_id' => null,
        ], true);

        $this->assertEquals('pack1', $pack->id);
        $this->assertEquals('Pack', $pack->name);
        $this->assertEquals(2, $pack->stickers->count());
        $this->assertInstanceOf(Sticker::class, $pack->stickers->first());
        $this->assertEquals('pack1', (string) $pack);
    }
}

