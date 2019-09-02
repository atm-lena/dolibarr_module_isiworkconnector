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

    public function getWaitingFiles(){
       //TO DO : return num of waiting files in FTP server
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