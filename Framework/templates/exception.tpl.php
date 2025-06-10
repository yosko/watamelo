<?php

$source = sprintf('%s:%s',
    $exception->getFile(),
    $exception->getLine()
);
$message = sprintf('%s(%s): %s',
    get_class($exception),
    $exception->getCode(),
    $exception->getMessage()
);
$trace = $exception->getTrace();
//d($source, $message, $trace);

// TODO: rework CSS location
?>

<article class="error">
    <style scoped>
        @import "<?php echo $templateUrl; ?>css/style.css"
    </style>
    <h2><?php echo $message; ?></h2>
    <p><?php echo $source; ?></p>
    <p>Trace:</p>
    <ol><?php
    foreach ($trace as $row) { ?>

        <li>
            <strong><?php printf('%s%s%s(...)', $row['class'], $row['type'], $row['function']); ?></strong>
            <br>
            <?php if (isset($row['file'])) { printf('%s:%s', $row['file'], $row['line']); } ?>
        </li><?php

    } ?>

    </ol>
</article>

