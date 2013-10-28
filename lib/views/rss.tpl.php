<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title><?php echo $p['feed']['title']; ?></title>
        <link><?php echo $p['feed']['link']; ?></link>
        <description><?php echo $p['feed']['subtitle']; ?></description>
        <language><?php echo $p['feed']['language']; ?></language>
        <copyright><?php echo $p['feed']['copyright']; ?></copyright>
        <atom:link href="<?php echo $p['feed']['self']; ?>" rel="self" type="application/rss+xml" /><?php foreach($p['feed']['items'] as $key => $value) { ?>

        <item>
            <title><?php echo $value['title']; ?></title>
            <guid isPermaLink="<?php echo isset($value['link'])?'false':'true'; ?>"><?php echo $value['guid']; ?></guid><?php if(isset($value['link'])) { ?>

            <link><?php echo $value['link']; ?></link><?php } //if ?>

            <pubDate><?php echo $value['pubDate']; ?></pubDate>
            <description><![CDATA[<?php echo $value['summary']; ?>]]></description>
        </item><?php } //foreach ?>

    </channel>
</rss>