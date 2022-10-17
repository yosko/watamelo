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

?>

<article class="error">
    <style scoped>
        @import "<?php echo $templateUrl; ?>css/style.css"
    </style>
    <ol>
        <li>
            <strong><?php echo $message; ?></strong>
            <br>
            <?php echo $source; ?>
        </li><?php
    foreach ($trace as $row) { ?>

        <li>
            <strong><?php printf('%s%s%s(...)', $row['class'], $row['type'], $row['function']); ?></strong>
            <br>
            <?php printf('%s:%s', $row['file'], $row['line']); ?>
        </li><?php

    } ?>

    </ol>
</article>

