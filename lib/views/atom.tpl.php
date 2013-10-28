<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <title><?php echo $p['feed']['title']; ?></title>
    <subtitle><?php echo $p['feed']['subtitle']; ?></subtitle>
    <link href="<?php echo $p['feed']['self']; ?>" rel="self" />
    <link href="<?php echo $p['feed']['link']; ?>" />
    <id><?php echo $p['feed']['self']; ?></id><?php if(isset($p['feed']['self'])) { ?>

    <updated><?php echo $p['feed']['self']; ?></updated><?php } foreach($p['feed']['items'] as $key => $value) { ?>

    <entry>
        <title><?php echo $value['title']; ?></title>
        <link href="<?php echo $value['link']; ?>" />
        <id><?php echo $value['link']; ?></id>
        <updated><?php echo $value['update']; ?></updated>
        <summary type="xhtml"><div xmlns="http://www.w3.org/1999/xhtml"><?php echo $value['summary']; ?></div></summary><?php if(isset($value['author'])) { ?>

        <author>
            <name><?php echo $value['author']['name']; ?></name>
            <email><?php echo $value['author']['email']; ?></email>
        </author><?php } //if ?>

    </entry><?php } //foreach ?>

</feed>