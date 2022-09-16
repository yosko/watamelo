<?php
$pageTitle = "Error : access forbidden";
include $templatePath.'header.tpl.php';
?>

        <header><h2><?php echo $pageTitle; ?></h2></header>
        <p>You are not allowed to access this page or file.</p>
        <p>Sorry.</p>
        <p>Go back to <a href="<?php echo $baseUrl; ?>">the homepage</a>.</p>
<?php include $templatePath.'footer.tpl.php'; ?>
