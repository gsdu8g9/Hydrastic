<?php 
namespace Hydra\HydraCommand;

use Hydra\ArrayMerger;
use Hydra\Post;
use Hydra\Taxonomy;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use Symfony\Component\Finder\Finder as SymfonyFinder;

/**
 *
 * @author Baptiste Pizzighini <baptiste@bpizzi.fr>
 */
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
			->setName('hydra:process')
			->setDefinition(array())
			->setDescription('Generate your website')
			->setHelp(<<<EOF
The <info>hydra:process</info> command generate your website !
EOF
		);

	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{

		$dic['output'] = $output;

		$output->writeln($this->dic['conf']['command_prefix'].' Started hydration of your text files');

		$files = $this->dic['finder']['txt_files'];

		$output->writeln($this->dic['conf']['command_prefix'].' Found <comment>'.count($files).' '.$this->dic['conf']['txt_file_extension'].'</comment> files');

		$taxonomy = $this->dic['taxonomy'];

		foreach ($files as $file) {
			$output->writeln('----->');

			$post = new Post($this->dic);

			$post->read($file)
				->clean()
				->parseMetas()
				->setTaxa()
				->parseContent()
				->hydrate()
				->writeToFile();

		} //-- parsing content files

		$output->writeln('----->');

		$output->writeln($this->dic['conf']['command_prefix'].' Done.');

	}
}
