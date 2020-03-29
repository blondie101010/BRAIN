<?php
/**
 * Condition class
 *
 * @author      Julie Pelletier (blondie101010)
 * @copyright   see LICENSE.txt at the root of this package
 * @access      public
 **/

namespace Blondie101010\Brain;

/**
 * The Condition is the base element in the BRAIN's design as it determines how logic chains branch.  It defines and applies rules based on the provided data.
 *
 * Each Condition connects with 2 Link nodes depending on the result of our comparison.
 **/
class Condition {
	/** @var string $field0 Field name to look for in data. */
	private $field0;

	/** @var string|null $field1 Field name to look for in data, a progression analysis indicator, or null for static comparison. */
	private $field1;

	/** @var mixed|null $value Value to compare with based on initial learning.  Null when not applicable. */
	private $value;
	
	/** @var Link|null $ge Reference to Link when the result is >=. */
	private $ge = null;

	/** @var Link|null $lt Reference to Link when the result is <. */
	private $lt = null;

	/** @var int $geCount Number of times this Condition evaluated >=. */
	private $geCount = 0;

	/** @var int $ltCount Number of times this Condition evaluated <. */
	private $ltCount = 0;


	/**
	 * Create a Condition based on the provided $data.  Some aspects are determined randomly while others depend on what is in $data.
	 *
	 * @param array $data Data array to use in the creation of the new Condition.
	 * @return Condition new Condition instance
	 **/
	public function __construct(array $data) {
        $field = array_rand($data);										 		            // get a random field

		if (is_array($data[$field]) && !is_string($data[$field][0]) && rand(1, 3) == 1) {	// progression analysis
			$this->field0 = $field;
			// define fields for comparison which includes the progression analysis types
			$types = ["_+", "_*", "_m", "_M"];                                              // arithmetic progression, geometric progression, min, max
			$this->field1 = $types[rand(0, 3)];			 						            // define the progression analysis type

			$this->value = self::getProgression($data[$field], $this->field1[1]);
		}
		else {
			switch (rand(1, 2)) {
				case 1:														 				// comparison between 2 fields (no value used)
					$this->value = null;													// N/A

					$fieldType = gettype($data[$field]);

					unset($data[$field]);									   				// remove current field from the list to avoid duplicates

					while (($field1 = array_rand($data)) !== null) {
						if ($fieldType == 'integer') {											// simple cheat to let it compare int to float
							$fieldType = 'double';
						}

						if ($fieldType == gettype($data[$field1])) {
							break;
						}
						
						unset($data[$field1]);												// remove current field from the list to avoid duplicates
					}

					if (!is_null($field1)) {
						$this->field0 = $field;
						$this->field1 = $field1;
					
						break;
					}
					// else we make it a static comparison

				case 2:														 				// static value
					$this->value = self::getAverage($data[$field]);						// getAverage() will take care of dealing with whether an it's an array or not

					$this->field0 = $field;
					$this->field1 = null;
					break;
			}
		}
	}


