<?php

// written by Pokuan Ho, 9/24/2018
// last updated 12/8/2018
// This is a class that takes in a url and returns a list of sanitized img links

class img_link_fetcher {
  private static $error_msg = array();
  private static $warning_msg = array();
  private static $curl;

  // limit types of image we show in case malicious people have .php, for example
  // as suffix to the img src.
  // http://preservationtutorial.library.cornell.edu/presentation/table7-1.html
  //
  // a more complete list of available image types can be found here
  // http://hul.harvard.edu/ois/////systems/wax/wax-public-help/mimetypes.htm
  // but the following is just a proof of concept.
  private static $allowed_img_types = array(
    ".tif"  => "image/tiff",
    ".tiff" => "image/tiff",
    ".gif"  => "image/gif",
    ".jpeg" => "image/jpeg",
    ".jpg"  => "image/jpeg",
    ".jfif" => "image/jpeg",
    ".fpx"  => "image/vnd.fpx",
    ".png"  => "image/png",
    ".bmp"  => "image/bmp",
  );

  /**
   * get_img_links function.
   * Main method that gets called by the user of this class. Takes in a URL and
   * returns a list of sanitized img links.
   *
   * @param  String $url
   * @return array  $sanitized_img_links
   */
  public static function get_img_links($url) {
    // reset error and warnings;
    self::$error_msg = array();
    self::$warning_msg = array();
    self::$curl = curl_init();

    // check url
    if (!self::_url_is_valid($url)) {
      self::$error_msg[] = "Invalid or empty URL Received, please try again.";
      return array();
    }

    // url is valid, continue parsing
    $content = self::_fetch_url_content($url);
    $domain  = self::_get_domain($url);
    $dom     = self::_get_parsed_dom($content);

    $sanitized_img_links = self::_get_sanitized_img_links($dom, $domain);
    curl_close(self::$curl);

    return $sanitized_img_links;
  }

  /**
   * Getter methods
   */
  public static function get_error_msgs() {return self::$error_msg;}
  public static function get_warning_msgs() {return self::$warning_msg;}
  public static function get_allowed_img_types() {
    return array_map(function($x) {
      return substr($x, 1);
    }, array_keys(self::$allowed_img_types));
  }

