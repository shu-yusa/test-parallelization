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
            $test_groups = [];
            foreach ($test_suites as $test_suite) {
                $test_groups[] = new TestGroup(1, $test_suite->count(), [$test_suite]);
            }
            return $test_groups;
        }

        usort($test_suites, function(TestSuite $a, TestSuite $b) {
            if ($b->count() < $a->count()) { return -1; }
            if ($a->count() === $b->count()) { return 0; }
            return 1;
        });
        $heap = new SplMinHeap();
        for ($i = 0; $i < $num_groups; $i++) {
            $heap->insert([0, []]);
        }
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

    public function runTests(array $test_suites, int $num_groups, array $test_group_indices=null): void
    {
        $test_groups = $this->splitTestClasses($test_suites, $num_groups);
        if ($test_group_indices) {
            $partial_group = [];
            for ($i = 0; $i < count($test_group_indices); $i++) {
                if ($i < count($test_groups)) {
                    $partial_group[] = $test_groups[$i];
                }
            }
            if (empty($partial_group)) {
                return;
            }
        }

        print("\n[Grouped Tests]\n");
        foreach ($test_groups as $i => $test_group) {
            print(sprintf("Group-%d: %d classes, %d cases\n", $i, $test_group->numClasses, $test_group->numCases));
        }
        print("\n");
        ob_flush();
        $pids = [];
        foreach ($test_groups as $i => $test_group) {
            $mypid = getmypid();
            print("Forking ...@" . $mypid . "\n");
            ob_flush();
            $pid = pcntl_fork();
            print("forked pid=" . $pid . ", i=" . $i . "\n");

            if ($pid === -1) {
                exit("Error forking...\n");
            } else if ($pid === 0) {
                $mypid = getmypid();
                printf("Child this_id:%d pid:%d\n\n", $mypid, $pid);
                sleep(1);
                $this->runTestGroup($test_group);
                print("EEEEE");
                die();
                exit(0);
            } else {
                $pids[] = $pid;
                $mypid = getmypid();
                printf("Parent this_id:%d pid:%d\n\n", $mypid, $pid);
            }
        }
        pcntl_waitpid(0, $status);
//        while(pcntl_waitpid(2, $status) !== -1) {
//            print("Waiting..\n");
//        }
        print("Status: " . $status . "\n");

        echo "Do stuff after all parallel execution is complete.\n";
        exit(TestRunner::SUCCESS_EXIT);
    }
}

class AppTestSuite extends TestCase
{
    public function test(): void
    {
        $default_parallelization_factor = 3;
        $test_group_indices = getenv("TEST_GROUP_INDICES");
        if ($test_group_indices) {
            $test_group_indices = array_map("intval", explode(",", $test_group_indices));
        }
        $num_parallelization = getenv("NUM_PARALLELIZATION") ?? $default_parallelization_factor;
        $suites = [];
        foreach (glob("./tests/*Test.php") as $file) {
            $suite = new TestSuite();
            $suite->addTestFile($file);
            $suites[] = $suite;
        }
        $runner = new ParallelTestRunner();
        $runner->runTests($suites, $default_parallelization_factor);
        $this->assertTrue(true);
    }
}
