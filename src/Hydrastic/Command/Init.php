<?php 
/**
 * This file is part of the Hydrastic package.
 *
 * (c) Baptiste Pizzighini <baptiste@bpizzi.fr> 
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Hydrastic\command;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Helper\DialogHelper;


/**
 * @author Baptiste Pizzighini <baptiste@bpizzi.fr>
 */
class Init extends SymfonyCommand
{

	protected $dic;

	public function __construct($dic)
	{
		parent::__construct();
		$this->dic = $dic;
	}

	/**
	 * @see Command
	 */
	protected function configure()
	{
		$this
			->setName('hydrastic:init')
			->setDefinition(array())
			->setDescription('Initiate folder structure and config file')
			->setHelp(<<<EOF
The <info>hydrastic:init</info> command checks if the current directory could be used for holding your txt/twig/html files.
If there no folder in the current place, then you can name some.
If there is already some folders, you can tell Hydrastic which one should be used for txt/twig/html files.
If an hydrastic-conf.yml exists, <info>hydrastic:init</info> will ask you to confirm it.
If there is no hydrastic-conf.yml yet, you will have the opportunity to create it here
EOF
		);

	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$dialog = new DialogHelper();

		if (!$this->dic['user_conf_defined']) {
			$output->writeln($this->dic['conf']['command_prefix'].' hydrastic-conf.yml not found, let\'s see if we can create one together.');

			//Definition of the type of folders
			$folderConf = array(
				'txt_dir' => null,
				'tpl_dir' => null,
				'www_dir' => null,
			);
			$folderConfDefinition = array(
				'txt_dir' => array('type' => 'text files', 'default' => 'txt'),
				'tpl_dir' => array('type' => 'template files', 'default' => 'tpl'),
				'www_dir' => array('type' => 'generated html files', 'default' => 'www'),
			);

			// Look for folders in current directory 
			$f = $this->dic['finder']['find'];
			$f->directories()->depth('< 1')->in($this->dic['working_directory']);

			//Foreach folder we found, ask which type of file it should be used for
			foreach ($f as $d) {
				$question = $this->dic['conf']['command_prefix']." <comment>".$d->getRelativePathname()."</comment> folder found, shall I use it for :\n"
					."        1) Holding text files (answer <info>txt</info>)\n"
					."        2) Holding template files (answer <info>tpl</info>)\n"
					."        3) Holding generated html files (answer <info>www</info>)\n"
					."        4) None (answer <info>none</info>)\n"
					.">";
				$validate = function ($v) {
					return in_array($v, array('txt', 'tpl', 'www', 'none')) ? $v : false;
				};
				try {
					$response = $dialog->askAndValidate($output, $question, $validate, 3);
				} catch (\Exception $e) {
					$output->writeln('<error>[error]</error> Error : Maximum tries reached (3)');
					die();
				}
				if ($response === "none") {
					$output->writeln($this->dic['conf']['command_prefix'].' '.$d->getRelativePathname().' will not be used by Hydrastic');
				} else {
					$output->writeln($this->dic['conf']['command_prefix'].' '.$d->getRelativePathname().' will be used for holding <info>'.$response.'</info> files');
					$folderConf[$response.'_dir'] = $d->getRelativePathname();
				}
				$output->writeln('---');
				if (false === in_array(null,$folderConf)) {
					//If every folder conf key has been defined, quit the loop
					break;
				}
			} //-- ask the user what should be existing folders used for

			if (iterator_count($f) == 0 ) {
				$output->writeln($this->dic['conf']['command_prefix'].' No folders found, let\'s create some.');
			} 

			//If a type of folder isn't defined, ask the user to create it
			foreach ($folderConf as $key => $folder) {
				if (is_null($folder)) {
					$output->writeln($this->dic['conf']['command_prefix'].' You need to create a folder for holding <comment>'.$folderConfDefinition[$key]["type"].'</comment>');
					$folder = $dialog->ask($output, "Tell me the name of that folder and I shall create it for you (may I suggest <comment>".$folderConfDefinition[$key]["default"]."</comment> ?):");
					$folderConf[$key] = $folder;
				}
			} //-- creation of folders yet to be configured

			//Configuration keys for the static site (those are default values 
			//that could be overidden by each text file)
			$siteConf = array(
				'title'        => null,
				'description'  => null,
				'author'       => null,
			);

			foreach ($siteConf as $k => $v) {
				$siteConf[$k] = $dialog->ask($output, $this->dic['conf']['command_prefix']." Please enter your site configuration for the key: <comment>".$k."</comment>");
				$output->writeln('---');
			}

			//Reviewing the configuration
			$confValid = false;
			while (false === $confValid) {
				$output->writeln($this->dic['conf']['command_prefix'].' Almost done! please review your configuration before I write it to disc:');
				foreach ($folderConf as $k => $v) {
					$output->writeln('         For holding <comment>'.$folderConfDefinition[$k]["type"].'</comment>, following folder will be used: <comment>'.$v.'</comment> (key <info>'.$k.'</info>)');
				}
				foreach ($siteConf as $k => $v) {
					$output->writeln('         The configuration key <comment>'.$k.'</comment>, for your site is: <comment>'.$v.'</comment> (key <info>'.$k.'</info>)');
				}
				$response = $dialog->ask($output, $this->dic['conf']['command_prefix']." If it looks ok to you, answer <info>done</info>.\n".$this->dic['conf']['command_prefix']." if you want to modify a configuration key now: please indicate me that key (you will have the opportunity to tweak it later by modifying <comment>hydrastic-conf.tml</comment>)");
				if ($response == "done") {
					$confValid = true;
				} else {
					//Last chance update of a configuration key
					if (array_key_exists($response, $folderConf)) {
						$folderConf[$response] = $dialog->ask($output, "          New folder for holding <comment>".$folderConfDefinition[$k]["type"]."</comment>: ");
					}
					if (array_key_exists($response, $siteConf)) {
						$siteConf[$response] = $dialog->ask($output, $this->dic['conf']['command_prefix']."New value for site configuration key <comment>".$response."</comment>: ");
					}
				}
			}


			//Dumping configuration to yml and creating folders
			$output->writeln('---');
			foreach ($folderConf as $k => $folder) {
				if (false === is_dir($this->dic['working_directory'].'/'.$folder)) {
					if (false === @mkdir($folder)) {
						$output->writeln('<error>[error]</error> Creation of the folder "'.$folder.'" failed.');
					} else {
						$output->writeln($this->dic['conf']['command_prefix'].' Folder created : <info>'.$folder.'/</info>');
					}
				} else {
					$output->writeln($this->dic['conf']['command_prefix'].' Folder already exists : <info>'.$folder.'/</info>');
				}
			}
			$masterConfig = array_merge_recursive($folderConf, array('metadata_defaults' => array('General' => $siteConf)));
			$dumper = $this->dic['yaml']['dumper'];
			file_put_contents('hydrastic-conf.yml',$dumper->dump($masterConfig, 3));

			//Done ! :)
			$output->writeln($this->dic['conf']['command_prefix'].' Configuration file writed to disc.');
			$output->writeln($this->dic['conf']['command_prefix'].' You can begin to write your templates and text files and try <info>hydrastic:process</info> command to generate you static content!');

			//Create an executable shortcut to hydrastic.phar under linux
			if (PHP_OS == 'Linux') {
				file_put_contents('hydrastic', "#!/bin/sh\nphp hydrastic.phar $@");
				system('chmod +x hydrastic');
				$output->writeln($this->dic['conf']['command_prefix'].' I created a shorcut for you : you can now run me with <info>./hydrastic</info>, assuming PHP binary is accessible from your ENV.');
			}

		} else {
			$output->writeln($this->dic['conf']['command_prefix'].' I can see you already have a configuration file. Please make a backup and delete it before running me again.');
		}//-- creation of a user config file


	}

}