# TweetMunger

Translate _(mung)_ tweets from a specific user account through several languages and back, then re-tweet from a new account.


## Set up

Setting up a new TweetMunger account will require a few accounts:

  * Create a new [Twitter](http://twitter.com/) account 
  * [Register a new app](https://dev.twitter.com/) with the twitter account
  * [Register for a Bing App account](http://www.bing.com/developers/createapp.aspx) (or a [Google Translate API Key](http://code.google.com/apis/language/translate/v2/getting_started.html), but this may cease to exist from December 2011)


## Init

Set up a script on a (php-enabled) web server that calls the TweetMunger class.  The following is one example of how you could do it:

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

### Init options explained

  * *debugMode*: Set to true to output content only in the browser, false to post to twitter
  * *originalTwitterAccount*: The Twitter account we're copying from 
  * *mungedTwitterAccount*: The Twitter account we're posting to
  * *userAgentAccount*: The email account used by TwitterSearch class to tell Twitter who's calling
  * *newTweetCount*: How many new tweets to translate each time
  * *ignoreRetweets*: Retweets can sometimes confuse Twitter Munger due to inconsistent IDs so ignore by default
  * *translationService*: Are we using Google or Bing to translate?
  * *originalLanguage*: The language of the original post
  * *translatableLanguages*: List of languages to translate posts through (These are different depending on whether we're using [Bing](http://msdn.microsoft.com/en-us/library/dd877886.aspx) or [Google](http://code.google.com/apis/language/translate/v1/reference.html#LangNameArray) to translate)
  * *bingAppId*: Bing API Key - Needed for any translation requests (see [here](http://msdn.microsoft.com/en-us/library/ff512421.aspx) and [here](http://www.bing.com/developers/createapp.aspx))
  * *googleTranslateApiKey*: Google Translate API Key - Needed for any translation requests (see [here](http://code.google.com/apis/language/translate/v2/getting_started.html))
  * *twitterConsumerKey*, *twitterConsumerSecret*, *twitterConsumerOauthToken* and *twitterConsumerOauthSecret*: Twitter Authorisation tokens -  [Register a new app](https://dev.twitter.com/) for these
  
  
## Automate

Set up a cron job on your server to call the above script every xx minutes/hours.  The following is one example:

    # call script four times an hour
    0,15,30,45 * * * * curl http://xxx.yy.zz/tweetmunger/ >/dev/null 2>&1