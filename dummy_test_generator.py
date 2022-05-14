import os
import random


def create_dummy_test_files(directory, num_classes, max_test_cases_per_class):
    """
    Generate dummy test class files.
    :param directory:
    :param num_classes:
    :param max_test_cases_per_class:
    :return:
    """
    for n in range(num_classes):
        with open(os.path.join(directory, "test_class_{}.py".format(n)), "w") as f:
            content = "import unittest\n\n\nclass {}(unittest.TestCase):\n".format("Dummy" + str(n) + "Test")
            for i in range(random.randint(1, max_test_cases_per_class)):
                content += "    def test_{}(self):\n        self.assertTrue(True)\n\n".format(i)
            f.write(content)


if __name__ == '__main__':
    create_dummy_test_files("tests", 100, 20)
