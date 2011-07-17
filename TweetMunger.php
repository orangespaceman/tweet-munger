<?php 
/*
 * Class to mung tweets - 
 * detect new tweets from a specified account
 * translate into 'foreign' and back 
 * post to new twitter account
 *
 * @author Pete G
 */

require_once('Twitteroauth.php');
require_once('TwitterSearch.php');

/**
 * Tweet Munger Class
 */
class TweetMunger {
    
    /**
     * The Twitter Search object
     *
     * @var object
     */
    protected $twitterSearch;

    /**
     * Set to true to output content only in the browser, false to post to twitter.
     *
     * @var bool
     */
    protected $debugMode = false;
    
    /**
     * The Twitter account we're copying from 
     *
     * @var string
     */
    protected $originalTwitterAccount;
    
    /**
     * The Twitter account we're posting to
     * 
     * @var string
     */
    protected $mungedTwitterAccount;

    /**
     * The email account used by TwitterSearch class to tell Twitter who's calling
     *
     * @var string
     */
    protected $userAgentAccount;

    /**
     * How many new tweets to translate each time
     * Good to set a limit in case the account gets heavy use
     * 
     * @var int
     */
    protected $newTweetCount = 10;

    /**
     * Retweets can sometimes confuse Twitter Munger due to inconsistent IDs so ignore by default
     *
     * @var bool
     */
    protected $ignoreRetweets = true;
    
    /**
     * Are we using Google or Bing to translate?
     * 
     * @var string
     */
     protected $translationService = "bing";

    /**
     * The language of the original post
     * 
     * @var string
     */
    protected $originalLanguage = "en";
    
    /**
     * List of languages to translate posts through
     * These are different depending on whether we're using Bing or Google to translate:
     * Google - http://code.google.com/apis/language/translate/v1/reference.html#LangNameArray
     * Bing - http://msdn.microsoft.com/en-us/library/dd877886.aspx
     * 
     * @var array
     */
    protected $translatableLanguages = array();
    
    /**
     * Google Translate API Key
     * Needed for any translation requests
     *
     * http://code.google.com/apis/language/translate/v2/getting_started.html
     *
     * @var string  
     */
    protected $googleTranslateApiKey;
    
    /**
     * Bing API Key
     * Needed for any translation requests
     *
     * http://msdn.microsoft.com/en-us/library/ff512421.aspx
     * http://www.bing.com/developers/createapp.aspx
     * 
     * @var string
     */
    protected $bingAppId;
    
    /**
     * Twitter Authorisation tokens
     * Register a new app at https://dev.twitter.com/
     *
     * @var string
     */
    protected $twitterConsumerKey;
    
    /**
     * Twitter Authorisation tokens
     *
     * @var string
     */
    protected $twitterConsumerSecret;
    
    /**
     * Twitter Authorisation tokens
     *
     * @var string
     */
    protected $twitterConsumerOauthToken;
    
