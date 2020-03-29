<?php
/**
 * Brain data feeder.
 *
 * @author      Julie Pelletier (blondie101010)
 * @copyright   see LICENSE.txt at the root of this package
 * @access      public
 **/

namespace Blondie101010\Brain;

/**
 * The Feeder class serves as a connection between the external world and the BRAIN.
 *
 * NOTE: It may be useful to integrate an interface to a data source instead of just taking a filename and dealing with the input directly, but that will be for another day.
 **/
class Feeder {
	protected $status = "O";																// no signal: OK
	protected $brain;
	protected $mode;
	protected $skip;
	protected $batchSize;
	protected $sample;
	protected $maxAttempts;
	protected $tolerance;

	public const MODE_TEST_AND_LEARN = 1;
	public const MODE_TEST_ONLY = 2;
	public const MODE_LEARN_ONLY = 3;


	/**
	 * Feeder constructor.
	 *
	 * @param Brain $brain Brain instance to feed / interact with.
	 * @param int $mode Mode of operation to define if we test or learn, based on the above self::MODE_* constants.
	 * @param int $skip Number of lines (records) to skip from the beginning of the data (file).
	 * @param int $batchSize Number of lines (records) to use in a batch.  This is a choice between speed and quality.  Bigger batches improve the learning quality but usually takes longer.
	 * @param bool $sample Whether to only read subsets of the records (true) or not (false).  This is most useful when you feed records that are regrouped or organized to a baby BRAIN.
	 * @param int $effort Intensity of efforts made to learn.  This is determining in the time needed to process the batch and in the quality of learning, but in some conflicting cases it can take longer for nothing.
	 * @return Feeder The new Feeder instance.
	 **/
	public function __construct(Brain $brain, int $mode = MODE_TEST_AND_LEARN, int $skip = 0, int $batchSize = 1000, bool $sample = false, int $effort = 5) {
		$this->brain = $brain;
		$this->mode = $mode;
		$this->skip = $skip;
		$this->batchSize = $batchSize;
		$this->sample = $sample;
		$this->maxAttempts = max(1, round(5.5 * $effort));
		$this->tolerance = round(6.6 / max(0.000000001, $effort));

		pcntl_signal(SIGTERM, [$this, "signalHandler"]); 									// setup signal handler
	}


	/**
	 * Handle SIGTERM.
	 *
	 * @param int $signal Signal number.
	 * @return null
	 **/
	public function signalHandler(int $signal) {
		switch ($signal) {
			case SIGTERM:
				$this->status = "T";														// sigTerm: terminate process cleanly
				break;

			default:
				// nothing to do, ignore other signals
		}

		Common::trace("received signal $signal, status now set at {$this->status}", Common::DEBUG_WARNING);
	}


	/**
	 * Process a data block.
	 *
 	 * @param array $data Array of serialized data records.
 	 * @param bool $testMode True if we test or false if we learn.  Learning is launched automatically after the test.  This may be partially overridden by this Feeder's mode.
 	 * @param array $failed Array of previously failed data records.
 	 * @return array Array of data that the Brain failed on.
 	 **/
	protected function processDataBlock(array $data, bool $testMode = false, array $failed = null, $label = "") {
		if ($testMode && $this->mode == self::MODE_LEARN_ONLY) {
			return $this->processDataBlock($data, false, $failed, $label);
		}

		Common::trace("$label\t" . ($testMode ? "testing " : "learning") . "\t" . count($data) . "\trecs..\t", Common::DEBUG_INFO, false);

		if (is_null($failed)) {
			$failed = [];
		}

		$good = 0;
		$total = 0;

		foreach ($data as $line) {
			pcntl_signal_dispatch();

			if ($this->status == 'T') {															// we were told to terminate
				return [];
			}

			$record = unserialize($line);
			$result = $record['_result'] ?? null;

			if (!is_null($result) && $testMode) {
				unset($record['_result']);
			}

			$answer = $this->brain->getAnswer($record);

			if (!is_null($result) && $testMode) {
				$total ++;

				if (Common::isSameAnswer($answer, $result)) {
					$good ++;
				}
				else {																			// keep track of failed records to focus on them
					$failed[] = $line;
				}
			}
		}

		if ($testMode) {
			if ($total) {
				$score = 100.0 * ($good / $total);
				Common::trace(sprintf("\t%7d / %-7d", $good, $total) . ":\t" . round($score, 2) . "%", Common::DEBUG_INFO);

				$total = $good = 0;

//				if ($score > 99.99 || $this->mode == self::MODE_TEST_ONLY) {
				if ($score == 100 || $this->mode == self::MODE_TEST_ONLY) {
					return [];																	// already a perfect round
				}
				else {
					return $this->processDataBlock($data, false, $failed, $label);
				}
			}
		}
		else {
			Common::trace("done.", Common::DEBUG_INFO);
		}

		return $failed;
	}


