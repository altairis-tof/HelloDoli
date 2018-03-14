<?php

// Inclusion du client REST
include('./httpful.phar');
include('./config.php');
include('./db.php');
include('./functions.php');
include('./constants.php');

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
    updatelog("Erreur lors de la lecture en BDD ." . PHP_EOL . "Exception :" . $e->getMessage());
}

$notificationExists = intval($response->fetch()[0], 10);
$response->closeCursor();

if (!$notificationExists == 0) 
{
    updatelog('Notification déjà traitée.');
} else {
    
    /* Recherche des détails du paiement via reqûete API HelloAsso */
    $actionID = str_pad($notification['id'], 11, '0', STR_PAD_LEFT);
    $actionID .= '3'; //un '3' n'est pas présent dans la notification HelloAsso mais
    $response = \Httpful\Request::get($helloAssoAPIUrl . "actions/" . $actionID . ".json")
            ->authenticateWith($helloAssoUsername, $helloAssoAPIPassword)
            ->expectsJson()
            ->send();
    updatelog(print_r($response->body, TRUE));
    
    $paymentID = $response->body->id_payment;
    
    $member['lastname'] = mb_strtoupper($response->body->last_name);
    $member['firstname'] = ucwords($response->body->first_name,'-');
    $member['email'] = $response->body->email;
    $member['morphy'] = "phy";
    
    foreach ($response->body->custom_infos as $value) {
        switch ($value->label){
            case "Numéro de téléphone":
                $member['phone_perso'] = $value->value;
                break;
            case "Adresse contributeur":
                $member['address'] = $value->value;
                break;
            case "Date de naissance":
                $member['birth'] = strtotime(str_replace('/', '-', $value->value));
                break;
            case "Je souhaite devenir un breizhipote actif ? (m'inscrire dans une commission)":
                $member['typeid'] = $value->value == 'Oui'?2:3;
                break;
            case "Je souhaite devenir coopérateur le jour où le supermarché sera ouvert":
                $options['options_cooperateur'] = $value->value == 'Oui'?1:0; // futur coopérateur
                break;
            case "Code Postal contributeur":
                $member['zip'] = $value->value;
                break;
            case "Genre":
                switch($value->value)
                {
                    case 'Homme':
                        $member['civility_id'] = 'MR';
                           break;
                    case 'Femme':
                        $member['civility_id'] = 'MME';
                        break;
                }
                break;
            case "Ville":
                $member['town'] = $value->value;
                break;
            case "Groupe de travail souhaité":
                $options['options_commission'] = $commissions[$value->value];
                break;      
        }   
    }
    $member['public'] = 0;
    $member['statut'] = -1; // brouillon
    $member['photo'] = null;
    $member['datec'] = time(); //date de création
    
    $member['need_subscription'] = 1;
    $member['array_options'] = $options;
    
    // Récupération des détails du paiement
    $response = \Httpful\Request::get($helloAssoAPIUrl . "payments/" . $paymentID . ".json")
            ->authenticateWith($helloAssoUsername, $helloAssoAPIPassword)
            ->expectsJson()
            ->send();
    updatelog(print_r($response->body, TRUE));
    
    // Envoi des données à l'API Dolibarr
    $response = \Httpful\Request::post($dolibarrAPIUrl. "members")
            ->addHeader('DOLAPIKEY', $dolibarrToken)
            ->sendsJson()
            ->body(json_encode($member))
            ->send();
    updatelog(print_r($response->body), TRUE);    

    /* Enregistrement de l'ID du paiement en BDD. */
    try {
        $bdd->exec('INSERT INTO payments_notifications(id) VALUES(' . intval($_POST['id'], 10) . ')');
    } catch (\Exception $e) {
        updatelog("Erreur lors de l'enregistrement de la notification en BDD." . PHP_EOL . "Exception :" . $e->getMessage());
    }
}

updatelog("********************************************************");
updatelog(PHP_EOL);
?>
