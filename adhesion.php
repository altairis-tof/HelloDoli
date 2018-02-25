<?php

  // Inclusion du client REST
  include('./httpful.phar');
  include('./helloasso.conf');

  if(isset($_POST['id'])
  AND isset($_POST['date'])
  AND isset($_POST['amount'])
  AND isset($_POST['url'])
  AND isset($_POST['payer_first_name'])
  AND isset($_POST['payer_last_name'])
  AND isset($_POST['url_receipt'])
  AND isset($_POST['url_tax_receipt'])
  AND intval($_POST['id'],10))
  {
    updatelog("Nouveau paiment reçu.".PHP_EOL.print_r($_POST,true));

    /* Recherche des détails du paiement via reqûete API HelloAsso */
    $response = \Httpful\Request::get($helloAssoAPIUrl."payments/".$_POST['id'].".json")->authenticateWith($helloAssoUsername, $helloAssoAPIPassword)->send();
    $actions = $response->body->actions;
    echo "<pre>";
    print_r($response);
    echo "</pre>";
    print_r($actions);



  }
  else {
    updatelog('Page appelée avec de mauvais paramètres.');
  }

  function updatelog($str)
  {
    if(is_string($str))
    {
      $logFile = fopen('adhesions.log', 'a');

      fputs($logFile, "**************************************************".PHP_EOL);
      fputs($logFile, date("d/m/y H:i").PHP_EOL);
      fputs($logFile, $str.PHP_EOL.PHP_EOL);

      fclose($logFile);
    }

  }

?>
