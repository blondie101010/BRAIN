# BRAIN
Binary Reasoning Artificial Intelligence Network - A self-enclosed prediction system.

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

# Usage
$brain = new Brain("name", "site");

$brain->getAnswer($dataRecord, $result);                           // where the result is only needed to learn


**Important design note**: This is a self-enclosed system which doesn't take any dependencies from the outside.  This makes it more reliable and much easier to integrate.  Because the master chains are imported, they can not get their dependencies injected and are therefore self-reliant.
