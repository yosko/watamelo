<?php
$pageTitle = "Error : access forbidden";
include $templatePath.'header.tpl.php';
?>

        <header><h3><?php echo $pageTitle; ?></h3></header>
        <p>You are not allowed to access this page or file.</p>
        <p>Sorry.</p>
        <p>Go back to <a href="<?php echo $rootUrl; ?>">the homepage</a>.</p>
<?php include $templatePath.'footer.tpl.php'; ?>