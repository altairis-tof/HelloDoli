<?php
  // Inclusion du client REST
  include('./httpful.phar');
  include('./config.php');
  include('./db.php');
  include('./functions.php');

  updatelog("********************************************************");
  updatelog(date("d/m/y H:i"));

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
    // TODO: vérifier que la notification correspond bien à une nouvelle adhésion (utiliser le campaignId ?)

    updatelog("Nouveau paiement reçu.".PHP_EOL.print_r($_POST,true));

    /* Vérification de l'existence de la notification en BDD */
    try {
      $response = $bdd->query('SELECT COUNT(*) FROM payments_notifications WHERE id='.intval($_POST['id'],10));
    } catch (\Exception $e) {
      echo("Erreur lors de la lecture en BDD .".PHP_EOL."Exception :".$e->getMessage());
    }

    $notificationExists = intval($response->fetch()[0],10);
    $response->closeCursor();

    if($notificationExists == 0)
    {
      /* Recherche des détails du paiement via reqûete API HelloAsso */
      $id = str_pad($_POST['id'], 11,'0', STR_PAD_LEFT);
      $id .= '3'; //un '3' n'est pas présent dans la notification HelloAsso mais
      $response = \Httpful\Request::get($helloAssoAPIUrl."actions/".$id.".json")->authenticateWith($helloAssoUsername, $helloAssoAPIPassword)->send();

      $member['lastName'] =  $response->body->last_name;
      $member['firstName'] = $response->body->first_name;
      $member['email'] = $response->body->email;
      // TODO: stocker toutes les infos nécessaires.

      updatelog(print_r($response->body, TRUE));
      // TODO: Faire le traitement Dolibarr

      /* Enregistrement de l'ID du paiement en BDD. */
      try {
        $bdd->exec('INSERT INTO payments_notifications(id) VALUES('.intval($_POST['id'],10).')');
      } catch (\Exception $e) {
        updatelog("Erreur lors de l'enregistrement de la notification en BDD.".PHP_EOL."Exception :".$e->getMessage());
      }
    }
    else {
      updatelog('Notification déjà traitée.');
    }
  }
  else {
    updatelog('Page appelée avec de mauvais paramètres.');
  }
  updatelog("********************************************************");
  updatelog(PHP_EOL);

?>
