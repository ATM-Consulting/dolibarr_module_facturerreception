<?php

require './config.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
require_once './lib/facturerreception.lib.php';

$langs->load("facturerreception@facturerreception");
$langs->load("orders");
$socid = GETPOST('socid');
$action = GETPOST('action');

$ATMdb = new TPDOdb;
$soc = new Societe($db);
$soc->fetch($socid);

/**
 * Actions
 */

switch($action) {
	case 'facturer_receptions':
		$res = _facturer_receptions();
		break;
}

/**
 * View
 */

llxHeader();

if (! $user->rights->facture->creer)
	accessforbidden();
 
// Idem std Dolibarr facturer commandes
print_fiche_titre($langs->trans('SupplierReceipts'));
print '<h3>'.$soc->getNomUrl(1,'supplier').'</h3>';
_print_liste_receptions($soc);

llxFooter();

/**
 * Functions
 */

function _print_liste_receptions(&$soc) {
	
	global $ATMdb, $langs;
	
	print '<form name="facturer_receptions" method="POST" action="?socid='.$soc->id.'">';
	print '<input type="hidden" name="action" value="facturer_receptions" />';
	
	$sql = 'SELECT cf.rowid as id_cmd_fourn, DATE_FORMAT(datec, "%Y-%m-%d %H:00:00") as date, SUM(cfd.qty) as "nb_produits", "" as case_a_cocher
			FROM '.MAIN_DB_PREFIX.'commande_fournisseur_dispatch cfd
			INNER JOIN '.MAIN_DB_PREFIX.'commande_fournisseur cf ON cf.rowid = cfd.fk_commande
			WHERE fk_soc='.$soc->id.'
			AND cf.fk_statut IN(4,5)
			GROUP BY cf.rowid, date';
	
	$l=new TListviewTBS('list_receptions');
	
	print $l->render($ATMdb, $sql, array(
		'title'=>array(
			'nb_produits'=>$langs->trans('nbProduits')
			,'date'=>$langs->trans('ReceiptDate')
			,'id_cmd_fourn'=>$langs->trans('SupplierOrder')
			,'case_a_cocher'=>$langs->trans('CaseACocher')
		)
		,'eval'=>array(
			'id_cmd_fourn'=>'get_nom_url(@val@)'
			,'case_a_cocher'=>'get_checkbox("@id_cmd_fourn@", "@date@")'
		)
		,'type'=>array(
			'date'=>'datetime'
		)
	));
	
	$form = new Form($db);
	print '<br />';
	
	print '<div align="right">';
	print $langs->trans('facturerreception_date_facture').' '.$form->select_date();
	print '<input type="SUBMIT" class="butAction" value="'.$langs->trans('RunBillReceipts').'" name="btSubFormFactReceipts" />';
	print '</div>';
	
	print '</form>';
	
}

function _facturer_receptions() {
	
	global $db, $langs;
	
	$TReceipts = $_REQUEST['TReceipts'];
	$date = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
	
	if(!empty($TReceipts)) {
		
		$Tab = array();
		$TCMDFourn = array();
		
		foreach($TReceipts as $id_cmd_fourn=>$TReceptions) {
			$cmd_fourn = new CommandeFournisseur($db);
			$cmd_fourn->fetch($id_cmd_fourn);
			
			foreach($TReceptions as $datereception) {
				
				$sql = "SELECT fk_commandefourndet,fk_product,SUM(qty) as qty
						FROM ".MAIN_DB_PREFIX."commande_fournisseur_dispatch 
						WHERE fk_commande=".$cmd_fourn->id."
						AND datec LIKE '".date('Y-m-d H', strtotime($datereception))."%'
						GROUP BY fk_commandefourndet,fk_product";
				
				$db->query($sql);
				
				while($obj = $db->fetch_object($res)) {
					
					$obj->line = getGoodLine($cmd_fourn, $obj->fk_commandefourndet, $obj->fk_product);
					$Tab[] = $obj;
					
				}
			}
			
			$TCMDFourn[] = $cmd_fourn;
	
		}
		
		setEventMessage($langs->trans('ReceiptsBilled', count($Tab)));
		
		createFacture($TCMDFourn, $Tab, $date);
		
	} else {
		setEventMessage($langs->trans('NoReceiptSelected'));
	}
	
}

function get_nom_url($id) {
	
	global $db;
	
	$cmd = new CommandeFournisseur($db);
	$cmd->fetch($id);
	
	return $cmd->getNomUrl(1);
	
}

function get_checkbox($id_cmd_fourn, $date_receipt) {
	
	return '<input type="checkbox" name="TReceipts['.$id_cmd_fourn.'][]" value="'.$date_receipt.'"/>';
	
}
