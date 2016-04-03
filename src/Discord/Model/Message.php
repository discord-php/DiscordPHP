<?php

/**
 * This file is part of DiscordPHP
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Discord\Annotation\Build;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class Message extends AbstractModel implements IdentifierModelInterface
{
    /*
     *
        'id',
        'channel_id',
        'content',
        'mentions',
        'author',
        'mention_everyone',
        'timestamp',
        'edited_timestamp',
        'tts',
        'attachments',
        'embeds',
        'nonce',
     */

    /**
     * @var string
     * @Build("id")
     */
    protected $id;

    /**
     * @var Channel
     * @Build("channel_id", class="Discord\Model\Channel")
     */
    protected $channel;

    /**
     * @var string
     * @Build("content")
     */
    protected $content;

    /**
     * @var User[]|ArrayCollection
     * @Build("mentions", class="Discord\Model\User", type="array")
     */
    protected $mentions;

    /**
     * @var User
     * @Build("author", class="Discord\Model\User")
     */
    protected $author;

    /**
     * @var bool
     * @Build("mentionEveryone", type="bool")
     */
    protected $mentionEveryone;

    /**
     * @var int
     * @Build("timestamp", type="int")
     */
    protected $timestamp;

    /**
     * @var int
     * @Build("edited_timestamp", type="int")
     */
    protected $editedTimestamp;

    /**
     * @var bool
     * @Build("tts", type="bool")
     */
    protected $tts;

    /**
     * @var array
     * @Build("attachments", type="array")
     */
    protected $attachments;

    /**
     * @var array
     * @Build("attachments", type="array")
     */
    protected $embeds;

    /**
     * @var mixed
     */
    protected $nonce;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return Message
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Channel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param Channel $channel
     *
     * @return Message
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return Message
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return User[]|ArrayCollection
     */
    public function getMentions()
    {
        return $this->mentions;
    }

    /**
     * @param User[]|ArrayCollection $mentions
     *
     * @return Message
     */
    public function setMentions(array $mentions)
    {
        $this->mentions = new ArrayCollection($mentions);

        return $this;
    }

    /**
     * @return User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param User $author
     *
     * @return Message
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isMentionEveryone()
    {
        return $this->mentionEveryone;
    }

    /**
     * @param boolean $mentionEveryone
     *
     * @return Message
     */
    public function setMentionEveryone($mentionEveryone)
    {
        $this->mentionEveryone = $mentionEveryone;

        return $this;
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param int $timestamp
     *
     * @return Message
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * @return int
     */
    public function getEditedTimestamp()
    {
        return $this->editedTimestamp;
    }

    /**
     * @param int $editedTimestamp
     *
     * @return Message
     */
    public function setEditedTimestamp($editedTimestamp)
    {
        $this->editedTimestamp = $editedTimestamp;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isTts()
    {
        return $this->tts;
    }

    /**
     * @param boolean $tts
     *
     * @return Message
     */
    public function setTts($tts)
    {
        $this->tts = $tts;

        return $this;
    }

    /**
     * @return array
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * @param array $attachments
     *
     * @return Message
     */
    public function setAttachments($attachments)
    {
        $this->attachments = $attachments;

        return $this;
    }

    /**
     * @return array
     */
    public function getEmbeds()
    {
        return $this->embeds;
    }

    /**
     * @param array $embeds
     *
     * @return Message
     */
    public function setEmbeds($embeds)
    {
        $this->embeds = $embeds;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * @param mixed $nonce
     *
     * @return Message
     */
    public function setNonce($nonce)
    {
        $this->nonce = $nonce;

        return $this;
    }
}
