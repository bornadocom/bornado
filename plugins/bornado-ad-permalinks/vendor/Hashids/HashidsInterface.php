<?php

namespace Hashids;

interface HashidsInterface {
	/**
	 * @param mixed ...$numbers
	 * @return string
	 */
	public function encode( ...$numbers ): string;

	/**
	 * @param string $hash
	 * @return array<int,int|string>
	 */
	public function decode( string $hash ): array;

	public function encodeHex( string $str ): string;

	public function decodeHex( string $hash ): string;
}
