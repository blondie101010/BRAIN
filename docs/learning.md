The BRAIN's learning is done by discriminating records that don't have the same outcome -- the outcome being -1 to +1 considered in 3 equal sections.  It therefore follows its existing rules until it gets an answer.  

If the answer is right, it confirms it by increasing the logic chain's ratings.  When the answer is wrong or inexistent, it gets destroyed and replaced with a new condition based on the current record data.

## `Feeder` class

The `Feeder` class handles the input data processing and is designed to optimize the learning process to ensure a proper level of discrimination was attained before moving on.

Roughly speaking, it starts by testing the batch of records to find the invalid answers, learns the whole batch, and then loops through the list of previous invalid answers until it explains them all or encounters a control limit.

After that, it checks and learns the full batch, and restarts if the test was too low and no limit was reached.  Note that these limits are meant to prevent continuously looping because of incompatible records or hard to find criteria.

While it may give the impression that the testing pass could be a waste of time, the discriminating approach of gathering the most conflicting records drastically improves the learning curve.

### `Feeder` class usage

The `Feeder`'s constructor takes the following arguments:

  Type  Name        Default               Notes
  _____ ___________ _____________________ ________________________________________________________________________________________ 
  Brain $brain      -                     the Brain instance
  int   $mode       MODE_TEST_AND_LEARN   MODE_TEST_AND_LEARN, MODE_TEST_ONLY, and MODE_LEARN_ONLY;  MODE_LEARN_ONLY will not
                                          perform discrimination learning and is therefore *much* less learning efficient
  int   $skip       0                     number of lines to skip in the input
  int   $batchSize  1000                  number of records to process per batch;  this should be as high as available RAM 
                                          permits for efficiency
  bool  $sample     false                 whether it should only process subsets of the input data;  mostly useful for testing
  int   $effort     5                     define the effort used to balance efficiency with error tolerance;  the default is good
                                          for simple relationships but should othewise be increased;  be advised that raising it
                                          too high when there are contradictions in the data can make the task much slower
