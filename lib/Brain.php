<?php
/**
 * Brain class
 *
 * @author      Julie Pelletier (blondie101010)
 * @copyright   see LICENSE.txt at the root of this package
 * @access      public
 **/

namespace Blondie101010\Brain;


/**
 * BRAIN's main class which is self-enclosed, taking no dependancies from the outside.
 *
 * Usage:	$brain = new Brain("name", "site", "optionalPath");
 * 			$brain->getAnswer($dataRecord, $result);										// where the result is only needed to learn
 **/
class Brain {
	const LATEST_VERSION = 44;																// latest version to determine if an upgrade is needed

	/** @var array $masters Master Link nodes.  There is only one unless incompatible data is encountered in the current master Link. */
	private $masters = [];

	/** @var string $name Name for this BRAIN which is used in the data file name. */
	private $name;

	/** @var string $site Name for this BRAIN which is used in the master Link names.  Although useless for now, this may become useful when adding parallel processing options. */
	private $site;

	/** @var string $path Path to the directory containing the BRAIN's data. */
	private $path;

	/** @var int $seq Sequence for master node IDs. */
	private $seq = 0;

	/** @var bool $learned Whether this Brain instance has learned something, which depends on whether it received results with the data. */
	private $learned = false;

	/** @var bool $upgraded Whether this Brain instance has done a version upgrade. */
	private $upgraded = false;

	/** @var bool $version The BRAIN's version...  set in __construct() */
	private $version;


	/** @var int $maxSteps Maximum number of steps needed to get an Answer in a master chain.  This is only for debugging. */
	private $maxSteps = 0;

	/** @var int $minSteps Minimum number of steps needed to get an Answer in a master chain.  This is only for debugging. */
	private $minSteps = PHP_INT_MAX;


	/**
	 * Brain constructor which loads the corresponding data file.
	 *
	 * @param string $name See Brain->name for details.
	 * @param string $site See Brain->site for details.
	 * @param string $path See Brain->path for details.
	 * @return Brain The new Brain instance.
	 **/
	public function __construct(string $name, string $site, $path = "./data") {
		$this->name = $name;
		$this->site = $site;

		$this->path = $path;

		if (!is_dir($path)) {
			mkdir($path, 0777, true);
		}

		if (file_exists("$path/Brain-$name.dat")) {
			$data = unserialize(file_get_contents("$path/Brain-$name.dat"));
			$this->seq = $data['seq'];
			$this->version = $data['version'];
			$this->masters = $data['masters'];

			// NOTE: auto-detecting if masters are serialized separately (attempt to bypass segfaults with PHP engine
			if (is_string(reset($this->masters))) {
				foreach ($this->masters as $key => $master) {
					$this->masters[$key] = unserialize($master);
				}
			}

			Common::trace("This brain has " . (count($this->masters)) . " master Link node(s) rated as follows:", Common::DEBUG_WARNING);

			$worst = $secondWorst = PHP_INT_MAX;
			$worstId = -1;
			foreach ($this->masters as $id => $master) {
				Common::trace("$id: {$master->rating} with {$master->ratingCount} experience.", Common::DEBUG_WARNING);

				$master->cleanup();															// cleanup at first to save memory and CPU (very quick)

				if ($secondWorst >= $master->rating) {											// take the newest lowest rating
					if ($worst >= $master->rating) {											// take the newest lowest rating
						$secondWorstId = $worstId;
						$secondWorst = $worst;
						$worstId = $id;
						$worst = $master->rating;
					}
					else {
						$secondWorstId = $id;
						$secondWorst = $master->rating;
					}
				}
			}

			Common::trace("Worst rated master is $worstId with a rating of {$this->masters[$worstId]->rating} and {$this->masters[$worstId]->ratingCount} experience.", Common::DEBUG_WARNING);

			if (count($this->masters) < 3) {
				Common::trace("Sorting masters by experience.", Common::DEBUG_WARNING);
				uasort($this->masters, 
					   function ($a, $b) {
							return $a->ratingCount <=> $b->ratingCount;
					   });
			}
			elseif ($this->masters[$worstId]->ratingCount < 1.5 * $this->masters[$secondWorstId]->ratingCount) {
				// remove weakest master if it's not significantly more experienced (protection)
				Common::trace("Removing worst rated master $worstId with a rating of {$this->masters[$worstId]->rating} and {$this->masters[$worstId]->ratingCount} experience.", Common::DEBUG_WARNING);

				unset($this->masters[$worstId]);
			}
		}
		else {
			$this->version = self::LATEST_VERSION;
		}

		$this->upgrade($this->version);														// check if an upgrade is needed
	}


	/**
	 * Brain destructor which saves the corresponding data file if anything was learned.
	 *
	 * @return null
	 **/
	public function __destruct() {
		if ($this->learned || $this->upgraded) {
			if ($this->learned) {
				Common::trace("This brain now has " . (count($this->masters)) . " master Link node(s)\nmaxSteps: {$this->maxSteps}\tminSteps: {$this->minSteps}", Common::DEBUG_INFO);
			}

			// keep a copy of ourself (no change done when not in learning mode)
			echo "Saving BRAIN...";

			// NOTE: attempting to bypass serialization bug causing segfault
			foreach ($this->masters as $key => $master) {
				$this->masters[$key] = serialize($master);
			}

			$data = ['seq' => $this->seq, 'masters' => $this->masters, 'version' => $this->version];
			file_put_contents($this->path . "/Brain-" . $this->name . ".dat", serialize($data));
			echo "completed!\n";
		}
	}


