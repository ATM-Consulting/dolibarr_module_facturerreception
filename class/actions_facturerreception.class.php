<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
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
 * \file    class/actions_facturerreception.class.php
 * \ingroup facturerreception
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class ActionsfacturerReception
 */
class ActionsfacturerReception
{
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $user,$conf,$langs,$db;
		
		dol_include_once('/facturerreception/lib/facturerreception.lib.php');
		
		if ($parameters['currentcontext'] == 'ordersuppliercard' && ! empty($conf->fournisseur->enabled) && $object->statut >= 2 && $action=='billedreception')  // 2 means accepted
		{
			if ($user->rights->fournisseur->facture->creer)
			{
				
				$datereception = GETPOST('datereception');
				
				if(!empty($datereception)) {
					$resultset = $db->query("SELECT fk_commandefourndet,fk_product,SUM(qty) as qty
					FROM ".MAIN_DB_PREFIX."commande_fournisseur_dispatch 
					WHERE fk_commande=".$object->id."
					AND datec LIKE '".date('Y-m-d H', strtotime($datereception))."%'
					GROUP BY fk_commandefourndet,fk_product
					"
					);
					
					$Tab = array();
					while($obj = $db->fetch_object($resultset)) {
						$obj->line = getGoodLine($object, $obj->fk_commandefourndet, $obj->fk_product);
						
						$Tab[] = $obj;
					}
					
					$TObj = array($object);
					createFacture($TObj,$Tab);
					
				}
				
				
			}
			
		}
	}

	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $user,$conf,$langs,$db;
		
		$langs->load('facturerreception@facturerreception');
		
		if ($parameters['currentcontext'] == 'ordersuppliercard' && ! empty($conf->fournisseur->enabled) && $object->statut >= 2)  // 2 means accepted
		{
			if ($user->rights->fournisseur->facture->creer)
			{
				$resultset = $db->query("SELECT DATE_FORMAT(datec,'%Y-%m-%d %H:%i:00') as 'date', datec as 'datem', SUM(qty) as 'nb'
				FROM ".MAIN_DB_PREFIX."commande_fournisseur_dispatch 
				WHERE fk_commande=".$object->id
				." GROUP BY date, datec ");

				if ($resultset)
				{
					$Tab = array();
					while($obj = $db->fetch_object($resultset)) {
						$Tab[$obj->date] = dol_print_date(strtotime($obj->datem), 'dayhour');
					}

					if(empty($Tab)) return 0;

					echo '<form name="facturerreception" action="?id=&action=billedreception" style="display:inline;">';
					echo '<input type="hidden" name="id" value="'.$object->id.'" />';
					echo '<input type="hidden" name="action" value="billedreception" />';
					echo '<select name="datereception" >';
						echo '<option value=""> </option>';

					foreach ($Tab as $k=>$v) {

						echo '<option value="'.$k.'">'.$v.'</option>';

					}
					echo '</select>';

					echo '<input type="submit" class="butAction" value="'.$langs->trans('BillRecep').'" />';

					echo '</form>';

					?>
					<script type="text/javascript">

						$(document).ready(function() {

							$("form[name=facturerreception]").appendTo("div.tabsAction");

						});

					</script>
					<?php	
				}
				else
				{
					dol_print_error($db);
				}
			}
		} elseif($parameters['currentcontext'] == 'suppliercard') {
			
			$path = dol_buildpath('/facturerreception/fourn_receipts.php?socid='.GETPOST('socid'), 1);
						
			?>
			<script type="text/javascript">
			
				$(document).ready(function() {
					$("div.tabsAction").append('<a class="butAction" href="<?php print $path; ?>"><?php print $langs->trans('BillReceipts'); ?></a>');
				});
				
			</script>
			<?php
			
		}

	}
	
	
	
}
