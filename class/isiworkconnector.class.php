<?php
/* Copyright (C) 2019 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!class_exists('SeedObject'))
{
	/**
	 * Needed if $form->showLinkedObjectBlock() is call or for session timeout on our module page
	 */
	define('INC_FROM_DOLIBARR', true);
	require_once dirname(__FILE__).'/../config.php';
}


class isiworkconnector extends SeedObject
{
//    /**
//     * Canceled status
//     */
//    const STATUS_CANCELED = -1;
//    /**
//     * Draft status
//     */
//    const STATUS_DRAFT = 0;
//	/**
//	 * Validated status
//	 */
//	const STATUS_VALIDATED = 1;
//	/**
//	 * Refused status
//	 */
//	const STATUS_REFUSED = 3;
//	/**
//	 * Accepted status
//	 */
//	const STATUS_ACCEPTED = 4;
//
//	/** @var array $TStatus Array of translate key for each const */
//	public static $TStatus = array(
//		self::STATUS_CANCELED => 'isiworkconnectorStatusShortCanceled'
//		,self::STATUS_DRAFT => 'isiworkconnectorStatusShortDraft'
//		,self::STATUS_VALIDATED => 'isiworkconnectorStatusShortValidated'
////		,self::STATUS_REFUSED => 'isiworkconnectorStatusShortRefused'
////		,self::STATUS_ACCEPTED => 'isiworkconnectorStatusShortAccepted'
//	);

	public $table_element = 'isiworkconnector';

	public $element = 'isiworkconnector';

//	/** @var int $isextrafieldmanaged Enable the fictionalises of extrafields */
//    public $isextrafieldmanaged = 1;

//    /** @var int $ismultientitymanaged 0=No test on entity, 1=Test with field entity, 2=Test with link by societe */
//    public $ismultientitymanaged = 1;

//    public $fields = array(
//
//    );

//    /**
//     * isiworkconnector constructor.
//     * @param DoliDB    $db    Database connector
//     */
//    public function __construct($db)
//    {
//		global $conf;
//
//        parent::__construct($db);
//
//		$this->init();
//    }

    public function runImportFiles(){
        global $conf;

        $error = 0;

        $res = isiworkconnector::filesProcessing();

    }

