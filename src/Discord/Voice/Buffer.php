<?php

namespace Discord\Voice;

use TrafficCophp\ByteBuffer\Buffer as BaseBuffer;

class Buffer extends BaseBuffer
{
	/**
	 * Writes a 32-bit unsigned integer with big endian;
	 *
	 * @param int $value
	 * @param int $offset 
	 * @return void 
	 */
	public function writeUInt32BE($value, $offset)
	{
		$this->checkForOverSize(0xffffffff, $value);
		$this->insert('I', $value, $offset, 3);
	}

	/**
	 * Writes a signed integer.
	 *
	 * @param int $value 
	 * @param int $offset 
	 * @return void 
	 */
	public function writeInt($value, $offset)
	{
		$this->insert('i', $value, $offset, 4);
	}

	/**
	 * Writes a signed short.
	 *
	 * @param short $value 
	 * @param int $offset 
	 * @return void 
	 */
	public function writeShort($value, $offset)
	{
		$this->insert('s', $value, $offset, 2);
	}

	/**
	 * Reads a unsigned integer with little endian.
	 *
	 * @param int $offset 
	 * @return int 
	 */
	public function readUIntLE($offset)
	{
		return $this->extract('I', $offset, 3);
	}

	/**
	 * Writes a char.
	 *
	 * @param char $value 
	 * @param int $offset
	 * @return void 
	 */
	public function writeChar($value, $offset)
	{
		$this->insert('c', $value, $offset, $this->lengthMap->getLengthFor('c'));	
	}
}