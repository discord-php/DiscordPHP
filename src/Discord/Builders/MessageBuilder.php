<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Builders;

use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Component;
use Discord\Builders\Components\SelectMenu;
use Discord\Exceptions\FileNotFoundException;
use Discord\Helpers\Multipart;
use Discord\Http\Exceptions\RequestFailedException;
use Discord\Parts\Channel\Attachment;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Guild\Sticker;
use JsonSerializable;

use function Discord\poly_strlen;

/**
 * Helper class used to build messages.
 *
 * @since 7.0.0
 *
 * @author David Cole <david.cole1340@gmail.com>
 */
class MessageBuilder implements JsonSerializable
{
    /**
     * Content of the message.
     *
     * @var string|null
     */
    private $content;

    /**
     * A nonce that can be used for message roundtrips with the gateway (up to 25 characters).
     *
     * @var int|string|null
     */
    private $nonce;

    /**
     * Override the default username of the webhook.
     *
     * @var string|null
     */
    private $username;

    /**
     * Override the default avatar of the webhook.
     *
     * @var string|null
     */
    private $avatar_url;
    /**
     * Whether the message is text-to-speech.
     *
     * @var bool
     */
    private $tts = false;

    /**
     * Array of embeds to send with the message.
     *
     * @var array[]|null
     */
    private $embeds;

    /**
     * Allowed mentions object for the message.
     *
     * @var array|null
     */
    private $allowed_mentions;

    /**
     * Message to reply to with this message.
     *
     * @var Message|null
     */
    private $replyTo;

    /**
     * Components to send with this message.
     *
     * @var Component[]|null
     */
    private $components;

    /**
     * IDs of up to 3 stickers in the server to send in the message.
     *
     * @var string[]
     */
    private $sticker_ids = [];

    /**
     * Files to send with this message.
     *
     * @var array[]|null
     */
    private $files;

    /**
     * Attachments to send with this message.
     *
     * @var Attachment[]|null
     */
    private $attachments;

    /**
     * Flags to send with this message.
     *
     * @var int|null
     */
    private $flags;

    /**
     * Creates a new message builder.
     *
     * @return static
     */
    public static function new(): self
    {
        return new static();
    }

    /**
     * Sets the content of the message.
     *
     * @param string $content Content of the message. Maximum 2000 characters.
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setContent(string $content): self
    {
        if (poly_strlen($content) > 2000) {
            throw new \LengthException('Message content must be less than or equal to 2000 characters.');
        }

        $this->content = $content;

        return $this;
    }

    /**
     * Sets the nonce of the message. Only used for sending message.
     *
     * @param int|string|null $nonce Nonce of the message.
     *
     * @throws \LengthException `$nonce` string exceeds 25 characters.
     *
     * @return $this
     */
    public function setNonce($nonce = null): self
    {
        if (is_string($nonce) && poly_strlen($nonce) > 25) {
            throw new \LengthException('Message nonce must be less than or equal to 25 characters.');
        }

        $this->nonce = $nonce;

        return $this;
    }

    /**
     * Override the default username of the webhook. Only used for executing webhook.
     *
     * @param string $username New webhook username.
     *
     * @throws \LengthException `$username` exceeds 80 characters.
     *
     * @return $this
     */
    public function setUsername(string $username): self
    {
        if (poly_strlen($username) > 80) {
            throw new \LengthException('Username can be only up to 80 characters.');
        }

        $this->username = $username;

        return $this;
    }

    /**
     * Override the default avatar URL of the webhook. Only used for executing webhook.
     *
     * @param string $avatar_url New webhook avatar URL.
     *
     * @return $this
     */
    public function setAvatarUrl(string $avatar_url): self
    {
        $this->avatar_url = $avatar_url;

        return $this;
    }

    /**
     * Sets the TTS status of the message. Only used for sending message or executing webhook.
     *
     * @param bool $tts
     *
     * @return $this
     */
    public function setTts(bool $tts = false): self
    {
        $this->tts = $tts;

        return $this;
    }

    /**
     * Returns the value of TTS of the builder.
     *
     * @return bool
     */
    public function getTts(): bool
    {
        return $this->tts ?? false;
    }

    /**
     * Adds an embed to the builder.
     *
     * @param Embed|array $embeds,...
     *
     * @throws \OverflowException Builder exceeds 10 embeds.
     *
     * @return $this
     */
    public function addEmbed(...$embeds): self
    {
        foreach ($embeds as $embed) {
            if ($embed instanceof Embed) {
                $embed = $embed->getRawAttributes();
            }

            if (isset($this->embeds) && count($this->embeds) >= 10) {
                throw new \OverflowException('You can only have 10 embeds per message.');
            }

            $this->embeds[] = $embed;
        }

        return $this;
    }