    public function filesProcessing(){
        global $conf;

        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
        require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

        $error = 0;

        //INFORMATIONS FTP
        $ftp_host = (empty($conf->global->IWCONNECTOR_FTP_HOST)) ? "" : $conf->global->IWCONNECTOR_FTP_HOST;
        $ftp_port = (empty($conf->global->IWCONNECTOR_FTP_PORT)) ? 21 : $conf->global->IWCONNECTOR_FTP_PORT;
        $ftp_user = (empty($conf->global->IWCONNECTOR_FTP_USER)) ? "" : $conf->global->IWCONNECTOR_FTP_USER;
        $ftp_pass = (empty($conf->global->IWCONNECTOR_FTP_PASS)) ? "" : $conf->global->IWCONNECTOR_FTP_PASS;
        $ftp_folder = (empty($conf->global->IWCONNECTOR_FTP_FOLDER)) ? "" : $conf->global->IWCONNECTOR_FTP_FOLDER;
        $timeout = 120;

        if(empty($ftp_host)) {
            $error++;
            $this->errors[] = "Information de connection FTP invalide : hôte non-défini";
        }

        //CONNEXION FTP
        if(!$error) $ftpc = ftp_connect($ftp_host, $ftp_port);

        if(!$ftpc){
            $error++;
            $this->errors[] = "Erreur de connection ftp";
        }

        //AUTHENTIFICATION FTP
        if(!$error) $res_log = ftp_login($ftpc, $ftp_user, $ftp_pass);

        if(!$res_log){
            $error++;
            $this->errors[] = "Erreur de login ftp";
        }

        //MODE PASSIF FTP
        if (!empty($conf->global->IWCONNECTOR_FTP_PASSIVE_MODE))
        {
            ftp_pasv($ftpc, true);
        }

        //DOSSIER COURANT FTP
        if(!$error) $res_dir = ftp_chdir($ftpc, $ftp_folder);

        if(!$res_dir)
        {
            $error++;
            $this->errors[] = "Erreur de répertoire ftp".$ftp_folder;
        }

        if(!$error){                                                                            //CONNEXION FTP OK

            //LISTE DES FICHIERS ET DOSSIERS FTP
            $TFile = isiworkconnector::get_FilesFTP();

            $TFileXML = (empty($TFile['xml'])) ? array() : $TFile['xml'];  //liste des fichiers xml serveur ftp
            $TFilePDF = (empty($TFile['pdf'])) ? array() : $TFile['pdf'];  //liste des fichiers pdf serveur ftp
            $TDir = (empty($TFile['dir'])) ? array() : $TFile['dir'];  //liste des dossiers serveur ftp

            //CREATION DOSSIER "TRAITE" SI IL N'EXISTE PAS
            $TDirName = array();
            if(!empty($TDir)){
                foreach($TDir as $dir){
                    $TDirName[] = $dir['name'];
                }
            }

            if((!in_array("Traité", $TDirName))){
                ftp_mkdir($ftpc, "Traité");
            }

            //TRAITEMENT DES FICHIERS XML
            foreach ($TFileXML as $fileXML){

                //OUVERTURE DU FICHIER XML SUR LE SERVEUR FTP
                $ftp_file_path = $ftp_folder.$fileXML['name'];

                $objXml = simplexml_load_file('ftp://'.$ftp_user.':'.$ftp_pass.'@'.$ftp_host.':'.$ftp_port.$ftp_file_path);

                //ON VERIFIE L'EXISTANCE DU FICHIER PDF ASSOCIE AU XML
                $filePDF = isiworkconnector::verifyPDFLinkedToXML($objXml, $TFilePDF);

                //TRAITEMENT FACTURE FOURNISSEUR
                if($objXml->type == 'Facture fournisseur'){
                    if($filePDF) {
                        $res = isiworkconnector::createDolibarrInvoiceSupplier($objXml, $fileXML, $filePDF, $ftpc);       //si le fichier pdf existe, on crée la facture

                        if(empty($res)){
                            $error ++;
                            $this->errors[] = "Impossible de créer la facture depuis le fichier : " . $fileXML['name'];
                        }

                    } else {
                        $error ++;
                        $this->errors[] = "Impossible de créer la facture " . $objXml->ref. " : PDF du fichier '" . $fileXML['name'] .  "' introuvable";
                    }
                } else {
                    $error ++;
                    $this->errors[] = "Le type '" . $objXml->type .  "' du document " . $fileXML['name'] . " est invalide";
                }

                //DEPLACEMENT FICHIERS XML ET PDF DANS LE DOSSIER "TRAITE"
                if(!$error){
                    isiworkconnector::cleanFTPDirectory($ftp_folder, $ftp_port, $ftp_host, $ftp_pass, $fileXML, $ftp_user,  $filePDF);
                }
            }
        }

        if($error) {                                                                            //CONNEXION FTP OUT
            setEventMessages('', $this->errors, "errors");
            return 0;
        } else {
            return 1;
        }
    }

    public function cleanFTPDirectory($ftp_folder, $ftp_port, $ftp_host, $ftp_pass, $fileXML, $ftp_user,  $filePDF){

        $ftp_file_path_dest = $ftp_folder.'/Traité/'.$fileXML['name'];
        $ftp_file_path_xml = $ftp_folder.$fileXML['name'];
        copy('ftp://'.$ftp_user.':'.$ftp_pass.'@'.$ftp_host.':'.$ftp_port.$ftp_file_path_xml, 'ftp://'.$ftp_user.':'.$ftp_pass.'@'.$ftp_host.':'.$ftp_port.$ftp_file_path_dest);
        unlink('ftp://'.$ftp_user.':'.$ftp_pass.'@'.$ftp_host.':'.$ftp_port.$ftp_file_path_xml);

        $ftp_file_path_dest_pdf = $ftp_folder.'/Traité/'.$filePDF['name'];
        $ftp_file_path_pdf = $ftp_folder.$filePDF['name'];
        copy('ftp://'.$ftp_user.':'.$ftp_pass.'@'.$ftp_host.':'.$ftp_port.$ftp_file_path_pdf, 'ftp://'.$ftp_user.':'.$ftp_pass.'@'.$ftp_host.':'.$ftp_port.$ftp_file_path_dest_pdf);
        unlink('ftp://'.$ftp_user.':'.$ftp_pass.'@'.$ftp_host.':'.$ftp_port.$ftp_file_path_pdf);
    }

    /**
     *    Création d'une facture fournisseur à partir d'un fichier XML FTP donné
     *
     *    @param      object                objet XML
     *                string                fichier XML
     *                string                fichier PDF
     *                FTP Buffer            connexion ftp vers les fichiers concernés
     *    @return     int    	     		1 si OK, < 0 si KO, 0 si création OK mais erreurs
     */

