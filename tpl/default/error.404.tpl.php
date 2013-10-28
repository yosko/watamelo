<?php
$p['pageTitle'] = "Error : page not found";
include $tplPath.'header.tpl.php';
?>

        <header><h3><?php echo $p['pageTitle']; ?></h3></header>
        <p>The page or file you are looking for doesn't exist or has been moved.</p>
        <p>Sorry.</p>
        <p>Go back to <a href="<?php echo $p['rootUrl']; ?>">the homepage</a>.</p>
<?php include $tplPath.'footer.tpl.php'; ?>