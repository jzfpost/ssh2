<?php


namespace jzfpost\ssh2;


use Exception;

class Ssh2Exception extends Exception
{
	/**
	 * @return string the user-friendly name of this exception
	 */
	public function getName()
	{
		return 'SSH2 Error';
	}
}