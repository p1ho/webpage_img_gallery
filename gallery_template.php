<?php
  require "img_link_fetcher.php";

  $link = $_POST["url"] ?? "";

  // fetch img links and messages
  $img_links   = img_link_fetcher::get_img_links($link);
  $error_msg   = img_link_fetcher::get_error_msgs();
  $warning_msg = img_link_fetcher::get_warning_msgs();

  // shorten long url's for display
  $link_print = (count(str_split($link)) > 40) ? substr($link, 0, 40)."..." : $link;
?>

<!doctype html>
<html lang="en">
<head>
  <title>Pics from <?php echo $link_print; ?></title>
  <style>
  h1, h2, h3, p {
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
  <form action="gallery_template.php" method="post">
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
  <br>
  <?php
  // Checking for Error
  if (count($error_msg) !== 0) {
    echo strip_tags("<details style='background-color:#ffe6e6'><summary>Errors</summary><div>", '<details><summary><div>');
    foreach ($error_msg as $msg) {
      echo strip_tags("<p>$msg</p>\n  ", '<p>');
    }
    echo strip_tags("</div></details>", '<details><div>');
  }

  // Checking for Warning
  if (count($warning_msg) !== 0) {
    echo strip_tags("<details style='background-color:#ffffe6'><summary>Warnings</summary><div>", '<details><summary><div>');
    foreach ($warning_msg as $msg) {
      echo strip_tags("<p>$msg</p>\n  ", '<p>');
    }
    echo strip_tags("</div></details>", '<details><div>');
  }

  // Outputs Images
  foreach ($img_links as $img_link) {
    echo strip_tags('<a href="'.$img_link.'" target="_blank">'.
                      '<div class="img-wrapper">'.
                        '<img src="'.$img_link.'" alt="" />'.
                      '</div>'.
                    '</a>'.
                    "\n  ", '<div><a><img>');
  }
  ?>
</body>
</html>
