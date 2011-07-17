<?php 
    require_once('TweetMunger.php');
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Tweet Munger</title>     
    </head>
    <body>
        <h1>Tweet Munger</h1>
        <?php
        
            $tweetMunger = new TweetMunger(array(
                'debugMode' => false,
                'originalTwitterAccount' => 'xxx',
                'mungedTwitterAccount' => 'yyy',
                'userAgentAccount' => 'xxx@yyy.com',
                'newTweetCount' => 10,
                'ignoreRetweets' => true,
                'translationService' => 'bing',
                'originalLanguage' => 'en',
                //'translatableLanguages' => array('hu', 'zh-TW', 'cy'), // for Google
                'translatableLanguages' => array('Ru', 'zh-CHT', 'Pl'), // for Bing

                'bingAppId' => 'xxx',
                'googleTranslateApiKey' => 'yyy',

                'twitterConsumerKey' => 'www',
                'twitterConsumerSecret' => 'xxx',
                'twitterConsumerOauthToken' => 'yyy',
                'twitterConsumerOauthSecret' => 'zzz'
            ));
        ?>
    </body>
</html>