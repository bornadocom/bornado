<?php

namespace Hashids;

final class Gmp implements MathInterface {
	public function add( $left, $right ) {
		return gmp_strval( gmp_add( $left, $right ) );
	}

	public function divide( $left, $right ) {
		return gmp_strval( gmp_div_q( gmp_init( (string) $left, 10 ), gmp_init( (string) $right, 10 ) ) );
	}

	public function greaterThan( $left, $right ): bool {
		return 1 === gmp_cmp( $left, $right );
	}

	public function intval( $value ): int {
		return (int) gmp_strval( gmp_init( (string) $value, 10 ) );
	}

	public function mod( $left, $right ) {
		return gmp_strval( gmp_mod( gmp_init( (string) $left, 10 ), gmp_init( (string) $right, 10 ) ) );
	}

	public function multiply( $left, $right ) {
		return gmp_strval( gmp_mul( gmp_init( (string) $left, 10 ), gmp_init( (string) $right, 10 ) ) );
	}

	public function strval( $value ): string {
		return (string) $value;
	}
}
