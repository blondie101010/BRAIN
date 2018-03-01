# BRAIN

Binary Reasoning Artificial Intelligence Network - A self-enclosed prediction system.


#### Table of contents

[Introduction](#introduction)<br/>
[Applications](#applications)<br/>
[Input caracteristics](#input-caracteristics)<br/>
[Outcome description](#outcome-description)<br/>
[Learning process](#learning-process)<br/>
[Back-track mode](#back-track-mode)<br/>
[AI implementation details](#ai-implementation-details)<br/>
[Usage](#usage)<br/>
[Cleanup logic](#cleanup-logic)<br/>
[Upgrade process](#upgrade-process)<br/>
[Why PHP](#why-php)<br/>
[How can the code be so simple?](#how-can-the-code-be-so-simple)<br/>
[Important design note](#important-design-note)<br/>
[Current status](#current-status)
[Future](#future)


## Introduction

BRAIN was developed as a mean to optimize multi-variant prediction making.  After sufficient learning, it exceeds statistical analyses which mainly deal with linear functions, but can not take into account thresholds, holes, and variable acceleration in predictive models.

All that is needed to get BRAIN to make predictions is to provide it as much data as possible with the actual outcome.  Then you can provide new data and ask it for the expected outcome.  The BRAIN's answer is relative to the provided real outcomes.

While it does not know anything about reality, it can take various data from the past, along with their real outcome, and make rules that allow it to predict the future with new data.


## Applications

The main benefit of BRAIN is to find deterministic patterns which allow it to predict the outcome.

Its domain of application is extremely vast and can include human behaviour, engineering, health, market trends, and many many more.

Its efficiency resides in the data that is fed to it.  You can not expect it to predict something based on data which doesn't show the event happening multiple times.

Its precision on the long run far exceeds the limits of statistical analysis.


## Input caracteristics

The BRAIN takes a single associative PHP array as its input.  The data array can include a variety of PHP types in the input data including single-level sub-arrays, strings, numbers, etc.  The only absolute exclusion is PHP's NULL which would affect the BRAIN's operation.

Note that, although functional, using strings in the data values makes it more difficult to establish proper rules.  It's therefore preferable to give them numeric equivalents if possible.

If the input data does not already include progression or trend indicators, sampling is recommended by combining a number of data records.  The BRAIN provides an `arrayCombine()` method to do so which accepts a variable number of single-level arrays and regroups them by field.  Note that it exclusively looks at the first array's field list for integrity and performance reasons.

While adding and removing input fields, or changing their data types is supported, it will force the BRAIN to make new logic to accomodate those changes, making it bigger, consuming more memory and taking more CPU to do the job.

**IMPORTANT NOTE**:  Do not change the way the input data is prepared or otherwise it could have drastic effects on the BRAIN's operation and it may cause it to lose a lot of knowledge.


## Outcome description

The real outcome is refered to as $result in the code and must be between -1 and +1 where 0 is the closest to undefined or null.  A result of 1 would typically be the utmost yes, 0 would be neutral and -1 would be a definite no.  Internally, the BRAIN will consider input $result to be in 3 categories:  -1 <= $result < -0.33, -0.33 <= $result <= 0.33, 0.33 < $result <= 1

Learned variances in the 3 resulting outcome categories will affect the average of that outcome.  This allows for more precise answers.  For example, if we want to predict profit, we start by defining it on the -1 to +1 scale.  Whilst it could be defined as:
- -1 = lose everything<br/>
- 0 = being even<br/>
- 1 = doubling the investment<br/>

.. it could be much more precise if we amplify it like:
- -1 = lose 50% or more<br/>
- 0 = being even<br/>
- 1 = gain 50% or more<br/>


## Learning process

The BRAIN passes the input data to its master Link nodes along with the provided outcome.  If a Link node detects that the answer it would have provided is different than the outcome, it replaces the answer with a Condition node which will make a rule based on the data.


## Back-track mode

Instead of simply getting a numeric answer, the BRAIN can return an array of the outcome computations which includes:

- answer:     the actual answer which is typically returned
- steps:      number of steps followed to get this answer
- experience: the final Answer node's experience
- backTrack:  array of the different Condition node details as they were traversed

Not only is this very useful for troubleshooting, but it can also provide really good insight in finding the factors involved in the actual outcome.


## AI implementation details

Using a supervised deterministic learning model, the BRAIN attempts to define rules that allow it to recognize the expected result.

Its deterministic approach in no way inferes that its environment must be deterministic.  It simply means that giving it a data record that it just learned will always produce the same expected result, instead of integrating new learnings to pools of stats like many AIs do.

BRAIN's environment is or can be:
- deterministic, stochastic or non-deterministic: whatever the environment representing the data, the BRAIN will attempt to find patterns to learn to produce the same result from similar data;
- static or dynamic: BRAIN has no expectations on its environment other than the integrity of the provided data;
- fully or partially observable: the only observable difference in BRAIN's perspective, is its difficulty in finding the right rules if some deciding factors are not provided;
- single-agent: the BRAIN's environment is not meant to be touched by other agents, although it is designed to allow knowledge exchanges with peers; it is planned to implement a master Link chain importing for cooperation;
- unknown: BRAIN does not have any information about what any of its input means except for the data input format specifications;
- mainly episodic: every data record provided is considered totally independent from the rest, but BRAIN supports trend analysis if arrays of sequential inputs are included in the data, which is recommended to improve the possibility of predicting the outcome;
- discrete: it is simply fed independant records, one after another;
- not simulated: the whole process is to blindly attempt to make rules which would detect the outcome from the input data;  simulations should not be used in learning mode as the whole point of BRAIN is to find the patterns where they are not obvious to their human counterparts who would design the simulation.


## Usage

`$brain = new Brain("name", "site", "optionalPath");`

`name` is used for the file name, and `site` is used for the internal master node IDs to allow chain exchanges with peers

`$brain->getAnswer($dataRecord, $result);`
Note that the result is only needed to learn.


## Cleanup logic

In its regular operation, a Link node will check if an underlying Condition node is useless or has a bad rating to avoid clogging the chains for nothing.


## Upgrade process
All changes which would impact a currently operational BRAIN will be handled automatically on the next launch.  This is handled with a version number which is used to run the conversion automatically inside the Brain's instanciation.  While it doesn't apply to any released version yet, the upgrade process was developed and used during development to optimize different aspects without having to restart the learning process.


## Why PHP?

You may think that using such a high level language might be a barrier in dealing with the important processing requirements of AI, but the flexibility of data abstraction done in this language, makes it easy for almost anyone to pass it data from any source without requiring much programming experience.  That is also useful for the BRAIN's operations on rules corresponding to that data.

Making a C equivalent, which was initially considered, may not provide much improvement since it would have to implement many features of the PHP engine.  It was initially considered more because BRAIN's memory usage was way too high, but after simply getting rid of all arrays, it got down by more than 90%.  C's threading efficiency might still make it an interesting prospect, as opposed to PHP's nothing shared threading which makes it much slower.


## How can the code be so simple?

The big work was to establish how things should be done, not actually doing them which is indeed easy once the design is complete.  Everything is merely a comparison between two values which determines the direction to take.

Of course there is still work to do to improve the system.  Some potential improvements are mentioned in the [Future](#future) section.


## Important design note

BRAIN is a self-enclosed system which doesn't take any dependencies from the outside.  This makes it more reliable and much easier to integrate.  Because the master chains are imported, they can not get their dependencies injected and are therefore self-reliant.


## Current status

The code is being cleaned up to do a first upload.  This should happen withing the next week or two.

That version will be functional and fairly well tested, but without an automated testing system yet.  Unit testing automation will come after the release of the learning script and the integration to Composer.


## Future

- A simple learning script is being developed to be used as is or as a sample.
- Provide sample data to show how it works.  This will be prepared when the learning script is released, or shortly after.  
- Adding options to change arbitrary thresholds, mainly related to the cleanup process.
- Find ways to better improve the cleanup process, but finding such rules is quite tricky, especially without impacting its performance and efficiency.
- Additional trend analysis may be added in the Condition, but a lot of benchmarks need to be done to see if it would be beneficial since it is already quite efficient in finding patterns and varying the rules could offer very little improvement.