    public function createDolibarrInvoiceSupplier($objXml, $fileXML, $filePDF, $ftpc){

        global $db, $user, $conf;

        require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
        require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';

        $error = 0;

        //on crée un nouvel objet facture
        $supplierInvoice = new FactureFournisseur($db);

        //ON RENSEIGNE LES INFORMATIONS OBLIGATOIRES POUR UNE FACTURE
        if(!empty($objXml->ref) && !empty($objXml->fournisseur) && !empty($objXml->date) && !empty($objXml->ref_supplier)){

            //on vérifie si la ref facture n'existe pas déjà
            $res = $supplierInvoice->fetch('', $objXml->ref);
            if(!empty($res)){
                $error++;
                $this->errors[] = 'Impossible de créer la facture : la référence "' . $objXml->ref . '" existe déjà';
            }

            //on vérifie si le fournisseur existe
            $sql = 'SELECT * FROM ' .MAIN_DB_PREFIX. 'societe WHERE code_fournisseur IS NOT NULL AND nom = "'. $objXml->fournisseur . '";';
            $resql = $db->query($sql);
            if(!($db->num_rows($resql)) || $db->num_rows($resql) > 1){
                $error++;
                $this->errors[] = 'Impossible de créer la facture : le fournisseur "' .$objXml->fournisseur. '" n\'existe pas';
            } else {
                $supplier = $db->fetch_object($resql);
                $supplierInvoice->socid = $supplier->rowid;
            }

            $supplierInvoice->date = strftime('%Y-%m-%d',strtotime($objXml->date));

            $supplierInvoice->ref_supplier = $objXml->ref_supplier->__toString();

        } else {
            $error++;
            $this->errors[] = 'Impossible de créer la facture ' . pathinfo($objXml->DocPath, PATHINFO_FILENAME) . ' : fichier xml incomplet';
        }

        //VERIFICATION DES PRODUITS/SERVICES
        if ($objXml->lines) {
            $TSupplierProduct = array();
            foreach ($objXml->lines as $item) {
                foreach ($item as $line) {
                    //id produit
                    $refProduct = $line->ref->__toString();
                    $sql = 'SELECT * FROM llx_product WHERE ref = "' . $refProduct . '"';
                    $resql = $db->query($sql);

                    if($db->num_rows($resql) == 1) {
                        $product = $db->fetch_object($resql);
                        $TSupplierProduct[$refProduct]['id'] = $product->rowid;
                        $TSupplierProduct[$refProduct]['type'] = $product->fk_product_type;
                    } else {
                        if ($db->num_rows($resql) == 0) {
                            $error++;
                            $this->errors[] = 'Produit/service "' . $refProduct . '" inexistant';
                            continue;
                        } elseif ($db->num_rows($resql) > 1) {
                            $error++;
                            $this->errors[] = 'Impossible de déterminer le produit correspondant : ' . $refProduct;
                            continue;
                        }
                    }
                }
            }
        }


        //DONNEES OBLIGATOIRES OK ET PRODUITS/SERVICES OK : CREATION FACTURE
        if(!$error){

            //RAJOUT DONNEES NON OBLIGATOIRE
            if(!empty($objXml->date_echeance)){
                $supplierInvoice->date_echeance = strftime('%Y-%m-%d',strtotime($objXml->date_echeance));
            }

            //CREATION DE LA FACTURE ET SES PIECES JOINTES
            $supplierInvoiceID = $supplierInvoice->create($user);

                //ON RECUPERE LA FACTURE CREE
                $supplierInvoice->fetch($supplierInvoiceID);

                //REFERENCE EXTERNE
                $supplierInvoice->update_ref_ext($fileXML['name']);

                //ON JOINT LE FICHIER PDF ET XML A LA FACTURE
                $ref = dol_sanitizeFileName($supplierInvoice->ref);
                $local_dir = $conf->fournisseur->facture->dir_output . '/' . get_exdir($supplierInvoice->id, 2, 0, 0, $supplierInvoice, 'invoice_supplier') . $ref;
                if (!dol_is_dir($local_dir)) {
                    dol_mkdir($local_dir);
                }

                $remote_file_pdf = $filePDF['name'];
                $remote_file_xml = $fileXML['name'];

                $local_file_pdf = $local_dir . '/' . $remote_file_pdf;
                $local_file_xml = $local_dir . '/' . $remote_file_xml;

                $res = ftp_get($ftpc, $local_file_pdf, $remote_file_pdf, FTP_ASCII);
                if(!$res){
                    $error++;
                    $this->errors[] = 'Fichier pdf non joint';
                }

                $res = ftp_get($ftpc, $local_file_xml, $remote_file_xml, FTP_ASCII);
                if(!$res){
                    $error++;
                    $this->errors[] = 'Fichier xml non joint';
                }

            if($supplierInvoiceID<=0){
                $error++;
                $this->errors[] = 'Echec création de la facture';
            }
        }

        if(!$error) {

            if ($TSupplierProduct){
                foreach ($TSupplierProduct as $product) {


                        //id produit
                        $idProduct = $product['id'];

                        //type produit
                        $typeProduct = $product['type'];

                        //quantité
                        $qty = $line->qty->__toString();

                        //réduction
                        $remise_percent = $line->remise_percent->__toString();

                        //description
                        $description = $line->description->__toString();

                        //prix unitaire ht
                        $pu_ht = $line->pu_ht->__toString();

                        //taux de tva
                        $tva_tx = $line->tva_tx->__toString();

                        if (!$error) {
                            //ajout ligne
                            $supplierInvoice->addline(
                                '',
                                $pu_ht,
                                $tva_tx,
                                '',
                                '',
                                $qty,
                                $idProduct,
                                $remise_percent,
                                '',
                                '',
                                '',
                                '',
                                'HT',
                                $typeProduct);

                        }
                }
            }
        }

        //VALIDATION FACTURE
        if(!$error){
            $supplierInvoice->validate($user);
        }

        if($error) {                                                                            //CONNEXION FTP OUT
            return 0;
        } else {
            return 1;
        }

    }

