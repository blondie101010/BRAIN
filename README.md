# BRAIN

Binary Reasoning Artificial Intelligence Network - A self-enclosed prediction / extrapolation system.

This document is a simple introduction to the system.  For more details, consult the [BRAIN manual](https://blondie101010.github.io/BRAIN/).

## Installation

BRAIN is now in Packagist, so it can be installed with composer as:

    composer require blondie101010/brain

## Usage

### Instanciation

    $brain = new \Blondie101010\Brain\Brain(string $name, string $site, string $brainPath = "./data");

Parameters:
- $name: the name you wish to give that BRAIN, which is used in its filename
- $site: string to define the instance running...  it is used to define the master chain names but is mostly present for future uses such as importing and exporting support;  although it is not enforced and would currently have no impact, it is suggested to use only alphanumeric characters
- $brainPath: directory in which to read and write the BRAIN's export file to.

The BRAIN's file name will be `$brainPath/Brain-$name.dat`.

### Learning

#### Individual record

You can learn an individual record with the following:

    $brain->getAnswer(array $dataRecord, float $result = null);

Parameters:
- $dataRecord: array of fields.  See [BRAIN manual: Input characteristics](https://blondie101010.github.io/BRAIN/#input-caracteristics) for more details.
- $result: value between -1 and +1.  It is evaluated in 3 states: close to -1, close to 0, and close to +1.  `0` typically refers to something like average or unknown.  See [BRAIN manual: Outcome description](https://blondie101010.github.io/BRAIN/#outcome-description) for more details.


#### Batch processing with the `Feeder` class

Here is a simple script to Feed a data file to the BRAIN:

    require "vendor/autoload.php";
    
    use \Blondie101010\Brain\Common;
    use \Blondie101010\Brain\Brain;
    use \Blondie101010\Brain\Feeder;

    Common::$debug = Common::DEBUG_INFO;  // make useful messages visible to see how it progresses

    $inputFile = "demo.dat";
    $brainName = "demoBrain";
    $site = "A";  // site identifier, for future use
    $brainPath = "./data";
    $skip = 0;
    $sample = false;
    $batchSize = 25000;

    $brain = new Brain($brainName, $site, $brainPath);
    $feeder = new Feeder($brain, Feeder::MODE_TEST_AND_LEARN, $skip, $batchSize, $sample);

    echo "Processing $inputFile.  Send SIGTERM to end cleanly (and therefore store new learnings).\n";
    $feeder->processFile($inputFile);

In order to demonstrate how the BRAIN works and provide a sample script, here is the code to use to generate test data:

    $outputFile = "demo.dat";

    /*
     * Data set builder for a simple potential murderer profiler.
     *
     * In order to validate the BRAIN's operations, we need to make sure the criteria used can not be misleading,
     * so we'll make rules to define a potential murderer: green race with (blue eyes or big ears) and >= 15 years old
     *
     * The result will be based on the most intensive crime they did which is on a scale of 1-10 where 5 is a fairly small
     * misdemeanor and 7 is a severe agression which could be fatal.
     *
     * Note that we DO NOT believe that the race or ear size have any impact on the criminality level, and some green guys are really nice.
     */

    // Use ridiculous criteria to evaluate the risk of someone being a murderer in order to test the learning algorithm in a   consistant fashion.
    function rateCriminality(array $person) {
        $result = rand(1, 2);  // base crime level

        if ($person['race'] == 2 && ($person['eyeColor'] == 1 || $person['earSize'] >= 6) && $person['age'] >= 15) {
           $result += rand(6, 7);
        }
        elseif ($person['race'] == 3 && $person['earSize'] > 4) {  // arbitrary rules to fill the middle result (close to 0)
           $result = 5.0;
        }

        // adjust result to be between -1 and +1 for the Brain
        return $result * 0.2 - 1;
    }


    $colors = ['red', 'blue', 'green', 'purple'];
    $data = "";

    for ($i = 0; $i < 50000; $i ++) {
       $result = 0;

       $person= ['race' => array_rand($colors), 'eyeColor' => array_rand($colors),
                 'earSize' => rand(3, 9), 'age' => rand(1, 60)];

       $person['_result'] = rateCriminality($person);

       $data .= serialize($person) . "\n";
    }

    file_put_contents($outputFile, $data);
    
So to test the BRAIN's demo, you basically run the data generator, and then the feeding script on that data file.  The above code will use `./demo.dat` for the demo input data file (to which the data generator writes).  

When you run the feeding script, you'll notice a lot of traces that indicate how it is processing the data.  It basically runs a pass on the batch size requested, then it works on the records which were not learned correctly until it reaches a certain level, retests the whole batch (B test), and if the result is acceptable, it moves to the next batch.  The demo does batches of 25K records while there are 50K records in the data file, so it does two batches when you run it.

As you'll see, the more it progresses the higher and more stable are the different test results.


## Support

There isn't yet a defined support channel other than github here, but we plan on opening a support channel on IRC if there is a demand for it.  Those interested may be able to reach me on FreeNode IRC under the nickname `blondie101010` or on [Glitter](https://gitter.im/Blondie101010-BRAIN/).

If you notice any issues or have a suggestion, be assured that all constructive comments are welcome.

Commercial development and support arrangements are possible on demand.