	/**
	 * Create a new master node.  This is seldom used unless the data provided is incompatible with the master node's first Condition.
	 *
	 * @param array $data Input data record to use for new master learning.
	 * @param float $result Actual result from the provided data.  This is always provided as we can't learn when we have no expected result.
	 * @return null
	 **/
	private function newMaster(array $data, float $result) {
		$this->seq ++;

		$master = new Link($data, $result);

		$this->masters["M{$this->site}-{$this->seq}"] = $master;
		return $this->masters["M{$this->site}-{$this->seq}"];
	}


	/**
 	 * Take a number of associative arrays and combine their fields to make arrays holding the their combined values in the order they are passed.
	 *
	 * Input fields are taken from the first parameter exclusively for integrity and peformance reasons.
 	 *
	 * @param array $arrs Variable number of record arrays.
	 * @return array Array of progressive records.
 	 **/
	public function arrayCombine(...$arrs) {
		if (($num = count($arrs)) < 1) {
			return null;
		}
		elseif ($num < 2) {
			return $arrs[0];
		}

		$newArr = [];

		$args = func_get_args();

		foreach ($arrs[0] as $key => $val) {
			$newArr[$key] = [];
			foreach ($arrs as $arr) {
				$newArr[$key][] = $arr[$key];
			}
		}

		return $newArr;
	}


	/**
	 * Upgrade the Brain to a newer version if applicable.
	 *
	 * @param int $version Current Brain version to determine which upgrade is applicable.
	 * @return null
	 **/
	public function upgrade(int $version) {
		if ($this->version < self::LATEST_VERSION) {
			Common::trace("upgrading to version " . self::LATEST_VERSION, Common::DEBUG_WARNING);

			foreach ($this->masters as $master) {
				$master->upgrade($this->version);
			}

			$this->upgraded = true;

			$this->version = self::LATEST_VERSION;
		}
	}


	/**
	 * Get or learn the actual answer matching the provided $data.
	 *
	 * I hesitated on the name since it is also used for learning as well, but I decided it looked better than process() to get an answer.
	 *
	 * Note that the answer to any learning will always be very similar to $result since it is the current only deterministic path for this data.  Running the same record right after would give the same answer, but processing of different data will cause it to evolve.  For example, a dry run on 1K of our records gives a progression in scores after each learning pass along the lines of 38%, 39%, 48%, 51%, 59%, 52%, 67%, 72%, 80%, 78%, 84%, 89%, 87%, 91%, etc.
	 *
	 * @param array $data The data record to getAnswer.
	 * @param float $result The optional real life answer to apply to our learning, ranging from -1 to +1.
	 * @return float|array Actual answer which is between -1 and +1, or the full result array when backTracking is enabled in Common::backTrack.
	 **/
	public function getAnswer(array $data, float $result = null) {
		if (isset($data['_result']) && is_null($result)) {
			$result = $data['_result'];
			unset($data['_result']);
		}

		// rebuild data array to prefix all fields, preventing internal conflict (_*, _+) and key type errors (numeric keys)
		$newData = [];
		foreach ($data as $key => $value) {
			$newData["F$key"] = $value;
		}

		$data = $newData;

		if (!is_null($result) && ($result < -1 || $result > 1)) {							// simply ignore an invalid result
			Common::trace("ignoring invalid result of $result", Common::DEBUG_WARNING);
			$result = null;
		}

		if (!is_null($result)) {
			$this->learned = true;
		}

		if (empty($this->masters) && is_null($result)) {
			throw new \Exception("we need to learn first");
		}

		$best = null;
		$totalSteps = 0;
		foreach ($this->masters as $id => $master) {
			$response = $master->getAnswer($data, $result);

			if (!is_null($response)) {
				$totalSteps += $response['steps'];

				if (is_null($best) || $response['rating'] >= $best['rating']) {
					$best = $response;

					if ($totalSteps > $response['steps'] && $best['rating'] >= Link::BASE_RATING) {
						break;																// we already encountered two acceptable answers
					}
				}
			}
		}

		if ((is_null($best) || 
			($best['rating'] < Link::BASE_RATING - 0.2 &&
			 count($this->masters) < 5)) &&												// avoid creating too many new masters
			!is_null($result)) {															// encourage branching for parallel operations and precision
			$master = $this->newMaster($data, $result);
			$response = $master->getAnswer($data, $result);

			if (is_null($best) || $response['rating'] >= $best['rating']) {
				$best = $response;
			}
		}

		if (!empty($best) && is_numeric($best['steps'])) {
			$this->maxSteps = max($this->maxSteps, $best['steps']);
			$this->minSteps = min($this->minSteps, $best['steps']);
		}

		if (!is_null($result) && (is_null($best) || !Common::isSameAnswer($best['answer'], $result))) {
			throw new Exception("Learning failed and this should not happen.  Please report this issue for troubleshooting!");
		}

		if (Common::$backTrack) {
			return is_null($response) ? 0 : $response;
		}

		return is_null($response) ? 0 : $response['answer'];
	}
}






