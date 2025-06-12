<?php

namespace Watamelo\Framework;

class Debugger
{
    private bool $init = false;

    public function start(): void
    {
        echo '
<style scoped>
    .debugger .ok { font-weight: bold; color: green; }
    .debugger .ko { font-weight: bold; color: red; }
</style>
<h2>Watamelo Debugger:</h2>
<ul id="debugger" class="debugger">';
        $this->init = true;
    }

    public function log(string $message, string $class = ''): void
    {
        if (!$this->init)
            $this->start();

        echo sprintf('<li class="%s">%s</li>', $class, $message);
    }

    public function end(): void
    {
        echo '</ul>';
    }

    public function __destruct()
    {
        $this->end();
    }
}
