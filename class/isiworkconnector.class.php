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

    public function __construct(DoliDB &$db)
    {
        parent::__construct($db);
    }

    /**
     * Importe les fichiers ISIWork du serveur FTP
     *
     * @return array   id/nom objet OK et nom fichiers "KO"
     */

    public function runImportFiles ($auto_validate_supplier_invoice = ''){
        global $conf;

        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
        require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
        require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';

        $error = 0;
        $TFilesImported= array();

        //CONNEXION FTP
        $ftpc = isiworkconnector::FTPConnection();

        if(!empty($ftpc)){                                                                            //CONNEXION FTP OK

            //LISTE DES FICHIERS XML / PDF ET DOSSIERS SUR LE FTP
            $TFiles = isiworkconnector::get_FilesFTP($ftpc);

            $TFilesXML = (empty($TFiles['xml'])) ? array() : $TFiles['xml'];  //liste des fichiers xml serveur ftp
            $TFilesPDF = (empty($TFiles['pdf'])) ? array() : $TFiles['pdf'];  //liste des fichiers pdf serveur ftp
            $TDirs = (empty($TFiles['dir'])) ? array() : $TFiles['dir'];  //liste des dossiers serveur ftp

            //CREATION DOSSIER "TRAITE" SI IL N'EXISTE PAS
            $TDirsName = array();
            if(!empty($TDirs)){
                foreach($TDirs as $dir){
                    $TDirsName[] = $dir;
                }
            }
            if((!in_array("Traité", $TDirsName))){
                ftp_mkdir($ftpc, "Traité");
            }

            //TRAITEMENT DES FICHIERS XML
            foreach ($TFilesXML as $fileXML){

                //OUVERTURE DU FICHIER XML SUR LE SERVEUR FTP
                $ftp_folder = (empty($conf->global->IWCONNECTOR_FTP_FOLDER)) ? "" : $conf->global->IWCONNECTOR_FTP_FOLDER;
                $ftp_file_path = $ftp_folder.$fileXML;

                $objXml = simplexml_load_file('ftp://'.$conf->global->IWCONNECTOR_FTP_USER.':'.$conf->global->IWCONNECTOR_FTP_PASS.'@'.$conf->global->IWCONNECTOR_FTP_HOST.':'.$conf->global->IWCONNECTOR_FTP_PORT.$ftp_file_path);

                if($objXml) {

                    //TRAITEMENT FACTURE FOURNISSEUR
                    if ($objXml->type == 'Facture fournisseur') {

                        //ON VERIFIE L'EXISTENCE DU FICHIER PDF ASSOCIE AU XML
                        $PDFlinkedtoXML = str_replace("\\", "/", $objXml->DocPath->__toString());
                        $PDFlinkedtoXML =  basename($PDFlinkedtoXML);

                        $filePDF = isiworkconnector::verifyPDFLinkedToXML($PDFlinkedtoXML , $TFilesPDF);

                        if ($filePDF) {
                            //ON CREE LA FACTURE
                            $res = isiworkconnector::createDolibarrInvoiceSupplier($ftpc, $objXml, $fileXML, $filePDF, $auto_validate_supplier_invoice);       //si le fichier pdf existe, on crée la facture

                        } else {
                            $error++;
                            $this->errors[] = "Impossible de créer la facture " . $objXml->ref . " : PDF du fichier '" . $fileXML . "' introuvable";
                        }

                        //FACTURE CREE
                        if (!empty($res)) {

                            //DEPLACEMENT FICHIERS XML ET PDF DANS LE DOSSIER "TRAITE"
                            $folder_dest = "Traité";                                                //Dossier de destination
                            $TFiles = array();                                                      //Liste des fichiers à transférer

                            $TFiles[] = $filePDF;
                            $TFiles[] = $fileXML;

                            isiworkconnector::cleanFTPDirectory($TFiles, $folder_dest);

                            //ON AJOUTE LA FACTURE (ID ET REF) A OK DANS LE TABLEAU RETOURNE PAR LA FONCTION
                            $id_supplierInvoice = $res;
                            $supplierInvoice = new FactureFournisseur($this->db);
                            $supplierInvoice->fetch($id_supplierInvoice);
                            $TFilesImported['OK']['FactureFourn'][$id_supplierInvoice] = $supplierInvoice->ref ;

                        }

                        //FACTURE NON CREE
                        else {
                            //ON AJOUTE LE NOM DU FICHIER XML AUX FICHIERS KO
                            $TFilesImported['KO'][] = $fileXML;
                        }
                    }

                    //TYPE DE DOCUMENT INCONNU
                    else {
                        $error++;
                        $this->errors[] = "Le type '" . $objXml->type . "' du document " . $fileXML . " est invalide";

                        //ON AJOUTE LE  NOM FICHIER XML AUX FICHIERS KO
                        $TFilesImported['KO'][] = $fileXML;
                    }

                } else {
                    $error++;
                    $this->errors[] = "Erreur lors du chargement du fichier " . $fileXML;

                    //ON AJOUTE LE  NOM FICHIER XML AUX FICHIERS KO
                    $TFilesImported['KO'][] = $fileXML;
                }
            }
        }

        if($error) {                                                                            //CONNEXION FTP OUT
            setEventMessages('', $this->errors, "errors");
        }

        return $TFilesImported;
    }


    /**
     * Se connecte au serveur FTP renseigné en conf
     *
     * @return int|resource
     */
    public function FTPConnection (){

        global $conf;

        $error = 0;

        //INFORMATIONS FTP
        $ftp_host = (empty($conf->global->IWCONNECTOR_FTP_HOST)) ? "" : $conf->global->IWCONNECTOR_FTP_HOST;
        $ftp_port = (empty($conf->global->IWCONNECTOR_FTP_PORT)) ? 21 : $conf->global->IWCONNECTOR_FTP_PORT;
        $ftp_user = (empty($conf->global->IWCONNECTOR_FTP_USER)) ? "" : $conf->global->IWCONNECTOR_FTP_USER;
        $ftp_pass = (empty($conf->global->IWCONNECTOR_FTP_PASS)) ? "" : $conf->global->IWCONNECTOR_FTP_PASS;
        $ftp_folder = (empty($conf->global->IWCONNECTOR_FTP_FOLDER)) ? "" : $conf->global->IWCONNECTOR_FTP_FOLDER;

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
        if(!empty($ftp_folder)) {

            if (!$error) $res_dir = ftp_chdir($ftpc, $ftp_folder);

            if (!$res_dir) {
                $error++;
                $this->errors[] = "Erreur de répertoire ftp" . $ftp_folder;
            }
        }

        if($error) {                                                                            //CONNEXION FTP OUT
            setEventMessages('', $this->errors, "errors");
            return 0;
        } else {
            return $ftpc;
        }

    }


    /**
     *
     * Déplace une liste de fichiers FTP dans un dossier destinataire FTP
     *
     * @param $TFiles
     * @param string $folder_dest
     */
    public function cleanFTPDirectory($TFiles, $folder_dest = ''){

        global $conf;

        $ftp_host = (empty($conf->global->IWCONNECTOR_FTP_HOST)) ? "" : $conf->global->IWCONNECTOR_FTP_HOST;
        $ftp_port = (empty($conf->global->IWCONNECTOR_FTP_PORT)) ? 21 : $conf->global->IWCONNECTOR_FTP_PORT;
        $ftp_user = (empty($conf->global->IWCONNECTOR_FTP_USER)) ? "" : $conf->global->IWCONNECTOR_FTP_USER;
        $ftp_pass = (empty($conf->global->IWCONNECTOR_FTP_PASS)) ? "" : $conf->global->IWCONNECTOR_FTP_PASS;
        $ftp_folder = (empty($conf->global->IWCONNECTOR_FTP_FOLDER)) ? "" : $conf->global->IWCONNECTOR_FTP_FOLDER;

        //SI IL Y A UN FICHIER DE DESTINATION ON DEPLACE
        if(!empty($folder_dest)) {
            foreach($TFiles as $file){
                $ftp_file_path_dest = $ftp_folder . '/'. $folder_dest .'/' . $file;
                $ftp_file_path = $ftp_folder . $file;
                copy('ftp://' . $ftp_user . ':' . $ftp_pass . '@' . $ftp_host . ':' . $ftp_port . $ftp_file_path, 'ftp://' . $ftp_user . ':' . $ftp_pass . '@' . $ftp_host . ':' . $ftp_port . $ftp_file_path_dest);
                unlink('ftp://' . $ftp_user . ':' . $ftp_pass . '@' . $ftp_host . ':' . $ftp_port . $ftp_file_path);
            }
        }

        //SI IL N'Y A PAS DE FICHIER DE DESTINATION, ON SUPPRIME SIMPLEMENT LES FICHIERS
        else {
            foreach($TFiles as $file){
                $ftp_file_path = $ftp_folder . $file;
                unlink('ftp://' . $ftp_user . ':' . $ftp_pass . '@' . $ftp_host . ':' . $ftp_port . $ftp_file_path);
            }
        }
    }

    /**
     *
     * Crée une facture fournisseur à partir à partir d'une connexion FTP et d'un objet XML
     *
     * @param $ftpc
     * @param $objXml
     * @param $fileXML
     * @param $filePDF
     * @return int                  1 si facture crée, 0 si erreur
     */

    public function createDolibarrInvoiceSupplier($ftpc, $objXml, $fileXML, $filePDF, $auto_validate = ''){

        global $user, $conf;

        require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
        require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';

        $error = 0;

        //ON CREE UNE NOUVELLE FACTURE FOURNISSEUR
        $supplierInvoice = new FactureFournisseur($this->db);

        //ON RENSEIGNE LES INFORMATIONS OBLIGATOIRES POUR UNE FACTURE
        if(!empty($objXml->ref) && !empty($objXml->fournisseur) && !empty($objXml->date) && !empty($objXml->ref_supplier)){

            //on vérifie si la ref facture n'existe pas déjà
            $res = $supplierInvoice->fetch('', $objXml->ref->__toString());
            if(!empty($res)){
                $error++;
                $this->errors[] = 'Impossible de créer la facture : la référence "' . $objXml->ref . '" existe déjà';
            }

            //on vérifie si le fournisseur existe ou si il en existe plusieur et qu'on n'arrive pas à déterminer quel est le bon
            $sql = 'SELECT * FROM ' .MAIN_DB_PREFIX. 'societe WHERE code_fournisseur IS NOT NULL AND nom = "'. $objXml->fournisseur . '";';
            $resql = $this->db->query($sql);
            if(!($this->db->num_rows($resql))){
                $error++;
                $this->errors[] = 'Impossible de créer la facture : le fournisseur "' .$objXml->fournisseur. '" n\'existe pas';
            } elseif ($this->db->num_rows($resql) > 1){
                $error++;
                $this->errors[] = 'Impossible de créer la facture : plusieurs fournisseurs au nom de "' .$objXml->fournisseur. '" existent';
            } else {
                $supplier = $this->db->fetch_object($resql);
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
            $TSupplierProducts = array();
            foreach ($objXml->lines as $item) {
                foreach ($item as $line) {
                    //id produit en fonction de la ref donnée
                    $refProduct = $line->ref->__toString();
                    $sql = 'SELECT * FROM llx_product WHERE ref = "' . $refProduct . '"';
                    $resql = $this->db->query($sql);

                    if($this->db->num_rows($resql) == 1) {
                        $product = $this->db->fetch_object($resql);
                        $TSupplierProducts[$refProduct]['id'] = $product->rowid;                    //id du produit
                        $TSupplierProducts[$refProduct]['type'] = $product->fk_product_type;        //type du produit (produit ou service)
                    } else {
                        if ($this->db->num_rows($resql) == 0) {
                            $error++;
                            $this->errors[] = 'Produit/service "' . $refProduct . '" inexistant';
                            continue;
                        } elseif ($this->db->num_rows($resql) > 1) {
                            $error++;
                            $this->errors[] = 'Plusieurs produits existants : ref ' . $refProduct;
                            continue;
                        }
                    }
                }
            }
        }


        //DONNEES OBLIGATOIRES OK ET PRODUITS/SERVICES OK : CREATION FACTURE
        if(!$error){

            //RAJOUT DONNEES NON OBLIGATOIRES
            if(!empty($objXml->date_echeance)){
                $supplierInvoice->date_echeance = strftime('%Y-%m-%d',strtotime($objXml->date_echeance));
            }

            //CREATION DE LA FACTURE
            $id_supplierInvoice = $supplierInvoice->create($user);

            //ON RECUPERE LA FACTURE CREE
            $supplierInvoice->fetch($id_supplierInvoice);

            //REFERENCE EXTERNE : NOM DU FICHIER XML SOURCE
            $supplierInvoice->update_ref_ext($fileXML);

            //ON JOINT LE FICHIER PDF ET XML A LA FACTURE
            $ref = dol_sanitizeFileName($supplierInvoice->ref);
            $local_dir = $conf->fournisseur->facture->dir_output . '/' . get_exdir($supplierInvoice->id, 2, 0, 0, $supplierInvoice, 'invoice_supplier') . $ref;
            if (!dol_is_dir($local_dir)) {
                dol_mkdir($local_dir);
            }

            $remote_file_pdf = $filePDF;
            $remote_file_xml = $fileXML;

            $local_file_pdf = $local_dir . '/' . $remote_file_pdf;
            $local_file_xml = $local_dir . '/' . $remote_file_xml;

            $res = ftp_get($ftpc, $local_file_pdf, $remote_file_pdf, FTP_ASCII);
            if(!$res){
                $error++;
                $this->errors[] = 'Fichier pdf non joint : ' . $supplierInvoice->ref;
            }

            $res = ftp_get($ftpc, $local_file_xml, $remote_file_xml, FTP_ASCII);
            if(!$res){
                $error++;
                $this->errors[] = 'Fichier xml non joint : ' . $supplierInvoice->ref;
            }

            //ON AJOUTE LES LIGNES DE LA FACTURE
            if ($TSupplierProducts){
                foreach ($TSupplierProducts as $product) {

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

        //ON VALIDE LA FACTURE
        if(!$error && !empty($auto_validate)){
            $supplierInvoice->validate($user, $objXml->ref->__toString());
        }

        if($error) {
            return 0;
        } else {
            return $supplierInvoice->id;
        }

    }

    /**
     * Vérifie si le PDF lié à l'objet XML existe
     *
     * @param $PDFlinkedtoXM
     * @param $TFilesPDF
     * @return int 0 si le PDF n'existe pas, string nom du fichier PDF si le PDF existe
     */

    public function verifyPDFLinkedToXML($PDFlinkedtoXML, $TFilesPDF){

        foreach ($TFilesPDF as $filePDF) {
            if ($PDFlinkedtoXML == $filePDF){
                return $filePDF;
            }
        }

        return 0;
    }

    /**
     * Nombre de fichiers XML en attente dans le serveur ftp
     */

    public function get_nb_XMLFilesFTP(){

        $TFiles = $this->get_FilesFTP();

        if(!empty($TFiles['xml'])) {
            return count($TFiles['xml']);
        } else {
            return 0;
        }
    }

    /**
     *  Retourne la liste des fichiers du serveur ftp par type
     */

    public function get_FilesFTP($ftpc){
        global $conf;

        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
        require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';


        //LISTE DES FICHIERS SUR LE SERVEUR FTP
        $TFilesFTP = ftp_nlist($ftpc,ftp_pwd($ftpc));

        if($TFilesFTP){

            $TFiles = array();

            foreach ($TFilesFTP as $filePath) {

                //ON RECUPERE LE NOM DU FICHIER
                str_replace("\\", "/", $filePath);
                $file = basename($filePath);

                //ON RECUPERE L'EXTENSION DU FICHIER
                $filetype = pathinfo($file, PATHINFO_EXTENSION);

                if ($filetype == "xml") {
                    $TFiles['xml'][] = $file;
                } elseif ($filetype == "pdf") {
                    $TFiles['pdf'][] = $file;
                } elseif ($filetype == "") {
                    $TFiles['dir'][] = $file;
                }
            }

            return $TFiles;

        } else {
            return 0;
        }
    }

}