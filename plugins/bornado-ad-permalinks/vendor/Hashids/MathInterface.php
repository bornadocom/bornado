<?php

namespace Hashids;

interface MathInterface {
	/**
	 * @param int|string $left
	 * @param int|string $right
	 * @return int|string
	 */
	public function add( $left, $right );

	/**
	 * @param int|string $left
	 * @param int|string $right
	 * @return int|string
	 */
	public function divide( $left, $right );

	/**
	 * @param int|string $left
	 * @param int|string $right
	 * @return bool
	 */
	public function greaterThan( $left, $right ): bool;

	/**
	 * @param int|string $value
	 * @return int
	 */
	public function intval( $value ): int;

	/**
	 * @param int|string $left
	 * @param int|string $right
	 * @return int|string
	 */
	public function mod( $left, $right );

	/**
	 * @param int|string $left
	 * @param int|string $right
	 * @return int|string
	 */
	public function multiply( $left, $right );

	/**
	 * @param int|string $value
	 * @return string
	 */
	public function strval( $value ): string;
}
