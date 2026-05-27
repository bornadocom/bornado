<?php

namespace Hashids;

final class BCMath implements MathInterface {
	public function add( $left, $right ) {
		return bcadd( (string) $left, (string) $right, 0 );
	}

	public function divide( $left, $right ) {
		return bcdiv( (string) $left, (string) $right, 0 );
	}

	public function greaterThan( $left, $right ): bool {
		return 1 === bccomp( (string) $left, (string) $right, 0 );
	}

	public function intval( $value ): int {
		return (int) $value;
	}

	public function mod( $left, $right ) {
		return bcmod( (string) $left, (string) $right, 0 );
	}

	public function multiply( $left, $right ) {
		return bcmul( (string) $left, (string) $right, 0 );
	}

	public function strval( $value ): string {
		return (string) $value;
	}
}
