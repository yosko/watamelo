<?php
$p['pageTitle'] = "Error : access forbidden";
include $tplPath.'header.tpl.php';
?>

        <header><h3><?php echo $p['pageTitle']; ?></h3></header>
        <p>You are not allowed to access this page or file.</p>
        <p>Sorry.</p>
        <p>Go back to <a href="<?php echo $p['rootUrl']; ?>">the homepage</a>.</p>
<?php include $tplPath.'footer.tpl.php'; ?>