    public function verifyPDFLinkedToXML($objXml, $TFilePDF){

        if(!empty($objXml->DocPath)) {
            $PDFlinkedtoXML = str_replace("\\", "/", $objXml->DocPath->__toString());
            $PDFlinkedtoXML =  basename($PDFlinkedtoXML);

            foreach ($TFilePDF as $filePDF) {
                if ($PDFlinkedtoXML == $filePDF['name']){
                    return $filePDF;
                }
            }
        }

        return 0;
    }

    /**
     * Nombre de fichiers XML en attente dans le serveur ftp
     */

    public function get_nb_XMLFilesFTP(){

        $TFilesXML = array();

        $res = $this->get_FilesFTP();
        $TFilesXML = $res['xml'];

        if(is_array($TFilesXML)) {
            return count($TFilesXML);
        } else {
            return 0;
        }
    }

    /**
     *  Retourne la liste des fichiers XML du serveur ftp par type
     */

    public function get_FilesFTP (){
        global $conf;

        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
        require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

        $error = 0;

        //INFORMATIONS FTP
        $ftp_host = (empty($conf->global->IWCONNECTOR_FTP_HOST)) ? "" : $conf->global->IWCONNECTOR_FTP_HOST;
        $ftp_port = (empty($conf->global->IWCONNECTOR_FTP_PORT)) ? 21 : $conf->global->IWCONNECTOR_FTP_PORT;
        $ftp_user = (empty($conf->global->IWCONNECTOR_FTP_USER)) ? "" : $conf->global->IWCONNECTOR_FTP_USER;
        $ftp_pass = (empty($conf->global->IWCONNECTOR_FTP_PASS)) ? "" : $conf->global->IWCONNECTOR_FTP_PASS;
        $ftp_folder = (empty($conf->global->IWCONNECTOR_FTP_FOLDER)) ? "" : $conf->global->IWCONNECTOR_FTP_FOLDER;
        $timeout = 120;

        if(empty($ftp_host)) {
            $error++;
            $this->errors[] = "Information de connection FTP invalide : hôte non-défini";
        }

        //CONNEXION FTP
        if(!$error) $ftpc = ftp_connect($ftp_host, $ftp_port);

        if(!$ftpc){
            $error++;
            $this->errors[] = "Erreur de connection ftp";
        }

        //AUTHENTIFICATION FTP
        if(!$error) $res_log = ftp_login($ftpc, $ftp_user, $ftp_pass);

        if(!$res_log){
            $error++;
            $this->errors[] = "Erreur de login ftp";
        }

        //MODE PASSIF FTP
        if (!empty($conf->global->IWCONNECTOR_FTP_PASSIVE_MODE))
        {
            ftp_pasv($ftpc, true);
        }

        //DOSSIER COURANT FTP
        if(!$error) $res_dir = ftp_chdir($ftpc, $ftp_folder);

        if(!$res_dir)
        {
            $error++;
            $this->errors[] = "Erreur de répertoire ftp".$ftp_folder;
        }

        if(!$error){                                                                                //CONNEXION FTP OK

            //LISTE FICHIERS SUR LE SERVEUR FTP
            $TFileFTP = ftp_mlsd($ftpc,ftp_pwd($ftpc));

            //LISTE DES FICHIERS FTP PAR TYPE
            $TFile = array();
            foreach ($TFileFTP as $file){
                if($file['name'] != "." && $file['name'] != ".." && $file['type'] == "file" ) {
                    $filetype = pathinfo($file['name'], PATHINFO_EXTENSION);                //on récupère l'extension des fichiers
                    if($filetype == "xml") {
                        $TFile['xml'][] = $file;
                    } elseif($filetype == "pdf") {
                        $TFile['pdf'][] = $file;
                    }
                } elseif($file['type'] == "dir"){
                    $TFile['dir'][] = $file;
                }
            }
            return $TFile;
        } else {                                                                                    //CONNEXION FTP OUT
            setEventMessages('', $this->errors[0], "errors");
            return 0;
        }
    }

