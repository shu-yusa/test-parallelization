<?php

function create_dummy_test_files($directory, $num_classes, $max_test_cases_per_class)
{
    for ($i = 0; $i < $num_classes; $i++) {
        $test_cases = "";
        for ($j = 0; $j < rand(1, $max_test_cases_per_class); $j++)
        {
            $test_cases .= <<<END
    public function test{$j}()
    {
        sleep(0);
        \$this->assertTrue(true);
    }\n\n
END;


        }

        $content = <<<END
<?php

use PHPUnit\Framework\TestCase;

class Dummy{$i}Test extends TestCase
{
$test_cases}
END;

        file_put_contents($directory . "/Dummy{$i}Test.php", $content);
    }

}

create_dummy_test_files("./tests", 100, 20);
