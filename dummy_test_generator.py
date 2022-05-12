import random


def create_dummy_test_classes(num_classes, max_test_cases_per_class):
    test_classes = []
    for n in range(num_classes):
        with open("tests/test_class_{}.py".format(n), "w") as f:
            content = "import unittest\n\n\nclass {}(unittest.TestCase):\n".format("Dummy" + str(n) + "Test")
            for i in range(random.randint(1, max_test_cases_per_class)):
                content += "    def test_{}(self):\n        self.assertTrue(True)\n\n".format(i)
            f.write(content)
    return test_classes


if __name__ == '__main__':
    create_dummy_test_classes(100, 20)