    /**
     * @param int    $withpicto     Add picto into link
     * @param string $moreparams    Add more parameters in the URL
     * @return string
     */
    public function getNomUrl($withpicto = 0, $moreparams = '')
    {
		global $langs;

        $result='';
        $label = '<u>' . $langs->trans("Showisiworkconnector") . '</u>';
        if (! empty($this->ref)) $label.= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;

        $linkclose = '" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
        $link = '<a href="'.dol_buildpath('/isiworkconnector/card.php', 1).'?id='.$this->id.urlencode($moreparams).$linkclose;

        $linkend='</a>';

        $picto='generic';
//        $picto='isiworkconnector@isiworkconnector';

        if ($withpicto) $result.=($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
        if ($withpicto && $withpicto != 2) $result.=' ';

        $result.=$link.$this->ref.$linkend;

        return $result;
    }

    /**
     * @param int       $id             Identifiant
     * @param null      $ref            Ref
     * @param int       $withpicto      Add picto into link
     * @param string    $moreparams     Add more parameters in the URL
     * @return string
     */
    public static function getStaticNomUrl($id, $ref = null, $withpicto = 0, $moreparams = '')
    {
		global $db;

		$object = new isiworkconnector($db);
		$object->fetch($id, false, $ref);

		return $object->getNomUrl($withpicto, $moreparams);
    }


//    /**
//     * @param int $mode     0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
//     * @return string
//     */
//    public function getLibStatut($mode = 0)
//    {
//        return self::LibStatut($this->status, $mode);
//    }
//
//    /**
//     * @param int       $status   Status
//     * @param int       $mode     0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
//     * @return string
//     */
//    public static function LibStatut($status, $mode)
//    {
//		global $langs;
//
//		$langs->load('isiworkconnector@isiworkconnector');
//        $res = '';
//
//        if ($status==self::STATUS_CANCELED) { $statusType='status9'; $statusLabel=$langs->trans('isiworkconnectorStatusCancel'); $statusLabelShort=$langs->trans('isiworkconnectorStatusShortCancel'); }
//        elseif ($status==self::STATUS_DRAFT) { $statusType='status0'; $statusLabel=$langs->trans('isiworkconnectorStatusDraft'); $statusLabelShort=$langs->trans('isiworkconnectorStatusShortDraft'); }
//        elseif ($status==self::STATUS_VALIDATED) { $statusType='status1'; $statusLabel=$langs->trans('isiworkconnectorStatusValidated'); $statusLabelShort=$langs->trans('isiworkconnectorStatusShortValidate'); }
//        elseif ($status==self::STATUS_REFUSED) { $statusType='status5'; $statusLabel=$langs->trans('isiworkconnectorStatusRefused'); $statusLabelShort=$langs->trans('isiworkconnectorStatusShortRefused'); }
//        elseif ($status==self::STATUS_ACCEPTED) { $statusType='status6'; $statusLabel=$langs->trans('isiworkconnectorStatusAccepted'); $statusLabelShort=$langs->trans('isiworkconnectorStatusShortAccepted'); }
//
//        if (function_exists('dolGetStatus'))
//        {
//            $res = dolGetStatus($statusLabel, $statusLabelShort, '', $statusType, $mode);
//        }
//        else
//        {
//            if ($mode == 0) $res = $statusLabel;
//            elseif ($mode == 1) $res = $statusLabelShort;
//            elseif ($mode == 2) $res = img_picto($statusLabel, $statusType).$statusLabelShort;
//            elseif ($mode == 3) $res = img_picto($statusLabel, $statusType);
//            elseif ($mode == 4) $res = img_picto($statusLabel, $statusType).$statusLabel;
//            elseif ($mode == 5) $res = $statusLabelShort.img_picto($statusLabel, $statusType);
//            elseif ($mode == 6) $res = $statusLabel.img_picto($statusLabel, $statusType);
//        }
//
//        return $res;
//    }

}