	/**
	 * Define which properties to export on serialization.
	 *
	 * @return array Array of properties to export.
	 **/
	public function __sleep() {
		return ['field0', 'field1', 'value', 'ge', 'lt', 'geCount', 'ltCount'];
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
	 * Calculate the the progression of an array.
	 *
	 * @param array|float $values Values for which to calculate the progression, accepting single floats.
	 * @param string $type '+' for arithmetic, '*' for geometric, 'm' for minimum, and 'M' for maximum.
	 * @return float The computed progression factor.
	 **/
	public static function getProgression($values, string $type) {
		if (!in_array($type, ['+', '*', 'm', 'M'])) {
			throw ProgrammingException("getProgression() type must be either '+', '*', 'm', and 'M'");
		}

		if (!is_array($values)) {
		    switch ($type) {
		        case '+':
		            return 0;
		            break;
		            
		        case '*':
		            return 1;
		            break;
		        
		        default:
		            return $values;
		    }
		}

        // deal with min / max
		if (strtoupper($type) == 'M') {
		    $min = PHP_INT_MAX;
		    $max = PHP_INT_MIN;
		    
		    foreach ($values as $value) {
		        $min = min($min, $value);
		        $max = max($max, $value);
		    }
		    
		    if ($type == 'm') {
		        return $min;
		    }
		    else {
		        return $max;
		    }
		}

		// deal with math progression
		$diffTotal = 0;
		$diffCount = count($values);

		$values = array_values($values);										// rebuild a clean numeric index

		for ($i = 1; $i < $diffCount; $i ++) {
			if ($type == '_+') {
				$diffTotal += $values[$i] - $values[$i - 1];
			}
			else {
				$diffTotal += $values[$i] / ($values[$i - 1] == 0 ? 0.00000001 : $values[$i - 1]);
			}
		}

		return $diffTotal / $diffCount;
	}


	/**
	 * Calculate the average value of a field.
	 *
	 * @param mixed $values Values for which to build an average.  If $values is not an array, it is simply returned.  For strings, the most common or first entry is taken.
	 * @return mixed Average of input values which depends on the data type present in $values.
	 **/
	public static function getAverage($values) {
		if (!is_array($values)) {
			return $values;
		}
		elseif (!count($values)) {
			return null;
		}

		if (is_string($values[0])) {										// find most common value
			$maxCount = 0;

			foreach (array_count_values($values) as $value => $count) {
				if ($count > $maxCount) {
					$avg = $value;
				}
			}

			return $avg;
		}

		$sum = 0;
		foreach ($values as $value) {
			$sum += $value;
		}

		return $sum / count($values);
	}


	/**
	 * Upgrade the Brain to a newer version if applicable.
	 *
	 * @param int $version Current Brain version.
	 * @return null
	 **/
	public function upgrade(int $version) {
		if ($version < 0.34) {
			// removed as it is not applicable anymore (pre-release upgrade)
		}

		// do so recursively down the chain

		$this->lt->upgrade($version);
		$this->ge->upgrade($version);
	}


	/**
	 * Compare the two values in the $values array.
	 *
	 * @param array $values Array of the two values to compare.
	 * @return string ">=" if $values[0] >= $values[1], else "<".
	 **/
	public static function compare(array $values) {
		if ($values[0] >= $values[1]) {
			return '>=';
		}
		else {
			return '<';
		}
	}


	/**
	 * Get the actual answer.
	 *
	 * @param array $data The data record to analyze.
	 * @param float $result The real life answer to apply to our learning.
	 * @return array Array of 'answer', 'steps', 'experience' obtained from our link or null if unknown.
	 **/
	public function getAnswer(array $data, float $result = null) {
//echo "aa\n";
		// firstly determine which values to compare
		$values = [];

		if (!isset($data[$this->field0])) {
//echo "ab\n";
			return null;																	// our field is absent, so we can not have a valid answer
		}

//echo "ac\n";
		if (is_null($this->field1)) {
			$values[0] = $this->value;
			$values[1] = $data[$this->field0];
		}
		else {																				// direct comparison with $this->value
			if ($this->field1 == '_+' || $this->field1 == '_*') {							// progression analysis
				$values[0] = $this->value;
				$values[1] = self::getProgression($data[$this->field0], $this->field1[1]);
			}
			else {														  					// two field comparison
				if (!isset($data[$this->field1])) {
					return null;															// our field is absent, so we can not have a valid answer
				}


				$values[0] = self::getAverage($data[$this->field0]);
				$values[1] = self::getAverage($data[$this->field1]);
			}
		}

		if (is_null($this->ge)) {
	 		// create Link nodes for the two possibilities:  >=, or <
			$this->ge = new Link();
			$this->lt = new Link();
		}

		$linkKey = self::compare($values);

		if ($linkKey == '>=') {
			$link = $this->ge;
		}
		else {
			$link = $this->lt;
		}

//echo "ad; linkKey: $linkKey\n";
//var_dump($data);
		$response = $link->getAnswer($data, $result);

//echo "ae\n";
		if (!is_null($response)) {
			if (!is_null($result)) {
				if ($linkKey == '>=') {
					$this->geCount ++;														// count the number of relevant learnings on each Link
				}
				else {
					$this->ltCount ++;														// count the number of relevant learnings on each Link
				}
			}

			$response['steps'] ++;
		}
		
		return $response;
	}
}


