<?php
/**
 * Common class
 *
 * @author      Julie Pelletier (blondie101010)
 * @copyright   see LICENSE.txt at the root of this package
 * @access      public
 **/

namespace Blondie101010\Brain;

/**
 * Provide common functionalities for the different components including answer comparison, debug, and backtracking.
 **/
class Common {
	const DEBUG_ERROR = 0;																	// can not be bypassed
	const DEBUG_WARNING = 1;
	const DEBUG_INFO = 2;
	const DEBUG_EXTREME = 3;

	/** @var bool $debug Debug level matching the above constants. */
	static public $debug = self::DEBUG_ERROR;

	/** @var bool $backTrack Whether to run the BRAIN in backTrack mode which causes the answer array to be returned instead of the actual answer value.  The returned array is also added with information on each encountered Condition. */
	static public $backTrack = false;


	/**
	 * Output a debug message depending on the debug level.
	 *
	 * @param string $message Message to output.
	 * @param int $level Level of importance of the provided message.
	 * @return null
	 **/
	static function trace(string $message, int $level, bool $addLineFeed = true) {
		if (self::$debug >= $level) {
			$eol = ($addLineFeed ? PHP_EOL : '');
			echo "$message$eol";
		}
	}


	/**
	 * Verify if the two provided answers match each other by their closeness to -1, 0, or +1.
	 *
	 * @param float $a Answer to compare.
	 * @param float $b Answer to compare.
	 * @return bool Whether they give the same conclusion or not.
	 **/
	static function isSameAnswer(float $a, float $b) {
		return ($a > 0.33 && $b > 0.33) || ($a < -0.33 && $b < -0.33) || ($a >= -0.33 && $b >= -0.33 && $a <= 0.33 && $b < 0.33);
	}
}





