--TEST--
Captured ternary converts typed Zend properties at its C++ return boundary
--FILE--
<?php
class CapturedPropertySelection
{
    public int $preferred = 7;
    public int $fallback = 3;
    public float $firstFloat = 1.25;
    public float $secondFloat = 2.5;
    public bool $firstBool = true;
    public bool $secondBool = false;
    public string $firstString = 'left';
    public string $secondString = 'right';
    public array $firstArray = [1];
    public array $secondArray = [2];

    public function values(bool $flag): array
    {
        // Unset prevents hoisting this property as a stable C++ scalar slot.
        if ($flag) { unset($this->preferred); $this->preferred = 7; }
        if ($flag) { unset($this->firstFloat); $this->firstFloat = 1.25; }
        $integer = $this->preferred > 0 ? $this->preferred : $this->fallback;
        $scalarFloat = $this->firstFloat > 0.0 ? $this->firstFloat : $this->secondFloat;
        $scalarBool = strlen('x') > 0 ? $this->firstBool : $this->secondBool;
        $text = strlen('x') > 0 ? $this->firstString : $this->secondString;
        $items = strlen('x') > 0 ? $this->firstArray : $this->secondArray;
        return [$integer, $scalarFloat, $scalarBool, $text, $items,
            $flag ? $this->preferred : $this->fallback];
    }
}

function main(): void
{
    $selection = new CapturedPropertySelection();
    var_dump($selection->values(true));
    $selection->preferred = 0;
    var_dump($selection->values(false));
}
?>
--EXPECT--
array(6) {
  [0]=>
  int(7)
  [1]=>
  float(1.25)
  [2]=>
  bool(true)
  [3]=>
  string(4) "left"
  [4]=>
  array(1) {
    [0]=>
    int(1)
  }
  [5]=>
  int(7)
}
array(6) {
  [0]=>
  int(3)
  [1]=>
  float(1.25)
  [2]=>
  bool(true)
  [3]=>
  string(4) "left"
  [4]=>
  array(1) {
    [0]=>
    int(1)
  }
  [5]=>
  int(3)
}
