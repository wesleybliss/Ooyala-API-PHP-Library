<?php
    
    /*
     * Ooyala API PHP Class Library
     * (Ooyala.class.php)
     *
     * @created 2010
     * @author Wesley Bliss | wbliss@episcopalchurch.org
     *
     * @todo General code cleanup; convert comment style to PHPDoc syntax
     *
     */
    
    
    class Ooyala {
        
        protected $partnerCode;
        protected $secretCode;
        
        /*
         * Ooyala API URLs
         * these should only change if Ooyala changes them
         */
        protected $apiQueryURL = 'http://api.ooyala.com/partner/query';
        protected $apiLabelURL = 'http://api.ooyala.com/partner/labels';
        
        
        //
        // initialize the class
        // partner and secret codes required to generate a secure hash
        //
        function __construct( $partnerCode, $secretCode ) {
            $this->partnerCode = $partnerCode;
            $this->secretCode = $secretCode;
        }
        
        //
        // generate a timestamp, in seconds, since the UNIX epoch (now + $minutes)
        //
        private function epochOffset( $minutes ) {
            return time() + ($minutes * 60);
        }
        
        //
        // flattens an array to a string, in the format of
        // $key => $value, becomes $key=$value&$key2=$value2 (etc)
        //
        private function array2querystring( $array, $delimiter = '&', $encodeURI = false ) {
            $qs = '';
            $i = 1;
            foreach ( $array as $k => $v ) {
                $qs .= ( $k . '=' . ($encodeURI ? urlencode($v) : $v) . (($i >= count($array)) ? '' : $delimiter) );
                $i++;
            }
            return $qs;
        }
        
        //
        // generate a SHA256 hash, base64 encoded signature for authentication
        // $params should be an array of your QueryString parameters
        //
        public function generateSignature( $params ) {
            
            //
            // basic workflow here is as follows:
            //      1. flatten parameters to querystring (with NO delimiter)
            //         1.1 do not URI encode the parameters/values yet
            //      2. prepend "secret key" to querystring
            //      3. SHA256 hash that string, returing RAW binary output
            //      4. base64 encode that result
            //      5. truncate the final result to 43 characters
            //
            
            // sort parameters alphabetically
            ksort( $params );
            
            return substr(
                base64_encode(
                    hash(
                        'sha256', ($this->secretCode . $this->array2querystring($params, '')), true
                    )
                ),
            0, 43 );
            
        }
        
        //
        // build a video query URL, consisting of an on-demand generated hash
        // enable $verbose to get back an array of SIGNATURE and QUERY_URL
        //
        public function generateVideoQuery( $params, $verbose = false ) {
            
            // add expiration, 5 minutes
            $params['expires'] = $this->epochOffset( 5 );
            
            // alphabetize array by indices
            ksort( $params );
            
            // generate a signature for validation
            $signature = $this->generateSignature( $params );
            
            // build the full query URL
            $restURL = sprintf(
                '%s?pcode=%s&%s&signature=%s',
                $this->apiQueryURL, urlencode($this->partnerCode),
                $this->array2querystring($params, '&', true), urlencode($signature)
            );
            
            if ( !$verbose ) {
                return $restURL;
            }
            else {
                return array(
                    'SIGNATURE' => $signature,
                    'QUERY_URL' => $restURL
                );
            }
            
        }
        
        //
        // general query for video(s) information
        // returns FALSE if query expired, empty string for other errors
        // TODO: this can be fleshed out a bit more, maybe making
        //       "video query" and "label query" etc. into their own classes??
        //
        public function queryVideos( $params ) {
            
            $queryURL = $this->generateVideoQuery( $params, false );
            
            $xmlResult = @file_get_contents( $queryURL );
            
            if ( strtolower($xmlResult) == 'already expired' ) {
                //throw new ErrorException( 'session or signature has already expired', -1, 1, 'Ooyala.class.php' );
                return false;
            }
            else {
                return $xmlResult;
            }
            
        }
        
        //
        // build a label query URL, consisting of an on-demand generated hash
        // enable $verbose to get back an array of SIGNATURE and QUERY_URL
        //
        public function generateLabelQuery( $params, $verbose = false ) {
            
            // add expiration, 5 minutes
            $params['expires'] = $this->epochOffset( 5 );
            
            // alphabetize array by indices
            ksort( $params );
            
            // generate a signature for validation
            $signature = $this->generateSignature( $params );
            
            // build the full query URL
            $restURL = sprintf(
                '%s?pcode=%s&%s&signature=%s',
                $this->apiLabelURL, urlencode($this->partnerCode),
                $this->array2querystring($params, '&', true), urlencode($signature)
            );
            
            if ( !$verbose ) {
                return $restURL;
            }
            else {
                return array(
                    'SIGNATURE' => $signature,
                    'QUERY_URL' => $restURL
                );
            }
            
        }
        
        //
        // general query for video(s) information
        // returns Array( $labelName => $videoCount )
        // returns FALSE if query expired, empty string for other errors
        // TODO: this can be fleshed out a bit more, maybe making
        //       "video query" and "label query" etc. into their own classes??
        //
        public function queryLabelsList( $params ) {
            
            // set the mode to "list"
            $params['mode'] = 'listLabels';
            
            $queryURL = $this->generateLabelQuery( $params, false );
            
            $xmlResult = @file_get_contents( $queryURL );
            
            if ( strtolower($xmlResult) == 'already expired' ) {
                
                //throw new ErrorException( 'session or signature has already expired', -1, 1, 'Ooyala.class.php' );
                return false;
            
            }
            else {
                
                // parse XML result into an array of labels
                $xmlDoc = new DOMDocument();
                $xmlDoc->loadXML( $xmlResult );
                $xpath = new DOMXPath( $xmlDoc );
                $xmlLabels = $xpath->query( '//label' );
                
                //
                // array format is
                //      KEY   = label name
                //      VALUE = number of videos with that label
                //
                $labels = array();
                foreach ( $xmlLabels as $label ) {
                    $labels[$label->nodeValue] = $label->getAttribute('movieCount');
                }
                
                return $labels;
                
            }
            
        }
        
    } // Ooyala class
    
?>