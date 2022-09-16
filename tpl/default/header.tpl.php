<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php if ($pageTitle!='') { echo $pageTitle.' - Watamelo'; } else { echo 'Watamelo - small PHP MVC framework'; } ?></title>
    <link rel="stylesheet" href="<?php echo $templateUrl; ?>css/style.css">
    <link href="<?php echo $templateUrl; ?>img/watamelo-16.png" rel="icon" type="image/x-icon" />
</head>
<body>
<header>
    <div id="title">
        <a id="home" href="<?php echo $baseUrl; ?>">
            <h1>Watamelo</h1>
            <em>small PHP MVC framework</em>
        </a>
    </div>
</header>
<div id="content">
    <section>