	/**
	* Process a data file.
	*
 	* @param string $inputFile File to read the data from.
	* @return null
 	**/
	public function processFile(string $inputFile) {
		if (!($fp = fopen($inputFile, "r"))) {
			throw new \Exception("Error opening $inputFile!\n");
		}

		if ($this->brain->isNew) {
			$testMode = false;
		}
		else {
			$testMode = true;
		}

		Common::trace("reading file $inputFile", Common::DEBUG_INFO);

		$difficult = [];

		$this->initSkip = $this->skip;

		for ($i = 0; $this->status == 'O'; $i ++) {
			Common::trace("current batch: $i", Common::DEBUG_INFO);
			$contents = [];

			if ($this->sample || $this->skip) {
				$this->skip = $this->initSkip;													// skip records after every batch we process
				Common::trace("skipping $this->skip records", Common::DEBUG_INFO);
			}

			for (; $this->skip > 0; $this->skip --) {
				if (fgets($fp) === FALSE) {
					break 2;
				}
			}

			$this->skip = 0;																// we're done skipping (unless sample is non-zero)

			for ($j = 0; $j < $this->batchSize; $j ++) {
				if (($line = fgets($fp)) === FALSE) {
					if (!$j) {																// for some reason we got no data
						break 2;
					}

					break;
				}

				$contents[] = $line;
			}

			$allFailed = $lastFailed = [];
			$firstContents = $contents;
			$attempts = 0;
			$firstPass = true;

			while ($this->status == 'O' && !empty($contents)) {								// learn until we reach a good level
				$contents = $this->processDataBlock($contents, $testMode, [], "A");

				if ($this->mode == self::MODE_TEST_ONLY) {
					break;
				}

				if (count($contents)) {
					if (!empty($lastFailed)) {
						$firstPass = false;
					}

					$lastFailed = $contents;

					// be more aggressive by keeping all failed records
					$difficult = array_unique(array_merge($lastFailed, $difficult));
				}

				if ($this->mode != self::MODE_LEARN_ONLY) {
					$testMode = true;
				}

				if (count($contents)) {
					$allFailed = array_unique(array_merge($contents, $allFailed));
				}
				else {
					$retries = 0;
					while (count($allFailed) && $retries < 0.5 * $this->maxAttempts) {
						shuffle($allFailed);												// prevent order from limiting our learning
						$lastFailed = array_unique($this->processDataBlock($allFailed, $testMode, $lastFailed, "aF"));

						if (count($lastFailed)) {	
							shuffle($lastFailed);											// prevent order from limiting our learning
							$allFailed = $this->processDataBlock($lastFailed, $testMode, [], "lFa");// reinforce the most difficult learning
						}
						else {
							$allFailed = [];
						}

						$retries ++;
					}

					$tmpDiff = array_merge($difficult, $allFailed);

					for ($retries = 0; count($tmpDiff) && $retries < $this->maxAttempts; $retries ++) {
						$tmpDiff = $this->processDataBlock($tmpDiff, $testMode, [], "D");
					}

					$contents = $this->processDataBlock($firstContents, $testMode, [], "B");

					shuffle($lastFailed);														// prevent order from limiting our learning
					$difficult = array_unique(array_merge($lastFailed, $difficult, $contents));	// keep the thoughest records
					$lastFailed = $this->processDataBlock($lastFailed, $testMode, [], "lFb");	// reinforce the most difficult learning
					$allFailed = array_unique(array_merge($contents, $difficult, $tmpDiff));

					$attempts ++;

					if ($attempts > $this->maxAttempts || (count($contents) - 1 < $this->tolerance &&
														   count($lastFailed) - 1 < $this->tolerance)) {
						break;
					}
				}
			}

			Common::trace("Batch done!", Common::DEBUG_INFO);
		}

		fclose($fp);
	}
}

