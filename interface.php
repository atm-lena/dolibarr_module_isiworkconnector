<?php

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
dol_include_once('isiworkconnector/class/isiworkconnector.class.php');
dol_include_once('isiworkconnector/lib/isiworkconnector.lib.php');

$object = new isiworkconnector($db);

$action = GETPOST('action');
$id = GETPOST('id');
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
        case 'import':

            //ON DETERMINE SI LES FACTURES FOURNISSEURS DOIVENT ETRE AUTOMATIQUEMENT VALIDEES
            $auto_validate_supplier_invoice = GETPOST('auto_validate_supplier_invoice');

            //ON IMPORTE LES FICHIERS
            $TFilesImported = $object->runImportFiles($auto_validate_supplier_invoice);

            //ON ENREGISTRE DANS LA SESSION LES DOCUMENTS OK ET KO
            $_SESSION['OK'] = $TFilesImported['OK'];
            $_SESSION['KO'] = $TFilesImported['KO'];

            //ON REVIENT SUR L'INTERFACE PRINCIPALE
            header('Location: ' . dol_buildpath('/isiworkconnector/interface.php', 1));

            exit;

        default :
            $ftpc=$object->FTPConnection();
            $nb_waitingfiles = $object->get_nb_XMLFilesFTP($ftpc);
    }
}


/*
 * View
 */

llxHeader('', $title);
print load_fiche_titre($langs->trans('ISIWork'), '', 'isiworkconnector@isiworkconnector');

//NOMBRE DE DOCUMENTS DISPONIBLES A IMPORTER
print "<div>". $nb_waitingfiles . " " . $langs->trans('FilesWaiting'). "</div><br>";
print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=import">'.$langs->trans("Import").'</a></div>'."\n";

//AFFICHAGE DU COMPTE RENDU DU DERNIER IMPORT EFFECTUE
if(!empty($_SESSION['OK']) || !empty($_SESSION['KO'])) {
    print "<br>";
    print '<div><b>COMPTE RENDU DU DERNIER IMPORT : </b></div>';
    print "<br>";

    if ($_SESSION['OK']) {                                                                                  //OK

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

    if ($_SESSION['KO']) {                                                                                  //KO
        print '<br>';
        print '<div>Echec d\'import :</div>';

        foreach ($_SESSION['KO'] as $doc) {
            print '<div>' . $doc . ' > <b>KO</b></div>';
        }
    }
}

llxFooter();