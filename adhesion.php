<?php
  if(isset($_POST['id'])
  AND isset($_POST['date'])
  AND isset($_POST['amount'])
  AND isset($_POST['url'])
  AND isset($_POST['payer_first_name'])
  AND isset($_POST['payer_last_name'])
  AND isset($_POST['url_receipt'])
  AND isset($_POST['url_tax_receipt']))
  {

  }
  else {
    echo PHP_VERSION;
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
