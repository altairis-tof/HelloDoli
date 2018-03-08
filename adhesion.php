<?php

// Inclusion du client REST
include('./httpful.phar');
include('./config.php');
include('./db.php');
include('./functions.php');

updatelog("********************************************************");
updatelog(date("d/m/y H:i"));

$notification['id'] = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$notification['date'] = filter_input(INPUT_POST, 'date');
$notification['amount'] = filter_input(INPUT_POST, 'amount');
$notification['amount'] = str_replace(',', '.', $notification['amount']);
$notification['url'] = filter_input(INPUT_POST, 'url', FILTER_VALIDATE_URL);
$notification['payer_first_name'] = filter_input(INPUT_POST, 'payer_first_name');
$notification['payer_last_name'] = filter_input(INPUT_POST, 'payer_last_name');
$notification['url_receipt'] = filter_input(INPUT_POST, 'url_receipt', FILTER_VALIDATE_URL);
$notification['url_tax_receipt'] = filter_input(INPUT_POST, 'url_tax_receipt', FILTER_VALIDATE_URL);

foreach ($notification as $value) {
    if (!$value) {
        updatelog('Page appelée avec de mauvais paramètres.');
        die();
    }
}

// TODO: vérifier que la notification correspond bien à une nouvelle adhésion (utiliser le campaignId ?)

updatelog("Nouveau paiement reçu." . PHP_EOL . print_r($notification, true));

/* Vérification de l'existence de la notification en BDD */
try {
    $response = $bdd->query('SELECT COUNT(*) FROM payments_notifications WHERE id=' . $notification['id']);
} catch (\Exception $e) {
    echo("Erreur lors de la lecture en BDD ." . PHP_EOL . "Exception :" . $e->getMessage());
}

$notificationExists = intval($response->fetch()[0], 10);
$response->closeCursor();

if ($notificationExists == 0) {
    /* Recherche des détails du paiement via reqûete API HelloAsso */
    $id = str_pad($notification['id'], 11, '0', STR_PAD_LEFT);
    $id .= '3'; //un '3' n'est pas présent dans la notification HelloAsso mais
    $response = \Httpful\Request::get($helloAssoAPIUrl . "actions/" . $id . ".json")
            ->authenticateWith($helloAssoUsername, $helloAssoAPIPassword)
            ->expectsJson()
            ->send();
    updatelog(print_r($response->body, TRUE));
    
    $member['lastname'] = $response->body->last_name;
    $member['firstname'] = $response->body->first_name;
    $member['email'] = $response->body->email;
    $member['morphy'] = "phy";
    $member['address'] = $response->body->custom_infos[2]->value;
    $member['zip'] = $response->body->custom_infos[6]->value;
    $member['town'] = "kikoo";
    $member['phone_perso'] = $response->body->address;
    $member['phone'] = null;
    $member['public'] = 0;
    $member['statut'] = 1;
    $member['photo'] = null;
    $member['datec'] = "";
    $member['datefin'] = 1511390014;
    $member['datevalid'] = "";
    $member['birth'] = 638575200;
    $member['typeid'] = 2;
    $member['type'] = "Actif";
    $member['need_subscription'] = 1;

// TODO: stocker toutes les infos nécessaires.
    
    $response = \Httpful\Request::post($dolibarrAPIUrl. "members")
            ->addHeader('DOLAPIKEY', $dolibarrToken)
            ->sendsJson()
            ->body(json_encode($member))
            ->send();
    print_r($response);
            
    
// TODO: Faire le traitement Dolibarr
    

    /* Enregistrement de l'ID du paiement en BDD. */
    try {
        $bdd->exec('INSERT INTO payments_notifications(id) VALUES(' . intval($_POST['id'], 10) . ')');
    } catch (\Exception $e) {
        updatelog("Erreur lors de l'enregistrement de la notification en BDD." . PHP_EOL . "Exception :" . $e->getMessage());
    }
} else {
    updatelog('Notification déjà traitée.');
}

updatelog("********************************************************");
updatelog(PHP_EOL);
?>
