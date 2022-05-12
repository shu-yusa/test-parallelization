import heapq
import unittest
from unittest import TestSuite, TextTestRunner
from collections import namedtuple
from concurrent.futures import ProcessPoolExecutor
from typing import List

TestGroup = namedtuple("TestGroup", "num_classes num_cases classes")


def split_test_classes(num_groups: int, test_suites: List[TestSuite]) -> List[TestGroup]:
    # Sort test suites by descending order of the included test cases
    test_suites.sort(key=lambda x: x.countTestCases(), reverse=True)
    # The second element is used to determine the order when group sizes are equal for multiple groups
    heap = [(0, 0, []) for _ in range(num_groups)]
    for n, test_suite in enumerate(test_suites):
        group_size, _, test_group = heapq.heappop(heap)
        test_group.append(test_suite)
        heapq.heappush(heap, (group_size + test_suite.countTestCases(), n, test_group))
    return [TestGroup(len(test_group), group_size, test_group) for group_size, _, test_group in heap]


def run_grouped_tests(test_group: TestGroup) -> bool:
    suite = TestSuite(test_group.classes)
    result = TextTestRunner().run(suite)
    return result.wasSuccessful()


def run_all_tests():
    num_groups = 4

    test_classes: List[TestSuite] = list(unittest.TestLoader().discover("tests"))
    test_groups = split_test_classes(num_groups, test_classes)

    print("[Grouped Tests]")
    for idx, test_group in enumerate(test_groups):
        print("Group-{}: {} classes, {} cases".format(idx, test_group.num_classes, test_group.num_cases))

    with ProcessPoolExecutor(max_workers=len(test_groups)) as executor:
        results = list(executor.map(run_grouped_tests, test_groups))
        print("[Test Results]")
        for idx, result in enumerate(results):
            print("Group-{}: {}".format(idx, ['NG', 'OK'][result]))
        return results


if __name__ == '__main__':
    run_all_tests()