    /**
     * Twitter Authorisation tokens
     *
     * @var string
     */
    protected $twitterConsumerOauthSecret;
    
    
    /**
     * Constructor.
     * Save/overwrite any default settings passed through during instantiation.  
     * (Not the best way to do it, but...)
     * 
     * @var array
     * @return void
     */
    public function __construct($options) {
        
        // save passed values
        foreach ($options as $option => $value) {
            if (property_exists($this, $option)) {
                $this->$option = $value;
            }
        }
        
        // debug
        if ($this->debugMode) {
            $this->debug('<p><em>(Debug mode on, not posting to twitter)</em></p>');
        }
        
        // get the latest tweet from the munged account
        $this->twitterSearch = new TwitterSearch();
        $this->twitterSearch->user_agent = 'phptwittersearch:'.$this->userAgentAccount;
        $latestMungedTweetId = $this->getLatestMungedTweetId();

        // check if there have been any new tweets since this
        $tweets = $this->getLatestTweets($latestMungedTweetId);
        $tweets = array_reverse($tweets);
        
        // loop through all new tweets
        foreach ($tweets as $key => $tweet) {

            // mung text
            $text = $this->mungText($tweet->text, $tweet->id_str);
            
            // condition : if a translation is found, post to twitter
            if (!empty($text)) {
                $this->tweet($text);
            }
        }
    }
    
    
    /**
     * Get the Twitter ID of the latest translated tweet
     * 
     * @return string
     */
    private function getLatestMungedTweetId() {
        $this->twitterSearch->from($this->mungedTwitterAccount);
        $lastMungedTweet = $this->twitterSearch->rpp(1)->results();
        $latestMungedTweetId = @$lastMungedTweet[0]->id_str;
        $this->debug('<p>$latestMungedTweetId: '.$latestMungedTweetId.'</p>');
        return $latestMungedTweetId;
    }
    
    
    /**
     * Get all new tweets since the last munged tweet 
     * 
     * @var string
     * @return array
     */
    private function getLatestTweets($latestMungedTweetId) {
        $this->twitterSearch->from($this->originalTwitterAccount);
        $this->twitterSearch->since($latestMungedTweetId);
        $results = $this->twitterSearch->rpp($this->newTweetCount)->results();
        $this->debug('<p>New Tweet count: '.count($results).'</p>');
        $this->debug('<hr />');
        return $results;
    }
    
    
    /**
     * Translate a tweet
     * 
     * @var string $text 
     * @var int $id
     * @return string
     */
    private function mungText($text, $id) {

        $this->debug('<p>Original Tweet (ID - '.$id.'): ' . $text . '</p>');
        
        // condition : ignore retweet?
        if ($this->ignoreRetweets && strpos($text, "RT") === 0) {
            $this->debug("<p>Retweet found, ignoring...</p>");
            $this->debug('<hr />');
            return false;
        }
        
        // remove content twitter automatically turns into hashtags and user ids - so as not to annoy people!
        $text = strip_tags(trim($text));
        $text = str_replace('@', '_', $text);
        $text = str_replace('#', '_', $text);

        $languageCount = count($this->translatableLanguages);

        // first translation - original language into first translatable language
        $text = $this->translate($text, $this->originalLanguage, $this->translatableLanguages[0]);
        $this->debug('<p>Translation from '.$this->originalLanguage.' into '.$this->translatableLanguages[0].': ' . $text . '</p>');

        // translate through each subsequent language
        for ($counter = 1; $counter < $languageCount; $counter++) { 
            $text = $this->translate($text, $this->translatableLanguages[$counter-1], $this->translatableLanguages[$counter]);
            $this->debug('<p>Translation from '.$this->translatableLanguages[$counter-1].' into '.$this->translatableLanguages[$counter].': ' . $text . '</p>');
        }
        
        // translate back into the original language
        $text = $this->translate($text, $this->translatableLanguages[$languageCount-1], $this->originalLanguage);
        $this->debug('<p>Translation from '.$this->translatableLanguages[$languageCount-1].' into '.$this->originalLanguage.': ' . $text . '</p>');
        
        // return the newly translated text
        return $text;
    }
    
    
    /**
     * Work out whether we're translating through Google or Bing API
     *
     * @var string $text
     * @var string $sourceLang
     * @var string $targetLang
     * @return string 
     */
    private function translate($text, $sourceLang, $targetLang) {
        $translationService = "translate".ucFirst($this->translationService);
        return $this->$translationService($text, $sourceLang, $targetLang);
    }
    
    
    /**
     * one-step google translate
     * http://code.google.com/apis/language/translate/v2/getting_started.html
     *
     * @var string $text
     * @var string $sourceLang
     * @var string $targetLang
     * @return string      
     */
    private function translateGoogle($text, $sourceLang, $targetLang) {
        $url = "https://www.googleapis.com/language/translate/v2?key=";
        $url .= $this->googleTranslateApiKey;
        $url .= "&q=";
        $url .= urlencode($text);
        $url .= "&source=".$sourceLang;
        $url .= "&target=".$targetLang;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        $responseJson = curl_exec($ch);
        curl_close($ch);
        
        $response = json_decode($responseJson);
        $translation = $response->data->translations[0]->translatedText;
        
        return $translation;
    }
    
    
    
    /**
     * one-step bing translate
     * http://msdn.microsoft.com/en-us/library/ff512421.aspx
     * 
     * @var string $text
     * @var string $sourceLang
     * @var string $targetLang
     * @return string
     */
    private function translateBing($text, $sourceLang, $targetLang) {
        $url = "http://api.microsofttranslator.com/v2/Http.svc/Translate?appId=";
        $url .= $this->bingAppId;
        $url .= "&text=";
        $url .= urlencode($text);
        $url .= "&from=".$sourceLang;
        $url .= "&to=".$targetLang;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        $responseXml = curl_exec($ch);
        curl_close($ch);

        $response = strip_tags($responseXml);
        
        return $response;
    }
    
    
    
    /**
     * Tweet the new text (if not in debug mode)
     * 
     * @var text
     * @return void
     */
    private function tweet($text) {
        $tweet = new TwitterOAuth($this->twitterConsumerKey, $this->twitterConsumerSecret, $this->twitterConsumerOauthToken, $this->twitterConsumerOauthSecret);
        if (!$this->debugMode) {
            $post = $tweet->post('statuses/update', array('status' => $text));
        } 
        $this->debug('<p>tweeting: ' . $text . '</p>');
        if (!$this->debugMode) {
            $this->debug("<pre style='font-size:9px;'>");
            $this->debug(print_r($post));
            $this->debug("</pre>");
        }
        $this->debug('<hr />');
    }
    
    
    /**
     * If we're in debug mode, output the debug text to the browser
     */
    private function debug($text) {
//        if ($this->debugMode) {
            echo $text;
//        }
    }
}
