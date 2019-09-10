<?php

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
dol_include_once('isiworkconnector/class/isiworkconnector.class.php');
dol_include_once('isiworkconnector/lib/isiworkconnector.lib.php');

$action = GETPOST('action');

$object = new isiworkconnector($db);

$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'isiworkconnectorinterface';

$hookmanager->initHooks(array('isiworkconnectorcardinterface', 'globalcard'));

/*
 * Actions
 */

$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    $error = 0;
    switch ($action) {
        case 'process':

            //ON IMPORTE LES FICHIERS
            $TDocsImported = $object->runImportFiles();

            //ON ENREGISTRE DANS LA SESSION LES DOCUMENTS OK ET KO
            $_SESSION['OK'] = $TDocsImported['OK'];
            $_SESSION['KO'] = $TDocsImported['KO'];

            //ON REVIENT SUR L'INTERFACE PRINCIPALE
            header('Location: ' . dol_buildpath('/isiworkconnector/interface.php', 1));

            exit;

        default :
            $nb_waitingfiles = $object->get_nb_XMLFilesFTP();
    }
}


/*
 * View
 */

llxHeader('', $title);
print load_fiche_titre($langs->trans('ISIWork'), '', 'isiworkconnector@isiworkconnector');


print "<div>". $nb_waitingfiles . " " . $langs->trans('FilesWaiting'). "</div><br>";
print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=process">'.$langs->trans("IWImport").'</a></div>'."\n";

//AFFICHAGE DU COMPTE RENDU DU DERNIER IMPORT EFFECTU
if(!empty($_SESSION['OK']) || !empty($_SESSION['KO'])) {
    print "<br>";
    print '<div><b>COMPTE RENDU DU DERNIER IMPORT : </b></div>';
    print "<br>";

    //FICHIERS TRAITES
    if ($_SESSION['OK']) {

        foreach ($_SESSION['OK'] as $docType => $Tdocs) {

            //FACTURES FOURNISSEUR
            if ($docType == 'FactureFourn') {
                print '<div>Factures créées : </div>';
                foreach ($Tdocs as $id => $ref) {
                    print '<div><a href = "' . DOL_URL_ROOT . '/fourn/facture/card.php?facid=' . $id . '">' . $ref . '</a></div>';
                }
            }
        }
    }


    //FICHIERS KO
    if ($_SESSION['KO']) {
        print '<br>';
        print '<div>Echec d\'import :</div>';

        foreach ($_SESSION['KO'] as $doc) {
            print '<div>' . $doc . ' > <b>KO</b></div>';
        }
    }
}

llxFooter();