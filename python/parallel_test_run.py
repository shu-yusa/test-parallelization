import heapq
import os
from collections import namedtuple
from concurrent.futures import ProcessPoolExecutor
from typing import List, Optional
from unittest import TestLoader, TestSuite, TextTestRunner

TestGroup = namedtuple("TestGroup", "num_classes num_cases classes")


def split_test_classes(test_suites: List[TestSuite], num_groups: int) -> List[TestGroup]:
    """
    Split given test suites (test classes) into the given number of groups in a way that
    each group contains as same number of test cases as possible.
    :param num_groups:
    :param test_suites:
    :return:
    """
    if len(test_suites) <= num_groups:
        # If the number of test classes is lower than the number of test groups,
        # let each group contain one test class.
        return [
            TestGroup(1, test_suite.countTestCases(), [test_suite])
            for test_suite in test_suites
        ]

    # Sort test suites by descending order of the included test cases.
    test_suites.sort(key=lambda x: x.countTestCases(), reverse=True)
    # The second element is used to determine the order when group sizes are equal for multiple groups.
    heap = [(0, 0, []) for _ in range(num_groups)]
    for n, test_suite in enumerate(test_suites):
        group_size, _, test_group = heapq.heappop(heap)
        test_group.append(test_suite)
        heapq.heappush(heap, (group_size + test_suite.countTestCases(), n, test_group))
    return [TestGroup(len(test_group), group_size, test_group) for group_size, _, test_group in heap]


def run_test_group(test_group: TestGroup) -> bool:
    suite = TestSuite(test_group.classes)
    result = TextTestRunner().run(suite)
    return result.wasSuccessful()


def run_tests(test_suites: List[TestSuite], num_groups: int, test_group_indices: Optional[List[int]]):
    test_groups = split_test_classes(test_suites, num_groups)
    if test_group_indices:
        # Pick up test groups to run by given indices.
        test_groups = [
            test_groups[idx] for idx in test_group_indices
            if idx < len(test_groups)
        ]
    if not test_groups:
        return

    print("[Grouped Tests]")
    for idx, test_group in enumerate(test_groups):
        print("Group-{}: {} classes, {} cases".format(idx, test_group.num_classes, test_group.num_cases))

    with ProcessPoolExecutor(max_workers=len(test_groups)) as executor:
        results = list(executor.map(run_test_group, test_groups))
        print("[Test Results]")
        for idx, result in enumerate(results):
            print("Group-{}: {}".format(idx, ['NG', 'OK'][result]))


if __name__ == '__main__':
    default_parallelization_factor = 4
    test_group_indices = os.environ.get("TEST_GROUP_INDICES")
    if test_group_indices:
        test_group_indices = [int(idx) for idx in test_group_indices.split(",")]

    num_parallelization = int(os.environ.get("NUM_PARALLELIZATION", default_parallelization_factor))
    test_classes: List[TestSuite] = list(TestLoader().discover("tests"))
    run_tests(test_classes, num_parallelization, test_group_indices)
