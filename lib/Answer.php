<?php
/**
 * Answer class
 *
 * @author      Julie Pelletier (blondie101010)
 * @copyright   see LICENSE.txt at the root of this package
 * @access      public
 **/

namespace Blondie101010\Brain;

/**
 * Hold and manage a single answer.  An Answer can be extremely volatile as it gets dismissed as soon as it makes a mistake.
 **/
class Answer {
	/** @var float $answerTotal Sum of all the encountered matching results. */
	private $answerTotal = 0;

	/** @var int $answerCount Number of encountered matching results.  This also serves as a rating because a single bad decision causes it to get replaced inside its parent Link. */
	private $answerCount = 0;


	/**
	 * Define which properties to export on serialization.
	 *
	 * @return array Array of properties to export.
	 **/
	public function __sleep() {
		return ['answerTotal', 'answerCount'];
	}


	/**
	 * Retrieve an individual property.  This allows read-only access to all the class's properties.
	 *
	 * @param string $name Name of the property to retrieve.
	 * @return mixed Value of the requested property or null if undefined.
	 **/
	public function __get(string $name) {
		if (property_exists($this, $name)) {
			return $this->$name;
		}
		else {
			return null;
		}
	}
	

	/**
	 * Upgrade the Brain to a newer version if applicable.
	 *
	 *
	 * @param int $version Current Brain version.
	 * @return null
	 **/
	public function upgrade(int $version) {
		if ($version < 1) {																	// nothing to upgrade yet here
		}
	}


	/**
	 * Get the actual answer.
	 *
	 * Note that it is essential to call getAnswer() right after instanciation, which is how Link->getAnswer() is done.
	 * 
	 * If we did it in the constructor, it would make it more repetitive to avoid counting the first entry twice.
	 *
	 * This also ensures we never have a division by zero problem.
	 *
	 * @param array $data The data array which is not needed here but kept to be consistent with the Condition's parameters.
	 * @param float $result The real life answer to apply to our learning.
     * @return array|null Array of 'answer', 'steps', 'experience', or null in case of error which causes the Link to dismiss this Answer.
	 **/
	public function getAnswer(array $data, float $result = null) {
		if (!is_null($result)) {
			$same = false;

			if (!$this->answerCount || ($same = Common::isSameAnswer($this->answerTotal / $this->answerCount, $result))) {
				$this->answerTotal += $result;												// fine tune or set answer
				$this->answerCount ++;
				$same = true;
			}

			if (!$same) {
				return null;																// this answer is bad and needs to be rejected (no chances with a deterministic approach)
			}
		}

		if (!$this->answerCount) {															// no learning yet
			return null;
		}

        return ['answer' => $this->answerTotal / $this->answerCount, 'steps' => 1, 'experience' => $this->answerCount];
	}
}


