# BRAIN

Binary Reasoning Artificial Intelligence Network - A self-enclosed prediction system.

**Important note:  this is not production ready.  A bug fix update will be released in the next few days along with a demo data generator and learning script.**

This document is a simple introduction to the system.  For more details, consult the [Brain manual](https://blondie101010.github.io/BRAIN/).

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
- $dataRecord: array of fields.  See [Input characteristics - BRAIN manual](https://blondie101010.github.io/BRAIN/#input-caracteristics) for more details.
- $result: value between -1 and +1.  It is evaluated in 3 states: close to -1, close to 0, and close to +1.  `0` typically refers to something like average or unknown.  See [Input characteristics - BRAIN manual](https://blondie101010.github.io/BRAIN/#input-caracteristics)) for more details.


#### Batch processing with the `Feeder` class

update to come in a few days with the full release
