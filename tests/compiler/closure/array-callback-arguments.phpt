--TEST--
Closures stored in an array preserve arguments across different signatures
--FILE--
<?php
namespace P {
    class Sub {
        /** @var array<int, \Closure> */
        public array $handlers = [];

        public function add(\Closure $cb): void {
            $this->handlers[] = $cb;
        }

        public function fire(): void {
            foreach ($this->handlers as $cb) {
                echo $cb($this), "\n";
            }
        }

        public function direct(): void {
            echo ($this->handlers[0])($this), "\n";
        }
    }
}
namespace {
    function main() {
        $s = new \P\Sub();
        $s->add(function (\P\Sub $self) use ($s): string {
            return $self === $s ? "required:ok" : "required:wrong";
        });
        $s->add(function (\P\Sub $source, int $value = 42): string {
            return get_class($source) . ":" . $value;
        });
        $s->add(function (\P\Sub $origin, ...$rest): string {
            return get_class($origin) . ":" . count($rest);
        });
        $s->add(fn(\P\Sub $item): string => get_class($item));
        $s->fire();
        $s->fire();
        $s->direct();
    }
}
?>
--EXPECT--
required:ok
P\Sub:42
P\Sub:0
P\Sub
required:ok
P\Sub:42
P\Sub:0
P\Sub
required:ok
