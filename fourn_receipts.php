<?php

require './config.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
require_once './lib/facturerreception.lib.php';

$socid = GETPOST('socid');
$action = GETPOST('action');

llxHeader();

$ATMdb = new TPDOdb;
$soc = new Societe($db);
$soc->fetch($socid);

if (! $user->rights->facture->creer)
	accessforbidden();

/**
 * Actions
 */

switch($action) {
	case 'facturer_receptions':
		_facturer_receptions();
		break;
}

/**
 * View
 */

// Idem std Dolibarr facturer commandes
print_fiche_titre($langs->trans('SupplierReceipts'));
print '<h3>'.$soc->getNomUrl(1,'supplier').'</h3>';
_print_liste_receptions($soc);


/**
 * Functions
 */

function _print_liste_receptions(&$soc) {
	
	global $ATMdb, $langs;
	
	$sql = 'SELECT cf.rowid as id_cmd_fourn, cfd.datec, SUM(cfd.qty) as "nb_produits", "" as case_a_cocher
			FROM '.MAIN_DB_PREFIX.'commande_fournisseur_dispatch cfd
			INNER JOIN '.MAIN_DB_PREFIX.'commande_fournisseur cf ON cf.rowid = cfd.fk_commande
			WHERE fk_soc='.$soc->id.'
			GROUP BY cfd.datec';
	
	$l=new TListviewTBS('list_receptions');
	
	print $l->render($ATMdb, $sql, array(
		'title'=>array(
			'nb_produits'=>$langs->trans('nbProduits')
			,'datec'=>$langs->trans('ReceiptDate')
			,'id_cmd_fourn'=>$langs->trans('SupplierOrder')
			,'case_a_cocher'=>$langs->trans('CaseACocher')
		)
		,'eval'=>array(
			'id_cmd_fourn'=>'get_nom_url(@val@)'
			,'case_a_cocher'=>'get_checkbox("@id_cmd_fourn@", "@datec@")'
		)
		,'type'=>array(
			'datec'=>'date'
		)
	));
	
}

function _facturer_receptions() {
	
	global $db;
	
}

function get_nom_url($id) {
	
	global $db;
	
	$cmd = new CommandeFournisseur($db);
	$cmd->fetch($id);
	
	return $cmd->getNomUrl(1);
	
}

function get_checkbox($id_cmd_fourn, $date_receipt) {
	
	return '<input type="checkbox" name="TReceipts['.$id_cmd_fourn.']" value="'.$date_receipt.'"/>';
	
}
