The BRAIN's learning is done by discriminating records that don't have the same outcome -- the outcome being -1 to +1 considered in 3 equal sections.  It therefore follows its existing rules until it gets an answer.  

If the answer is right, it confirms it by increasing the logic chain's ratings.  When the answer is wrong, it gets destroyed and replaced with a new condition that matches with the current record.

## Learning class

The `Learning` class handles the input data learning process to ensure a proper level of discrimination was attained before moving on.

Roughly speaking, it starts by testing the batch of records to find the invalid answers, learns the whole batch, and then loops through the list of previous invalid answers until it explains them all or encounters a limit control.

After that, it checks and learns the full batch, and restarts if the test was too low and no control limit was reached.  Note that these limits are meant to prevent continuously looping because of incompatible records or hard to find criteria.

While it may give the impression that the testing pass could be a waste of time, the discriminating approach of gathering the most conflicting records drastically improves the learning curve.
