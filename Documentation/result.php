<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Amordad">
    <meta name="keywords" content="Metagenomics, alignment-free, HMP">
    <meta name="author" content="Wenzheng Li">

    <title>

      Amordad

    </title>

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">

    <!-- Documentation extras -->
    <link href="css/result.css" rel="stylesheet">
    <link href="css/docs.css" rel="stylesheet">

    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <link rel="icon" href="img/amordad.ico">
  </head>

  <body class="bs-docs-home">
    <header class="navbar navbar-static-top bs-docs-nav" id="top" role="banner">
      <div class="container">
        <div class="navbar-header">
          <button class="navbar-toggle" type="button" data-toggle="collapse" data-target=".bs-navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a href="index.php" class="navbar-brand">Amordad</a>
        </div>
        <nav class="collapse navbar-collapse bs-navbar-collapse" role="navigation">
          <ul class="nav navbar-nav">
            <li>
              <a href="FAQ.html">FAQ</a>
            </li>
          </ul>
          <ul class="nav navbar-nav navbar-right">
            <li><a href="http://smithlabresearch.org">SmithLab</a></li>
            <li><a href="https://github.com/smithlabcode/amordad">Repo</a></li>
          </ul>
        </nav>
      </div>
    </header>

    <main>
    <div class="query">
    <?php
    // ob_start();
    // ini_set('display_errors', TRUE);
    $upload_location = '/db/.upload/';
    $uniq_id = uniqid('amordad_upload_');
    $sample = $upload_location.$uniq_id.".cv";

    // if kmers extracted via javascript, write them into a cv file
    if(isset($_POST['kmer-counts'])) {
      $cvfile = fopen($sample, "w") or die("Unable to open file!");
      $file_name = $_POST['file-name'];
      fwrite($cvfile, $file_name."\n");
      $kmer_counts = json_decode($_POST['kmer-counts'], true);
      ksort($kmer_counts, SORT_NUMERIC);
      foreach($kmer_counts as $key => $value) {
        $index_count = "$key $value\n";
        fwrite($cvfile, $index_count);
      }
      fclose($cvfile);
    }

    // if kmer count vector is uploaded
    else
      move_uploaded_file($_FILES["file-select"]["tmp_name"], $sample);

    $url = "http://localhost:18080/query?path=".urlencode($sample);
    $file = file_get_contents($url);
    if($file) {
      $result = json_decode($file, true);
      if(isset($result["error"]))
        echo '<span style="color: red;">'.$result['error'].'</span>';
      else {
        $key_words = array("id", "total", "time");
        $id = $result["id"];
        $total = number_format($result["total"]);
        $time = $result["time"];
        $query = "<div><span class=\"query_header\">$id</span></div>";
        echo "$query<br>\n";
        $num_results = count($result)-count($key_words);
        echo "<span class=number><b>$num_results</b></span> results found<br>\n";
        echo "Searched over <span class=number><b>$total</b></span> "
          ."samples in <span class=number><b>$time</b></span> seconds.<br>\n";
      }
    }
    else
      echo '<br><span style="color: red;">The server currently is down! We will be back soon!</span><br>'."\n";
    ?>
    <p>Results below are sorted by Best Match</p>
    <hr>
    </div> <!-- end of query division -->
    <div class="results">
    <?php
    if(isset($result["error"]) || !$file)
      return;
    require 'mysql_login.php';
    asort($result, SORT_NUMERIC);

    $ini_array = parse_ini_file("/db/.config.ini");
    $meta_fields = $ini_array['meta_columns'];
    $label_options = array("primary", "success", "info", "warning", "danger");
    foreach ($result as $key => $value) {
      if(!in_array($key, $key_words)) {
        echo "$key<br>\n";
        $statement = "select * from sample where id=\"$key\"";
        $row = mysqli_fetch_array(mysqli_query($con, $statement));
        if($row['source'] == "MGRAST" or substr($key, 0, 3) == "mgp") {
          $mgs = substr($key, strpos($key, '_') + 4);
          $url = "http://metagenomics.anl.gov/?page=MetagenomeOverview&metagenome=".$mgs;
        }
        elseif($row['source'] == "EBI" or !isset($row['source'])) {
          $EBI_ID = explode("_", $key);
          $url = "https://www.ebi.ac.uk/metagenomics/projects/"
                 .$EBI_ID[0]."/samples/".$EBI_ID[1]."/runs/"
                 .$EBI_ID[2]."/results/versions/1.0";
        }

        echo "<a href=$url target=_blank>$url</a><br>\n";
        $num_meta = 0;
        foreach ($meta_fields as $mf) {
          $metadata = $row[$mf];
          if($metadata) {
            $label_option = $label_options[$num_meta%count($label_options)];
            echo "<span class=\"label label-$label_option tag\">$mf: $metadata</span>";
            $num_meta += 1;
          }
        }
        echo "<hr>";
      }
    }
    ?>
    </div>
    </main>
    <div style="width: 100%; height:300px;"</div>

    <!-- jQuery (necessary for Bootstrap's Javascript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
    <!-- Bootstrap's Javascript plugins -->
    <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
  </body>
</html>
