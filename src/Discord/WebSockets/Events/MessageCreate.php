<?php

namespace Discord\WebSockets\Events;

use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;

class MessageCreate extends Event
{
	/**
	 * Returns the formatted data.
	 *
	 * @param array $data 
	 * @return Message 
	 */
	public function getData($data)
	{
		return new Message([
			'id'                => $data->id,
            'channel_id'        => $data->channel_id,
            'content'           => $data->content,
            'mentions'          => $data->mentions,
            'author'            => $data->author,
            'mention_everyone'  => $data->mention_everyone,
            'timestamp'         => $data->timestamp,
            'edited_timestamp'  => $data->edited_timestamp,
            'tts'               => $data->tts,
            'attachments'       => $data->attachments,
            'embeds'            => $data->embeds,
            'nonce'				=> $data->nonce
		], true);
	}
}