    /**
     * Sets the embeds for the message. Clears the existing embeds in the process.
     *
     * @param Embed[]|array $embeds
     *
     * @return $this
     */
    public function setEmbeds(array $embeds): self
    {
        $this->embeds = [];

        return $this->addEmbed(...$embeds);
    }

    /**
     * Sets the allowed mentions object of the message.
     *
     * @link https://discord.com/developers/docs/resources/channel#allowed-mentions-object
     *
     * @param array $allowed_mentions
     *
     * @return $this
     */
    public function setAllowedMentions(array $allowed_mentions): self
    {
        $this->allowed_mentions = $allowed_mentions;

        return $this;
    }

    /**
     * Sets this message as a reply to another message. Only used for sending message.
     *
     * @param Message|null $message
     *
     * @return $this
     */
    public function setReplyTo(?Message $message = null): self
    {
        $this->replyTo = $message;

        return $this;
    }

    /**
     * Adds a component to the builder.
     *
     * @param Component $component Component to add.
     *
     * @throws \InvalidArgumentException Component is not a type of `ActionRow` or `SelectMenu`
     * @throws \OverflowException        Builder exceeds 5 components.
     *
     * @return $this
     */
    public function addComponent(Component $component): self
    {
        if (! ($component instanceof ActionRow || $component instanceof SelectMenu)) {
            throw new \InvalidArgumentException('You can only add action rows and select menus as components to messages. Put your other components inside an action row.');
        }

        if (isset($this->components) && count($this->components) >= 5) {
            throw new \OverflowException('You can only add 5 components to a message');
        }

        $this->components[] = $component;

        return $this;
    }

    /**
     * Removes a component from the builder.
     *
     * @param Component $component Component to remove.
     *
     * @return $this
     */
    public function removeComponent(Component $component): self
    {
        if (($idx = array_search($component, $this->components)) !== null) {
            array_splice($this->components, $idx, 1);
        }

        return $this;
    }

    /**
     * Sets the components of the message. Removes the existing components in the process.
     *
     * @param array $components New message components.
     *
     * @return $this
     */
    public function setComponents(array $components): self
    {
        $this->components = [];

        foreach ($components as $component) {
            $this->addComponent($component);
        }

        return $this;
    }

    /**
     * Returns all the components in the builder.
     *
     * @return Component[]
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * Adds a sticker to the builder. Only used for sending message or creating forum thread.
     *
     * @param string|Sticker $sticker Sticker to add.
     *
     * @throws \OverflowException Builder exceeds 3 stickers.
     *
     * @return $this
     */
    public function addSticker($sticker): self
    {
        if (count($this->sticker_ids) >= 3) {
            throw new \OverflowException('You can only add 3 stickers to a message');
        }

        if ($sticker instanceof Sticker) {
            $sticker = $sticker->id;
        }

        $this->sticker_ids[] = $sticker;

        return $this;
    }

    /**
     * Removes a sticker from the builder.
     *
     * @param string|Sticker $sticker Sticker to remove.
     *
     * @return $this
     */
    public function removeSticker($sticker): self
    {
        if ($sticker instanceof Sticker) {
            $sticker = $sticker->id;
        }

        if (($idx = array_search($sticker, $this->sticker_ids)) !== null) {
            array_splice($this->sticker_ids, $idx, 1);
        }

        return $this;
    }

    /**
     * Sets the stickers of the builder. Removes the existing stickers in the process.
     *
     * @param array $stickers New sticker ids.
     *
     * @return $this
     */
    public function setStickers(array $stickers): self
    {
        $this->sticker_ids = [];

        foreach ($stickers as $sticker) {
            $this->addSticker($sticker);
        }

        return $this;
    }

    /**
     * Returns all the sticker ids in the builder.
     *
     * @return string[]
     */
    public function getStickers(): array
    {
        return $this->sticker_ids;
    }

