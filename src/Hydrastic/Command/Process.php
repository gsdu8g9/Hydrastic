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

use Hydrastic\ArrayMerger;
use Hydrastic\Post;
use Hydrastic\Taxonomy;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use Symfony\Component\Finder\Finder as SymfonyFinder;

class Process extends SymfonyCommand
{
	protected $dic = array();

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
			->setName('hydrastic:process')
			->setDefinition(array())
			->setDescription('Generate your website')
			->setHelp(<<<EOF
The <info>hydrastic:process</info> command generate your website !
EOF
		);

	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{

		$this->dic['output'] = $output;

		$output->writeln($this->dic['conf']['command_prefix'].' Started hydration of your text files');

		$files = $this->dic['finder']['txt_files'];

		$output->writeln($this->dic['conf']['command_prefix'].' Found <comment>'.count($files).' '.$this->dic['conf']['General']['txt_file_extension'].'</comment> files');

		$taxonomy = $this->dic['taxonomy'];
		$taxonomy->initiateTaxonStorage();

		foreach ($files as $file) {
			$output->writeln('----->');

			$post = new Post($this->dic);

			$post->read($file)
				->clean()
				->parseMetas()
				->parseContent()
				->hydrate()
				->attachToTaxonomy();

		} //-- parsing content files

		$taxonomy->cleanWwwDir()->createDirectoryStruct();

		$output->writeln('----->');

		$output->writeln($this->dic['conf']['command_prefix'].' Done.');

	}
}