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
            //TO DO : import FTP server files
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



llxFooter();