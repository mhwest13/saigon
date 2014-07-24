<?php
//
// Copyright (c) 2014, Zynga Inc.
// https://github-ca.corp.zynga.com/zcloud-tools/saigon
// Author: Leo Nishio
// License: BSD 2-Clause
//

/* Header file
 *  Reasons for being a php file, is in case we wanted to do something in the header.
 */

?>
<html>
<head>
<link type='text/css' rel='stylesheet' href='static/css/admin.css'>
<style>
a:link,a:visited { display:block;width:145px;font-weight:bold;color:#000000;background-color:#66a9bd;text-align:center;padding:4px;text-decoration:none;border-radius:6px;border-width:2px;border-style:solid;border-color:#000000; }
a:hover,a:active { background-color:#719ba7; }
</style>
</head>
<body>

<script type="text/javascript">
	function goto (deployment) {
		window.open('action.php?controller=core&action=menu&deployment=' + deployment, 'input');
	}
</script>
<?php
/* 
ul { list-style-type:none;margin:0;padding:0;overflow:hidden; }
li { float:left;padding:1; }
*/

	$validConrtollers = array( 'cgicfg', 'command', 'contact', 'contactgrp', 'contacttemp', 'core', 
								'deployment', 'hostgrp', 'hosttemp', 'modgearmancfg', 'nagioscfg', 'nagiosplugin',
								'ngnt', 'nrpecfg', 'nrpecmd', 'nrpeplugin', 'resourcecfg', 'svc', 'svcdep', 
								'svcesc', 'svcgrp', 'svctemp', 'timeperiod' );

	$cmdControllerMap = array( 	'commands'   => 'command',
								'hostgroup'  => 'hostgrp',
								'hostgroups' => 'hostgrp',
								'hosttemplate'  => 'hosttemp',
								'hosttemplates' => 'hosttemp',
								'nodetemplate'  => 'ngnt',
								'nodetemplates' => 'ngnt',
								'nrpecmds'      => 'nrpecmd',
								'nrpeplugins'   => 'nrpeplugin',
								'svcs'          => 'svc',
								'timeperiods'   => 'timeperiod',
								'contacttemplate'  => 'contacttemp',
								'contacttemplates' => 'contacttemp',
								'contacts'         => 'contact',
	);
	
	$cmdActionMap = array( 'ngnt' => 'manage' );
	
	foreach ($viewData->searchResults as $deployment => $matches) {
		if (isset($matches['match']) && $matches['match']) {
			
			echo "<h2>Match in Deployment: $deployment</h2>\n";
			
			// Deployment Info Section
			if (isset($matches['deployment_info']) || isset($matches['nonversioned'])) {
				echo "<a style=\"background-color: #ad4f5d\" href=\"action.php?controller=deployment&action=modify_stage&deployment=$deployment\" target=\"output\" onClick=\"goto($deployment)\">Deployment Info</a>\n";
				if (isset($matches['deployment_info']['key_name']) && count($matches['deployment_info']['key_name'])) {
					echo "<ul>\n";
					foreach ($matches['deployment_info']['key_name'] as $infoKey) {
						echo "<li>Key Name match: <b>$infoKey</b></li>\n";
					}
					echo "</ul>\n";
				}
				if (isset($matches['deployment_info']['key_value']) && count($matches['deployment_info']['key_value'])) {
					echo "<ul>\n";
					foreach ($matches['deployment_info']['key_value'] as $infoVal) {
						echo "<li>Value match: <b>$infoVal</b></li>\n";
					}
					echo "</ul>\n";
				}
			}
			
			// hostsearch* keys
			$replaceMe = "/^".$matches['deployment_hash'].":/";
			if (isset($matches['nonversioned'])) {
				if (isset($matches['nonversioned']['key_name']) && count($matches['nonversioned']['key_name'])) {
					echo "<ul>\n";
					foreach ($matches['nonversioned']['key_name'] as $nvmKey) {
						echo "<li>Key Name match: <b>".preg_replace($replaceMe, '', $nvmKey)."</b></li>\n";
					}
						echo "</ul>\n";
				}
				if (isset($matches['nonversioned']['key_value']) && count($matches['nonversioned']['key_value'])) {
					echo "<ul>\n";
					foreach ($matches['nonversioned']['key_value'] as $nvKey => $nvVal) {
						foreach ($nvVal['key_value'] as $parmVal) {
							echo "<li>Value match in <b>".preg_replace($replaceMe, '', $nvKey).": $parmVal</b></li>\n";
						}
					}
					echo "</ul>\n";
				}
			}
			
			// All Versioned Keys
			if (isset($matches['versioned'])) {
				echo "<h3>Versioned Info (version: <b>".$matches['version']."</b>)</h3>";
				$replaceMe = "/^".$matches['deployment_hash'].":".$matches['version'].":/";
				$lastLink = '';
				
				if (isset($matches['versioned']['key_name']) && count($matches['versioned']['key_name'])) {
					echo "<ul>\n";
					foreach ($matches['versioned']['key_name'] as $vmKey) {
						$stripped = preg_replace($replaceMe, '', $vmKey);
						list($cmd) = explode(':', $stripped);
						if (isset($cmdControllerMap[$cmd])) $cmd = $cmdControllerMap[$cmd];
						if ($cmd != 'testoutput') {
							$action = 'stage';
							if (isset($cmdActionMap[$cmd])) $action = $cmdActionMap[$cmd];
							$link = "<a href=\"action.php?controller=$cmd&action=$action&deployment=$deployment\">$cmd</a>";
							if ($link != $lastLink) {
								echo "</ul>$link<ul>\n";
							}
							echo "<li>Key Name match: <b>".$stripped."</b></li>\n";
							$lastLink = $link;
						}
					}
					echo "</ul>\n";
				}
				if (isset($matches['versioned']['key_value']) && count($matches['versioned']['key_value'])) {
					echo "<ul>\n";
					foreach ($matches['versioned']['key_value'] as $vKey => $vVal) {
						if (isset($vVal['key_value'])) {
							$stripped = preg_replace($replaceMe, '', $vKey);
							list($cmd) = explode(':', $stripped);
							if (isset($cmdControllerMap[$cmd])) $cmd = $cmdControllerMap[$cmd];
							if ($cmd != 'testoutput') {
								$action = 'stage';
								if (isset($cmdActionMap[$cmd])) $action = $cmdActionMap[$cmd];
								$link = "<a href=\"action.php?controller=$cmd&action=$action&deployment=$deployment\">$cmd</a>";
								if ($link != $lastLink) {
									echo "</ul>$link<ul>\n";
								}
								foreach ($vVal['key_value'] as $parmVal) {
									echo "<li>Value match in <b>".$stripped.": $parmVal</b></li>\n";
								}
								$lastLink = $link;
							}
						}
					}
					echo "</ul>\n";
				}
			}
			
			
		}
	}
?>

<div style="visibility: hidden">
<H1>Search Data Dump: <?php echo date('-r'); ?></H1>
<pre>
search =  <?php echo $viewData->search; ?>

deployment =  <?php echo $viewData->deployment; ?>

--------------------------------------------------------------------------------
<?php echo var_export($viewData->searchResults, true); ?>

--------------------------------------------------------------------------------

</pre>
</div>

</body>
</html>

<?php

