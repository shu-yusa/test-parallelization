# Python test parallelization sample
This sample code demonstrates how one can parallelize test execution in Python.
Two levels of parallelizations are made in this sample. That is, parallelization in a computing instance by multiprocessing, and parallelization with multiple computing instances (GitHub Actions’ Job).
To balance the workload in each process, test classes are split into groups so that the number of test cases in each group becomes as equal as possible.

## Test execution
To run tests, run the following command (confimed with Python3.9)
```bash
python parallel_test_run.py
```
By default, it runs tests with 4 processes. One will see a result like
```
[Grouped Tests]
Group-0: 25 classes, 265 cases
Group-1: 25 classes, 266 cases
Group-2: 25 classes, 266 cases
Group-3: 25 classes, 266 cases
.....................................................................................................................................................................................................................................................................................................
----------------------------------------------------------------------
.Ran 265 tests in 0.003s

OK
...........................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................
.----------------------------------------------------------------------
.Ran 266 tests in 0.003s

.OK
................................................................................................................................................................................................
----------------------------------------------------------------------
Ran 266 tests in 0.004s

OK
...
----------------------------------------------------------------------
Ran 266 tests in 0.004s

OK
[Test Results]
Group-0: OK
Group-1: OK
Group-2: OK
Group-3: OK

```
With giving an environment variable, one can overwrite the parallelization factor
```bash
NUM_PARALLELIZATION=8 python parallel_test_run.py
```
There is another available environment variable called `TEST_GROUP_INDICES`. 
This variable is supposed to have comma-separated integers, and when this is specified,
executed test groups are picked up by these indices.
For example, if you run as follows,

```bash
TEST_GROUP_INDICES=0,1 python parallel_test_run.py
```

you will see a result like 
```
[Grouped Tests]
Group-0: 25 classes, 265 cases
Group-1: 25 classes, 266 cases
..............................................................................................................................................................................................................................................................................................................................................................................................
----------------------------------------------------------------------
.Ran 265 tests in 0.003s

OK
....................................................................................................................................................
----------------------------------------------------------------------
Ran 266 tests in 0.003s

OK
[Test Results]
Group-0: OK
Group-1: OK
```
This variable is intended for the parallelization in GitHub Actions.
