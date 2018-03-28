<?php
/**
 * Link class
 *
 * @author      Julie Pelletier (blondie101010)
 * @copyright   see LICENSE.txt at the root of this package
 * @access      public
 **/

namespace Blondie101010\Brain;

/**
 * The Link class serves as a connection between the Brain, the Condition, and the Answer.
 *
 * It is not only responsible in linking the other objects together but also to create and replace them depending on its internal rating of the target.
 *
 * Conditions themselves do not know what they point to other than its a Link.
 **/
class Link {
	const BASE_RATING = 0.95;

	private $type = null;
	private $target = null;
	private $ratingGood = self::BASE_RATING;
	private $ratingCount = 1;


// TODO: remove type and use get_class() which should save more than 10 bytes per Link

	/**
	 * Define which properties to export on serialization.
	 *
	 * @return array Array of properties to export.
	 **/
	public function __sleep() {
		return ['type', 'target', 'ratingGood', 'ratingCount'];
	}


	/**
	 * Retrieve an individual property.  This allows read-only access to all the class's properties.
	 *
	 * We also create a virtual property for the rating which is needed at many places.
	 * Note that it's value is typically between 0 and 1 but can overflow one way or the other.  1 representing a success rate close to 100%.
	 *
	 * @param string $name Name of the property to retrieve.
	 * @return mixed Value of the requested property or null if undefined.
	 **/
	public function __get($name) {
		if ($name == 'rating') {
			return $this->ratingGood / (float) $this->ratingCount;
		}

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
	 * @param int $version Current Brain version.
	 * @return null
	 **/
	public function upgrade(int $version) {
		if ($version < 40) {
			// clean rating
			$this->ratingGood = self::BASE_RATING;
			$this->ratingCount = 1;
		}

		// do so recursively in the chain
		if (!is_null($this->target)) {
			$this->target->upgrade($version);
		}
	}


	/**
	 * Check if we're pointing to a bad Condition.
	 *
	 * @return bool Whether the Condition we link to is bad or useless, or not (acceptable).
	 **/
	private function isBadCondition() {
//		return false;
		return !is_null($this->type) && $this->type == 'C' &&
			   (($this->ratingCount > 150 && $this->rating < self::BASE_RATING - 0.05) || 
			    ($this->ratingCount > 200 && $this->rating < self::BASE_RATING) || 
//			    in_array($this->target->field0, ["Ffield0", "Fotherfield"]) ||				// quick patch to remove some undesired fields;  be sure to remove them from your input data before you apply this patch
			    ($this->target->geCount > 500 && !$this->target->ltCount));
	}


	/**
	 * Recursively remove useless/poorly rated Condition nodes.
	 *
	 * @param bool $trunk Whether to truncate the rating to allow more intense cleanup/adaptation following a change in the data or the rating algo.
	 * @return null
	 **/
	public function cleanup(bool $trunc = false) {
		if ($this->type === "C") {															// type may have changed
			$this->target->ge->cleanup($trunc);
			$this->target->lt->cleanup($trunc);
		}

		if ($this->isBadCondition()) {
			$this->skip();
		}

		if ($trunc) {
			$this->ratingGood = $this->ratingGood / $this->ratingCount;
			$this->ratingCount = 1;
		}
	}


	/**
	 * Skip a bad Condition, keeping its best immediate child.
	 *
	 * @return null
	 **/
	public function skip() {
		// skip the useless or bad Condition but keep its children
//		Common::trace("skipping useless or poorly rated Condition with a rating of {$this->rating}", Common::DEBUG_WARNING);
		Common::trace("skipping useless or poorly rated Condition with a rating of {$this->rating}", Common::DEBUG_EXTREME);

		if (!$this->target->ltCount || $this->target->ge->rating > $this->target->lt->rating) {
			$this->type = $this->target->ge->type;
			$this->ratingGood = $this->target->ge->ratingGood;
			$this->ratingCount = $this->target->ge->ratingCount;
			$this->target = $this->target->ge->target;
		}
		else {
			$this->type = $this->target->lt->type;
			$this->ratingGood = $this->target->lt->ratingGood;
			$this->ratingCount = $this->target->lt->ratingCount;
			$this->target = $this->target->lt->target;
		}

		if (is_null($this->type)) {															// in case we copied a new Link
			$this->type = "A";
   			$this->target = new Answer();

			// clean rating
			$this->ratingCount = 1;
			$this->ratingGood = 1;
		}
	}


	/**
	 * Evaluate the negative impact of a response.  This function is designed to better organize $this->getAnswer().  
	 *
	 * It not only provides the negative impact to return to our caller but also adjusts $this->ratingGood and $this->ratingCount.
	 *
	 * @param array|null &$response The response received from our target.
	 * @param float $result The real life answer to apply to our learning.
	 * @return float Negative impact value to return to our caller.
	 **/
	private function applyImpact($response, $result) {
		if (is_null($result)) {
			return 0;
		}

		$impact = 0;

$begin = $this->ratingGood;
		if (is_null($response)) {
			$impact = -1;
		}
		else {
			if (isset($response['impact'])) {
				$impact = $response['impact'] * 0.25;
			}

			if (Common::isSameAnswer($response['answer'], $result)) {
				$impact ++;
			}
			else {
				$impact --;
			}
		
			// fine tuning based on difference between the answer and expected result
			$this->ratingGood -= abs($response['answer'] - $result);
		}

		$this->ratingGood += $impact;
		$this->ratingCount ++;

		return $impact;
	}


	protected function makeCondition(array $data, float $result, Answer $answer = null) {
		$this->type = "C";

		if (!is_null($answer) && !is_null($answer->data)) {
			// Find a rule that distinguishes between the data record which created $answer and the current one as their results do not match.
			$possibilities = [];

			foreach ($data as $field => $value) {
				if ($value != $answer->data[$field]) {										// find a difference
					$possibilities[$field] = $field;
				}
			}

			if (count($possibilities)) {
				$field = array_rand($possibilities);
				$this->target = new Condition($data, $field);

				if (Condition::compare([Condition::getAverage($data[$field]), Condition::getAverage($answer->data[$field])]) == ">=") {
					$this->target->getAnswer($data, $result);
				}
				else {
					$this->target->getAnswer($answer->data, $answer->result);
				}
			}
			else {
				$answer = null;
			}
		}

		if (is_null($answer)) {
			$this->target = new Condition($data);

			$this->target->getAnswer($data, $result);										// initial learning
		}

		// clean rating
		$this->ratingCount = 0;																// it will be incremented later
		$this->ratingGood = self::BASE_RATING;
	}


	/**
	 * Get the actual answer.
	 *
	 * @param array $data The data record to analyze.
	 * @param float $result The real life answer to apply to our learning.
	 * @return array|null Array of 'answer', 'steps', 'experience', and optionally 'backTrack',  obtained from our link or null if unknown.
	 **/
   	public function getAnswer(array $data, float $result = null) {
		if (is_null($this->type)) {															// we don't know the answer yet
   			if (is_null($result)) {
   				return null;
   			}

			Common::trace("adding new Answer", Common::DEBUG_EXTREME);
			
			$this->type = "A";
   			$this->target = new Answer();
   		}
		elseif (!is_null($result) && $this->isBadCondition()) {
			$this->skip();
		}
    
		$firstResponse = $response = $this->target->getAnswer($data, $result);

		$impact = $this->applyImpact($response, $result);

		if (!is_null($result)) { 															// do continuous maintenance
			if ($this->type == "A" && is_null($response)) {									// Answer is invalid
				// lets replace the bad Answer with a new Condition
				Common::trace("replace bad Answer with Condition", Common::DEBUG_EXTREME);
$answer = $this->target;
				$this->makeCondition($data, $result, $this->target);						// make Condition based on Answer data

				$response = $this->target->getAnswer($data, $result);						// initial learning
			}
			elseif ($this->isBadCondition) {												// or Condition filters nothing or is badly rated
				$this->skip();

				$response = $this->target->getAnswer($data, $result);						// process the new target Condition
			}

			if (is_null($firstResponse)) {													// only do it once per response
				$impact += $this->applyImpact($response, $result);						// note that $response is passed by ref
			}
		}

		if (!is_null($response)) {
			if (isset($response['rating'])) { 												// take the most specific rating
				$this->ratingGood += $response['rating'] - self::BASE_RATING;
			}
			else {
				$response['rating'] = $this->rating;
			}

			if (abs($impact) > 0.01) {
				$response['impact'] = $impact;
			}
			elseif (isset($response['impact'])) {
				unset($response['impact']);
			}

			if (Common::$backTrack && $this->type = "C") {
				$backTrack = [];

				$backTrack['field0'] = $this->target->field0;
				$backTrack['field1'] = $this->target->field1;
				$backTrack['value'] = $this->target->value;
				$backTrack['dataField0'] = $data[$this->target->field0];

				if (is_null($this->target->field1)) {
					$backTrack['dataField1'] = null;
					$backTrack['comparison'] = "value";
				}
				else {
					if ($this->target->field1[0] == "_") {
						$backTrack['dataField1'] = null;
						$backTrack['comparison'] = $this->target->field1[1] . " progression analysis";
					}
					else {
						$backTrack['dataField1'] = $data[$this->target->field1];
						$backTrack['comparison'] = "two field comparison";
					}
				}

				if (!array_key_exists('backTrack', $response)) {
					$response['backTrack'] = [];
				}

				array_unshift($response['backTrack'], $backTrack);							// tracking is added backwards, but will show in forward order
			}
		}

		// don't count the Link as a step

		return $response;
	}
}


