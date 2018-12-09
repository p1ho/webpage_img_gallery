<?php
  //This program shows a gallery of pictures from the link provided
  //written by Pokuan Ho, 9/24/2018

  $link = $_POST["url"];
  // Consulted https://www.w3schools.com/Php/php_form_url_email.asp
  if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",$link)) {
    echo "Invalid URL, please try again";
    $img_link_array = array();
  } else {
    $img_link_array = getAllPicLinks($link);
  }

  // shorten long url's for display
  $link_print = (count(str_split($link)) > 40) ? substr($link, 0, 40)."..." : $link;

  /**
   * getAllPicLinks.
   * @param  String $link URL to the website to be scanned
   * @return Array        Array containing img links
   */
  function getAllPicLinks($link) {
    $content = file_get_contents($link);
    $dom = new DOMDocument();
    @$dom->loadHTML($content); //suppress warnings
    $alt_links = $dom->getElementsByTagName("link");

    // because I assume relative picture paths are always in a folder at the
    // root level, I will trim off everything after the main url
    //
    // This might not be a good assumption to make especially for "../" cases,
    // but I would have to add a lot more checks.
    if (substr($link, 0, 4) === "http") {
      // if url is https://www.some-url/ or http://www.some-url/
      $trim_loc = getCharLoc($link, "/", 3);
    } else {
      // if url is www.some-url or some other format
      $trim_loc = getCharLoc($link, "/", 1);
    }

    $link_root = ($trim_loc !== -1) ? substr($link, 0, $trim_loc+1) : $link;

    $imgs = $dom->getElementsByTagName("img");
    $img_link_array = [];
    foreach($imgs as $img) {
      $img_link = $img->getAttribute("src");
      // If img_link is empty
      if (trim($img_link) === "") {
        continue;
      }
      // if img_link is a direct path
      if (substr($img_link, 0, 4) === "http") {
        $img_link_array[] = $img_link;
      // the following are specific relative path cases for img_link
      } else if (substr($img_link, 0, 2) === "//") {
        $img_link_array[] = "https:" . $img_link;
      } else if (substr($img_link, 0, 2) === "./") {
        $img_link_array[] = $link_root . substr($img_link, 1);
      } else if ($img_link[0] === "/"){
        $img_link_array[] = $link_root . $img_link;
      } else {
        $img_link_array[] = $link_root . "/" . $img_link;
      }
    }
    return $img_link_array;
  }

  /**
   * getCharLoc, returns the location of the nth occurence of a character in
   * the string. Returns -1 if not found
   * @param  String    $str
   * @param  Character $char
   * @param  Integer   $n    the nth occurence of $char in the $str (must > 0)
   * @return Integer       Location in the String
   */
  function getCharLoc($str, $char, $n) {
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

  // For debugging
  function show($s) {
    echo "<pre>";
    print_r($s);
    echo "</pre>";
  }
?>
<!doctype html>
<html lang="en">
<head>
  <title>Pics from <?php echo $link_print; ?></title>
  <style>
  h1, h2, p {
    font-family: sans-serif;
    margin: 10px;
  }
  input {
    display: inline-block;
    height: 50px;
    box-sizing: content-box;
    font-size: 2em;
    margin-left: 10px;
  }
  input[name=url] {
    width: 400px;
  }
  input[type=submit] {
    width: 150px;
  }
  .img-wrapper {
    width: 200px;
    height: 200px;
    margin: 10px;
    overflow: hidden;
    border-radius: 10px;
    box-shadow: 0 5px 5px #ccc;
    border: 0;
    display: inline-block;
  }
  .img-wrapper:hover img{
    transform: scale(1.1);
  }
  img {
    width: 100%;
    height: 100%;
    border: 0;
    transition: .2s;
    background-color: #333;
  }
  </style>
</head>
<body>
  <h1>Site Image Fetcher</h1>
  <p>Written by Pokuan Ho</p>
  <p>I would like my codes to be examined with a stricter standard, thanks!</p>
  <form action="gallery.php" method="post">
    <input type="text" name="url" placeholder="https://example.com" pattern="https?://.+" required/>
    <input type="submit" />
  </form>
  <br><br><br>
  <h2>Pictures from
    <a href="<?php echo $link;?>" target="_blank">
      <?php echo $link_print; ?>
    </a>
  </h2>
  <p>If Adblocker is not turned off, some pictures may not display correctly</p>
  <p>If img has a relative path, it assumes it is stored in a folder in the root directory</p>
  <?php
  foreach ($img_link_array as $img_link) {
    echo '<div class="img-wrapper">'.
            '<a href="'.$img_link.'" target="_blank">'.
              '<img src="'.$img_link.'" alt="" />'.
            '</a>'.
          '</div>'.
          "\n  ";
  }
  ?>
</body>
</html>
