<?php

namespace App;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestResult;
use PHPUnit\Framework\Warning;
use PHPUnit\TextUI\ResultPrinter;
use PHPUnit\TextUI\TestRunner;
use SplMinHeap;
use Throwable;


class TestGroup
{
    public int $numClasses;
    public int $numCases;
    public array $classes;

    public function __construct(int $num_classes, int $num_cases, array $classes)
    {
        $this->numClasses = $num_classes;
        $this->numCases = $num_cases;
        $this->classes = $classes;
    }
}

class ParallelTestRunner
{
    private function splitTestClasses(array $test_suites, int $num_groups): array
    {
        if (count($test_suites) <= $num_groups) {
            // In case the number of TestSuites are lower than the number of expected groups
            $test_groups = [];
            foreach ($test_suites as $test_suite) {
                $test_groups[] = new TestGroup(1, $test_suite->count(), [$test_suite]);
            }
            return $test_groups;
        }

        // Sort TestSuites by descending of the number of TestCases included
        usort($test_suites, function(TestSuite $a, TestSuite $b) {
            if ($b->count() < $a->count()) { return -1; }
            if ($a->count() === $b->count()) { return 0; }
            return 1;
        });
        $heap = new SplMinHeap();
        for ($i = 0; $i < $num_groups; $i++) {
            $heap->insert([0, []]);
        }
        // Fill groups in a greedy way
        foreach ($test_suites as $test_suite) {
            $test_group = $heap->extract();
            $test_group[1][] = $test_suite;
            $heap->insert([$test_group[0] + $test_suite->count(), $test_group[1]]);
        }

        $test_groups = [];
        foreach ($heap as $test_group) {
            $test_groups[] = new TestGroup(
                num_classes: count($test_group[1]),
                num_cases: $test_group[0],
                classes: $test_group[1]);
        }
        return $test_groups;
    }

    public function runTestGroup(TestGroup $testGroup): bool
    {
        $suite = new TestSuite();
        $suite->setTests($testGroup->classes);
        $runner = new TestRunner();
        $result = $runner->run($suite, ["extensions" => []], exit: false);
        return $result->wasSuccessful();
    }

    public function runTests(array $test_suites, int $num_groups, array $test_group_indices=null): bool
    {
        $test_groups = $this->splitTestClasses($test_suites, $num_groups);
        // Pick up test suites to run by indices
        $partial_groups = [];
        if ($test_group_indices) {
            for ($i = 0; $i < count($test_group_indices); $i++) {
                if ($i < count($test_groups)) {
                    $partial_groups[] = $test_groups[$i];
                }
            }
            if (empty($partial_groups)) {
                return true;
            }
        } else {
            $partial_groups = $test_groups;
        }

        print("[Grouped Tests]\n");
        foreach ($partial_groups as $i => $test_group) {
            print(sprintf("Group-%d: %d classes, %d cases\n", $i, $test_group->numClasses, $test_group->numCases));
        }
        ob_flush();
        $children = [];
        foreach ($partial_groups as $test_group) {
            $pid = pcntl_fork();
            if ($pid === -1)
                die("Error forking...\n");

            if ($pid) {
                // Parent process
                $children[] = $pid;
            } else {
                // Child process
                $result = $this->runTestGroup($test_group);
                ob_flush();
                exit($result ? 0 : 1);
            }
        }
        $output = "\n[Test Results]\n";
        $was_all_successful = true;
        // Wait for all child processes to finish
        foreach ($children as $key => $pid) {
            pcntl_waitpid($pid, $status);
            // Extract exit status of the child process
            $status = pcntl_wexitstatus($status);
            $was_successful = $status === 0;
            $was_all_successful &= $was_successful;
            $output .= Sprintf("Group-%s: %s\n", $key, $was_successful ? "OK" : "NG");
        }
        print($output);
        return $was_all_successful;
    }
}

class AppTestSuite extends TestCase
{
    public function testSuite(): void
    {
        $default_parallelization_factor = 3;
        $test_group_indices = null;
        if (getenv("TEST_GROUP_INDICES")) {
            $test_group_indices = array_map("intval", explode(",", getenv("TEST_GROUP_INDICES")));
        }

        $num_parallelization = getenv("NUM_PARALLELIZATION") ?: $default_parallelization_factor;
        // Create TestSuite objects
        $suites = [];
        foreach (glob("./tests/*Test.php") as $file) {
            $suite = new TestSuite();
            $suite->addTestFile($file);
            $suites[] = $suite;
        }

        $runner = new ParallelTestRunner();
        $was_successful = $runner->runTests($suites, $num_parallelization, $test_group_indices);
        $this->assertTrue($was_successful, message: "Test suite was not fully successful");
    }
}
