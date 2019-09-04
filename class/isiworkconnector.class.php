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

        $res = isiworkconnector::transferFilesFTP();

        if(empty($res)) {
            $error ++;
            $this->errors[] = 'Erreur : transfert des fichiers du serveur FTP en local incomplet';
        }

        if(!$error) {

        }

    }

    public function transferFilesFTP(){
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

            //CREATION DOSSIER "TRAITE" SI INEXISTANT
            $dir = $conf->isiworkconnector->dir_output . '/traite';
            if(!is_dir($dir)){
                dol_mkdir($dir);
            }

            //LISTE DES FICHIERS FTP
            $TFile = ftp_mlsd($ftpc,ftp_pwd($ftpc));

            foreach ($TFile as $file) {
                if ($file['name'] != '.' && $file['name'] != '..' && $file['type'] == 'file') {
                    $local_file = $conf->isiworkconnector->dir_output . '/' . $file['name'];
                    $remote_file = $file['name'];
                    //TRANSFERT DU FICHIER FTP EN LOCAL
                    $res = ftp_get($ftpc, $local_file, $remote_file, FTP_ASCII);
                    if($res){
                        //SI TRANFERT OK, SUPPRESSION DU FICHIER SUR LE SERVEUR FTP
                        ftp_delete($ftpc, $remote_file);
                    } else {
                        $error ++;
                        $this->errors[] = "Echec transfert du document : " . $file['name'];
                    }
                }
            }
            if(!$error){
                return 1;
            }
        }

        if($error) {                                                                            //CONNEXION FTP OUT
            setEventMessages('', $this->errors[0], "errors");
            return 0;
        }
    }

    /**
     * Nombre de fichiers XML en attente dans le serveur ftp
     */

    public function get_nb_XMLFilesFTP(){

        $res = $this->get_XMLFilesFTP();

        if(is_array($res)) {
            return count($res);
        } else {
            return 0;
        }
    }

    /**
     *  Retourne la liste des fichiers XML du serveur ftp
     */

    public function get_XMLFilesFTP (){
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

            //LISTE FICHIERS
            $TFile = ftp_mlsd($ftpc,ftp_pwd($ftpc));

            //LISTE FIHIERS XML A TRAITER
            $TFileXML = array();
            foreach ($TFile as $file){
                if($file['name'] != "." && $file['name'] != ".." && $file['type'] == "file" ) {
                    $filetype = pathinfo($file['name'], PATHINFO_EXTENSION);                //on récupère l'extension des fichiers
                    if($filetype == "xml") {
                        $TFileXML[] = $file;
                    }
                }
            }
            return $TFileXML;
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