    /**
     * Adds a file attachment to the builder.
     *
     * Note this is a synchronous function which uses `file_get_contents` and therefore
     * should not be used when requesting files from an online resource. Fetch the content
     * asynchronously and use the `addFileFromContent` function for tasks like these.
     *
     * @param string      $filepath Path to the file to send.
     * @param string|null $filename Name to send the file as. `null` for the base name of `$filepath`.
     *
     * @return $this
     */
    public function addFile(string $filepath, ?string $filename = null): self
    {
        if (! file_exists($filepath)) {
            throw new FileNotFoundException("File does not exist at path {$filepath}.");
        }

        return $this->addFileFromContent($filename ?? basename($filepath), file_get_contents($filepath));
    }

    /**
     * Adds a file attachment to the builder with a given filename and content.
     *
     * @param string $filename Name to send the file as.
     * @param string $content  Content of the file.
     *
     * @return $this
     */
    public function addFileFromContent(string $filename, string $content): self
    {
        $this->files[] = [$filename, $content];

        return $this;
    }

    /**
     * Returns the number of files attached to the builder.
     *
     * @return int
     */
    public function numFiles(): int
    {
        if (! isset($this->files)) {
            return 0;
        }

        return count($this->files);
    }

    /**
     * Removes all files from the builder.
     *
     * @return $this
     */
    public function clearFiles(): self
    {
        $this->files = [];

        return $this;
    }

    /**
     * Adds attachment(s) to the builder.
     *
     * @param Attachment|string|int $attachment Attachment objects or IDs to add
     *
     * @return $this
     */
    public function addAttachment(...$attachments): self
    {
        foreach ($attachments as $attachment) {
            if ($attachment instanceof Attachment) {
                $attachment = $attachment->getRawAttributes();
            } else {
                $attachment = ['id' => $attachment];
            }

            $this->attachments[] = $attachment;
        }

        return $this;
    }

    /**
     * Returns all the attachments in the builder.
     *
     * @return Attachment[]
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /**
     * Removes all attachments from the message.
     *
     * @return $this
     */
    public function clearAttachments(): self
    {
        $this->attachments = [];

        return $this;
    }

    /**
     * Sets the flags of the message.
     * You cannot set flags except for when sending webhooks or interaction. Use the APIs given.
     *
     * @internal
     *
     * @param int $flags
     *
     * @return $this
     */
    public function _setFlags(int $flags): self
    {
        $this->flags = $flags;

        return $this;
    }

    /**
     * Returns a boolean that determines whether the message needs to
     * be sent via multipart request, i.e. contains files.
     *
     * @return bool
     */
    public function requiresMultipart(): bool
    {
        return isset($this->files);
    }

    /**
     * Converts the request to a multipart request.
     *
     * @internal
     *
     * @param bool $payload Whether to include the JSON payload in the response.
     *
     * @return Multipart
     */
    public function toMultipart(bool $payload = true): Multipart
    {
        if ($payload) {
            $fields = [
                [
                    'name' => 'payload_json',
                    'content' => json_encode($this),
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                ],
            ];
        }

        foreach ($this->files as $idx => [$filename, $content]) {
            $fields[] = [
                'name' => 'file'.$idx,
                'content' => $content,
                'filename' => $filename,
            ];
        }

        return new Multipart($fields);
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        $empty = true;

        if (! empty($this->files)) {
            $body = [];
            $empty = false;
        }

        if (isset($this->content)) {
            $body['content'] = $this->content;
            $empty = false;
        }

        if (isset($this->username)) {
            $body['username'] = $this->username;
        }

        if (isset($this->avatar_url)) {
            $body['avatar_url'] = $this->avatar_url;
        }

        if ($this->nonce !== null) {
            $body['nonce'] = $this->nonce;
        }

        if ($this->tts) {
            $body['tts'] = true;
        }

        if (isset($this->embeds)) {
            $body['embeds'] = $this->embeds;
            $empty = false;
        }

        if (isset($this->allowed_mentions)) {
            $body['allowed_mentions'] = $this->allowed_mentions;
        }

        if ($this->replyTo) {
            $body['message_reference'] = [
                'message_id' => $this->replyTo->id,
                'channel_id' => $this->replyTo->channel_id,
            ];
        }

        if (isset($this->components)) {
            $body['components'] = $this->components;
            $empty = false;
        }

        if ($this->sticker_ids) {
            $body['sticker_ids'] = $this->sticker_ids;
            $empty = false;
        }

        if (isset($this->attachments)) {
            $body['attachments'] = $this->attachments;
            $empty = false;
        }

        if (isset($this->flags)) {
            $body['flags'] = $this->flags;
        } elseif ($empty) {
            throw new RequestFailedException('You cannot send an empty message. Set the content or add an embed or file.');
        }

        return $body;
    }
}
