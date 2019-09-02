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

/**
 *	\file		lib/isiworkconnector.lib.php
 *	\ingroup	isiworkconnector
 *	\brief		This file is an example module library
 *				Put some comments here
 */

/**
 * @return array
 */
function isiworkconnectorAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load('isiworkconnector@isiworkconnector');

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/isiworkconnector/admin/isiworkconnector_setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;
    $head[$h][0] = dol_buildpath("/isiworkconnector/admin/isiworkconnector_extrafields.php", 1);
    $head[$h][1] = $langs->trans("ExtraFields");
    $head[$h][2] = 'extrafields';
    $h++;
    $head[$h][0] = dol_buildpath("/isiworkconnector/admin/isiworkconnector_about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@isiworkconnector:/isiworkconnector/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@isiworkconnector:/isiworkconnector/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'isiworkconnector');

    return $head;
}

/**
 * Return array of tabs to used on pages for third parties cards.
 *
 * @param 	isiworkconnector	$object		Object company shown
 * @return 	array				Array of tabs
 */
function isiworkconnector_prepare_head(isiworkconnector $object)
{
    global $langs, $conf;
    $h = 0;
    $head = array();
    $head[$h][0] = dol_buildpath('/isiworkconnector/card.php', 1).'?id='.$object->id;
    $head[$h][1] = $langs->trans("isiworkconnectorCard");
    $head[$h][2] = 'card';
    $h++;
	
	// Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@isiworkconnector:/isiworkconnector/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@isiworkconnector:/isiworkconnector/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'isiworkconnector');
	
	return $head;
}

/**
 * @param Form      $form       Form object
 * @param isiworkconnector  $object     isiworkconnector object
 * @param string    $action     Triggered action
 * @return string
 */
function getFormConfirmisiworkconnector($form, $object, $action)
{
    global $langs, $user;

    $formconfirm = '';

    if ($action === 'valid' && !empty($user->rights->isiworkconnector->write))
    {
        $body = $langs->trans('ConfirmValidateisiworkconnectorBody', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmValidateisiworkconnectorTitle'), $body, 'confirm_validate', '', 0, 1);
    }
    elseif ($action === 'accept' && !empty($user->rights->isiworkconnector->write))
    {
        $body = $langs->trans('ConfirmAcceptisiworkconnectorBody', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmAcceptisiworkconnectorTitle'), $body, 'confirm_accept', '', 0, 1);
    }
    elseif ($action === 'refuse' && !empty($user->rights->isiworkconnector->write))
    {
        $body = $langs->trans('ConfirmRefuseisiworkconnectorBody', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmRefuseisiworkconnectorTitle'), $body, 'confirm_refuse', '', 0, 1);
    }
    elseif ($action === 'reopen' && !empty($user->rights->isiworkconnector->write))
    {
        $body = $langs->trans('ConfirmReopenisiworkconnectorBody', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmReopenisiworkconnectorTitle'), $body, 'confirm_refuse', '', 0, 1);
    }
    elseif ($action === 'delete' && !empty($user->rights->isiworkconnector->write))
    {
        $body = $langs->trans('ConfirmDeleteisiworkconnectorBody');
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmDeleteisiworkconnectorTitle'), $body, 'confirm_delete', '', 0, 1);
    }
    elseif ($action === 'clone' && !empty($user->rights->isiworkconnector->write))
    {
        $body = $langs->trans('ConfirmCloneisiworkconnectorBody', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmCloneisiworkconnectorTitle'), $body, 'confirm_clone', '', 0, 1);
    }
    elseif ($action === 'cancel' && !empty($user->rights->isiworkconnector->write))
    {
        $body = $langs->trans('ConfirmCancelisiworkconnectorBody', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmCancelisiworkconnectorTitle'), $body, 'confirm_cancel', '', 0, 1);
    }

    return $formconfirm;
}
