<?php

class ToolsController extends giController {


	protected $PluginsAssetsToInstall;
	
	// ensure we are in local mode before allowing further actions
	public function preAction() {
	
		// if not in local mode
		if($this->Core->getEnvironment() != 'local') {
			// prevent any further execution with a ->Response->403 as soon as it works again…
			$this->Core->Response->setType('text');
			$this->Core->Response->setContent("Not allowed in production\n");
			$this->Core->Response->output();
		}
		
	}


	public function indexAction() {
	
		$this->Core->Response->setType('text');
		$this->View('index');
		
	}
	
	public function purgeMemcacheAction() {
	
		$status = $this->Core->Database->flushCache();
		$this->Core->Response->setType('text');
		if($status) { $message = 'Flushed the hell out of it'; }
		else { $message = 'Cannot flush'; }
		$this->Core->Response->setContent($message);
		
	}
	
	// this will respond to /toolbox/installAssets/
	public function installAssetsAction() {


		// get plugins list
		$this->PluginsAssetsToInstall = $this->scanPluginsFolder();

		$this->Core->Response->setType('text');
		$this->Core->Response->setContent("** Installing assets symlinks\n\n");

		// pour cacun des plugins
		foreach($this->PluginsAssetsToInstall as $aPluginName => $aPluginFolder){
			$this->Core->Response->addContent("*  Installing assets for plugin : $aPluginName \n");
			$this->Core->Response->addContent("-> Traitement do dossier: {$aPluginFolder}/assets/");
			$pluginTypes = array();
			// balayer dossier assets
			foreach(scandir($aPluginFolder . '/assets/') as $aPlugin) {
				// if not hidden
				if(substr($aPlugin,0,1) != '.') {
					$pluginTypes[] = strtolower(giHelper::slugify($aPlugin));
				}
			}

			// installer les assets
			foreach($pluginTypes as $type){
				
				// install asset
				$this->installAction($aPluginName,$aPluginFolder,$type);
			}
			$this->Core->Response->addContent("*  Finished installing assets for plugin : {$aPluginName}\n\n");

		}

		$this->Core->Response->addContent("** Installation proccess ended");

	}


	// this will NOT respond to /toolbox/install/ as the method is private
	private function installAction($aPluginName,$aPluginFolder,$type) {
		
		$this->Core->Response->addContent("-> Installing $aPluginName -> ".strtoupper($type)."\n");
		$privateAssetsPath = $aPluginFolder.'/assets/'.$type;
		$publicAssetsTypeFolder = '../public/assets/'.$type;
		$publicPluginAssetsFolder = $pluginAssetsFolder.'/'.$aPluginName;
		// if folder public/assets/pluginname does not exit
		if(!file_exists($publicAssetsTypeFolder) && !is_dir($publicAssetsTypeFolder)) {
			mkdir($publicAssetsTypeFolder,0777);
			$this->Core->Response->addContent("-> Creating folder : {$publicAssetsTypeFolder} in public/assets");
		}

		if(!file_exists($publicPluginAssetsFolder)) {
			$src = '../../'.$aPluginFolder.'/assets/'.$type;
			$target = $aPluginName;
			$currentDir = getcwd();
			chdir($publicAssetsTypeFolder);
			$success = @symlink($src, $target);
			$this->Core->Response->addContent("-> Creating link for [{$src}] with name $target in public/assets/{$type} : ");
			if($success) {
				$this->Core->Response->addContent("OK\n");
			}
			else {
				$this->Core->Response->addContent("ERROR\n");
			}
			chdir($currentDir);
		} 
	}

	private function scanPluginsFolder() {
		// scan all plugins
		$pluginsList = array();
		foreach(scandir('../private/plugins/') as $aPlugin) {
			// if the folder is an actual plugin
			if(substr($aPlugin,0,1) != '.') {
				// if the assets folder exist
				if(file_exists('../private/plugins/' . $aPlugin . '/assets/')) {
					// set the plugin assets path
					$pluginsList[$aPlugin]	= '../private/plugins/' . $aPlugin ;
				}

			//
			}
		}
		return($pluginsList);
	}

}

?>