<?php
$pageTitle = "Error : page not found";
include $templatePath.'header.tpl.php';
?>

        <header><h3><?php echo $pageTitle; ?></h3></header>
        <p>The page or file you are looking for doesn't exist or has been moved.</p>
        <p>Sorry.</p>
        <p>Go back to <a href="<?php echo $rootUrl; ?>">the homepage</a>.</p>
<?php include $templatePath.'footer.tpl.php'; ?>