  /**
   * _fetch_url_content function.
   * Takes the given $url and try to fetch its content.
   *
   * @param  String $url
   * @return mixed       [Returns the content of the request, FALSE if failed]
   */
  private static function _fetch_url_content($url) {
    curl_setopt_array(self::$curl, array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => 1,
    ));
    $result = curl_exec(self::$curl);
    if (curl_errno(self::$curl)) {
      return FALSE;
    }
    $curl_info = curl_getinfo(self::$curl);
    if (strpos($curl_info['content_type'], 'text/html') === FALSE ) {
      return FALSE;
    }
    return $result;
  }

  /**
   * _url_is_valid function.
   * Checks if the URL passed is a valid URL
   *
   * @param  String  $url
   * @return boolean
   */
  private static function _url_is_valid($url) {
    // Consulted https://www.w3schools.com/Php/php_form_url_email.asp
    return preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $url);
  }

  /**
   * _getparsed_dom function.
   * Attempts to parse the content as if its HTML document
   * To log errors when parsing the document, I combined the solutions below:
   * https://stackoverflow.com/questions/6090667/php-domdocument-errors-warnings-on-html5-tags
   * https://stackoverflow.com/questions/12129261/simple-error-checking-if-statement
   * It suppresses the error throw by html-parser so they don't get shown
   * to the users; but we can fetch the suppressed errors and pick out those
   * error objects that are actually errors, and not just warnings.
   *
   * @param  mixed  $content [returned content from curl_request]
   * @return DOMDocument
   */
  private static function _get_parsed_dom($content) {
    $dom = new DOMDocument();
    libxml_use_internal_errors(TRUE);
    $dom->loadHTML($content);
    $libxml_errors = libxml_get_errors();
    $actual_errors = array();
    foreach ($libxml_errors as $error) {
      if ($error->level == LIBXML_ERR_ERROR || $error->level == LIBXML_ERR_FATAL) {
        $msg = $error->message;
        $line = $error->line;
        self::$error_msg[] = "HTML Parse Error: $msg (Line $line)";
      }
    }
    libxml_clear_errors(); // clear error buffer
    if (count($actual_errors) !== 0) {
      return new DOMDocument();
    }
    return $dom;
  }

  /**
   * _get_domain function.
   * This simply takes in a URL and isolates the domain and return it.
   *
   * I assume relative img paths are always in a folder at the root level, so
   * I trim off everything after the main url.
   * This might not be a good assumption to make when we consider "../" cases,
   * but addressing that is beyond what I hoped to accomplish here.
   *
   * @param  String $url
   * @return string $domain
   */
  private static function _get_domain($url) {
    if (substr($url, 0, 4) === "http") {
      // if url is https://www.some-url/ or http://www.some-url/
      $trim_loc = self::_get_char_loc($url, "/", 3);
    } else {
      // if url is www.some-url or some other format
      $trim_loc = self::_get_char_loc($url, "/", 1);
    }
    return ($trim_loc !== -1) ? substr($url, 0, $trim_loc+1) : $url;
  }

  /**
   * _get_char_loc function.
   * Returns the location of the nth occurence of a character in
   * the string. Returns -1 if not found. This is more useful than strpos()
   * if what you're looking for is not the first occurence of a character
   * in a string.
   * @param  String    $str
   * @param  Character $char
   * @param  Integer   $n    [the nth occurence of $char in the $str (must > 0)]
   * @return integer         [Location in the String]
   */
  private static function _get_char_loc($str, $char, $n) {
    if (count(str_split($char)) > 1) {
      throw new Exception ("Second argument must be a single character.");
    }
    if (!is_int($n)) {
      throw new Exception ("Third argument must be an integer.");
    }
    if ($n <= 0) {
      throw new Exception ("Third argument must be bigger than 0.");
    }
    $char_array = str_split($str);
    $counter = 0;
    for ($i = 0; $i < count($char_array); $i++) {
      if ($char_array[$i] === $char) {
        $counter++;
      }
      if ($counter === $n) {
        return $i;
      }
    }
    return -1;
  }

  /**
   * _get_sanitized_img_links function.
   * This takes in a DOMDocument object and will process all containing imgs.
   * It will end up returning an array of img links that have been checked for:
   * 1) no parameters appended to the url
   * 2) allowed file type (file suffix)
   * 3) allowed mime type (from headers in HEAD request)
   *
   * Note: It would be more secure to actually fetch the image to parse it, but
   * the trade off is significantly longer load time.
   *
   * @param  DOMDocument $dom
   * @param  String      $domain [web domain of this site]
   * @return array      [array of img links]
   */
  private static function _get_sanitized_img_links($dom, $domain) {
    $imgs = $dom-> getElementsByTagName("img");
    $img_links = array();
    foreach($imgs as $img) {
      $img_link = $img->getAttribute("src");

      // If img_link is empty
      if (trim($img_link) === "") {
        continue;
      }

      // remove any parameters attached to img_link
      $img_link_qm_index = strpos($img_link, '?');
      if ($img_link_qm_index !== FALSE) {
        $img_link = substr($img_link, 0, $img_link_qm_index);
      }

      // check img_link suffix against image types allowed
      $img_type = '';
      $img_mime_type = '';
      foreach(self::$allowed_img_types as $file_type => $mime_type) {
        if (strtolower(substr($img_link, -strlen($file_type))) === $file_type) {
          $img_type = $file_type;
          $img_mime_type = $mime_type;
          break;
        }
      }
      // didn't find a match in the allowed list
      if ($img_type === '') {
        self::$warning_msg[] = "'$img_link' has been removed because file type is not allowed.";
        continue;
      }

      // Fixing img_links to have a full URL structure.
      // if img_link is a direct path
      if (substr($img_link, 0, 4) === "http") {
        $img_link = $img_link;
      // the following are specific relative path cases for img_link
      } else if (substr($img_link, 0, 2) === "//") {
        $img_link = "https:" . $img_link;
      } else if (substr($img_link, 0, 2) === "./") {
        $img_link = $domain . substr($img_link, 1);
      } else if ($img_link[0] === "/"){
        $img_link = $domain . $img_link;
      } else {
        $img_link = $domain . "/" . $img_link;
      }

      // actual check content that goes to img_link with HEAD request so we
      // don't output stuff that aren't images or pictures that failed to load.
      // Because of performance issues, we're not using GET to actualy download
      // and parse to see if it's an actual image.
      curl_setopt_array(self::$curl, array(
        CURLOPT_URL => $img_link,
        CURLOPT_HEADER => 1,
        CURLOPT_RETURNTRANSFER => 1,
        // allowing custom requests to make sure GET request is never sent
        // Consulted https://gist.github.com/lixingcong/878a179bf1dce183b194a63135d104c8
        CURLOPT_CUSTOMREQUEST => 'HEAD',
        CURLOPT_NOBODY => 1,
      ));
      $curl_resp = curl_exec(self::$curl);
      if (curl_errno(self::$curl)) {
        self::$warning_msg[] = "'$img_link' has been removed because accessing link failed.";
        continue;
      }

      $curl_info = curl_getinfo(self::$curl);
      if (strpos($curl_info['content_type'], $img_mime_type) === FALSE ) {
        self::$warning_msg[] = "'$img_link' has been removed because its Content-Type is inconsistent with its file type.";
        continue;
      }

      $img_links[] = $img_link;
    }
    return $img_links;
  }
}
