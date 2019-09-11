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

//RESULTATS DE L'IMPORT
if(!empty($_SESSION['OK']) || !empty($_SESSION['KO'])) {

    //DOCUMENTS OK
    if ($_SESSION['OK']) {

        print '<div>Documents créés ('.count($_SESSION['OK']).')</div>';

        //ENTETE TABLEAU
        print '<table class="noborder" width="100%">';
        print '<tbody>';
        print '<tr class="liste_titre">';
        print '<th>Fichier</th>';
        print '<th>Type document</th>';
        print '<th>Document créé</th>';
        print '</tr>';
        print '<tr>';


        //LIGNES DU TABLEAU
        foreach ($_SESSION['OK'] as $file=>$doc) {

            //FICHIER
            print '<td>'.$file.'</td>';

            //TYPE DU DOCUMENT
            print '<td>'.$doc['type'].'</td>';

            //LIEN VERS L'OBJET CREE
            if($doc['type'] == "Facture fournisseur"){                                                          //facture fournisseur

                //ON RECUPERE LA REFERENCE DE LA FACTURE CREE
                $sql = "SELECT ref FROM " .MAIN_DB_PREFIX. "facture_fourn WHERE rowid=" . $doc['id'];
                $resql = $db->query($sql);
                if($resql){
                    $object = $db->fetch_object($resql);
                    $ref = $object->ref;
                }

                //ON AFFICHE
                print '<td><a href="'.DOL_URL_ROOT.'/fourn/facture/card.php?facid='.$doc['id'].'">'.$ref.'</a></td>';
            }

                $sql = "SELECT ref FROM " .MAIN_DB_PREFIX. "facture_fourn WHERE rowid=" . $doc['id'];
                $resql = $db->query($sql);

                if($resql){
                    $object = $db->fetch_object($resql);
                    $ref_supplierInvoice = $object->ref;
                }
            print '</tr>';
        }
        print '</tbody>';
        print '</table>';
    }

    print '<br>';

    //DOCUMENTS KO
    if ($_SESSION['KO']) {

        print '<div>Echec d\'import ('.count($_SESSION['KO']).')</div>';//KO

        //ENTETE TABLEAU
        print '<table class="noborder" width="100%">';
        print '<tbody>';
        print '<tr class="liste_titre">';
        print '<th>Fichier</th>';
        print '<th>Message d\'erreur</th>';
        print '</tr>';
        print '<tr>';

        //LIGNES DU TABLEAU
        foreach ($_SESSION['KO'] as $file=>$value) {

            //FICHIER
            print '<td>'.$file.'</td>';

            //MESSAGE D'ERREUR
            print '<td>'.$value['error'].'</td>';
            print '</tr>';

        }

        print '</tbody>';
        print '</table>';
    }
}

llxFooter();