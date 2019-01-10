<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Command;

use MondayFactory\DatabaseModelGenerator\Generator\CollectionGenerator;
use MondayFactory\DatabaseModelGenerator\Generator\DataGenerator;
use MondayFactory\DatabaseModelGenerator\Generator\LowLevelDatabaseStorageGenerator;
use Nette\IOException;
use Nette\Neon\Neon;
use Nette\Utils\FileSystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateBasicModel extends Command
{

	public const COMMAND_NAME = 'basic-model';

	/**
	 * @var array
	 */
	private $definition;

	/**
	 * @var string
	 */
	private $neonName;

	/**
	 * @var array
	 */
	private $generators = ['collection', 'data', 'llstorage'];

	/**
	 * @var bool
	 */
	private $onlyPrint;

	/**
	 * @var bool
	 */
	private $force;

	/**
	 * @var OutputInterface
	 */
	private $output;

	/**
	 * @var string
	 */
	private $projectFilesPath;

	/**
	 * @var InputInterface
	 */
	private $input;


	public function __construct()
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->setName(self::COMMAND_NAME);
		$this->setDescription('Generates basic MondayFactory Model files from definition.');

		$this->addArgument('neonName', InputArgument::REQUIRED, 'Name of the neon with definition.');
		$this->addArgument(
			'projectFilesPath',
			InputArgument::REQUIRED,
			'Path to the project files e.g. "app", or absolute "/data/app". Default is used getcwd(): ' . getcwd() . '.');
		$this->addArgument(
			'whatGenerate',
			InputArgument::IS_ARRAY | InputArgument::OPTIONAL ,
			'What You need to generate? You can add more types as list separated by space. 
				For all leave argument blank. Allowed values: 
				[' . join(', ', $this->generators) . ']' , ['collection', 'data', 'llstorage']);
		$this->addOption('def-dir', null, InputOption::VALUE_REQUIRED, '', null);
		$this->addOption('ignored-namespace', 'I', InputOption::VALUE_REQUIRED, '', null);
		$this->addOption('only-print', 'p', InputOption::VALUE_NONE);
		$this->addOption('force', 'f', InputOption::VALUE_NONE);
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): void
	{
		$this->input = $input;

		if (is_string($input->getArgument('projectFilesPath'))) {
			$this->projectFilesPath = $input->getArgument('projectFilesPath');
		} else {
			throw new \InvalidArgumentException('Parameter projectFilesPath must be string.');
		}

		$this->onlyPrint = is_bool($input->getOption('only-print'))
			? $input->getOption('only-print')
			: false;
		$this->force = is_bool($input->getOption('force'))
			? $input->getOption('force')
			: false;
		$this->output = $output;

		try {
			if (! is_string($input->getArgument('neonName'))) {
				throw new \InvalidArgumentException('Neon name must be string.');
			}

			$this->neonName = $input->getArgument('neonName');
			$neon = $this->resolveNenonPath();
			$neonContent = file_get_contents($neon);

			if ($neonContent === false) {
				throw new \UnexpectedValueException('Neon file can not be read.');
			}

			$this->definition = Neon::decode($neonContent);

		} catch (\Exception $e) {
			$output->writeln($e->getMessage());

			return;
		}

		$whatGenerate = is_array($input->getArgument('whatGenerate'))
			? $input->getArgument('whatGenerate')
			: $this->generators;

		foreach ($whatGenerate as $generator) {
			if (! in_array($generator, $this->generators, true)) {
				$output->writeln("Generator {$generator} is not allowed. Skipping.");

				continue;
			}

			switch ($generator) {
				case 'collection':
					$this->generateCollection();

					break;
				case 'data':
					$this->generateData();

					break;
				case 'llstorage':
					$this->generateLlstorage();

					break;
			}
		}

		$this->output->writeln("Done.");
	}

	private function generateCollection(): void
	{
		$collectionGenerator = new CollectionGenerator($this->definition, $this->getNeonName());

		$this->processOutput($collectionGenerator->getContent(), $collectionGenerator->getFileNamespace());
	}

	private function generateData(): void
	{
		$dataGenerator = new DataGenerator($this->definition, $this->getNeonName());


		$this->processOutput($dataGenerator->getContent(), $dataGenerator->getFileNamespace());
	}

	private function generateLlstorage(): void
	{
		$lowLevelDatabaseStorage = new LowLevelDatabaseStorageGenerator($this->definition, $this->getNeonName());

		$this->processOutput($lowLevelDatabaseStorage->getContent(), $lowLevelDatabaseStorage->getFileNamespace());
	}

	private function processOutput(string $output, string $filePath): void
	{
		$this->write($output, $filePath);
	}

	private function write(string $content, string $namespace): void
	{
		$namespace = ltrim(str_replace('\\', '/', $namespace), '/');

		if (is_string($this->input->getOption('ignored-namespace')))
		{
			$ignoredFileSpace = str_replace('\\', '/', $this->input->getOption('ignored-namespace'));
			$ignoredFileSpace = rtrim(ltrim($ignoredFileSpace, '/'), '/');

			$namespace = ltrim(substr($namespace, strlen($ignoredFileSpace)), '/');
		}

		$projectFilesPath = '';

		if (strlen($this->projectFilesPath) > 0) {
			$projectFilesPath = substr($this->projectFilesPath, 0, 1) === '/'
				? realpath($this->projectFilesPath)
				: realpath(getcwd() . '/' . $this->projectFilesPath);
		} else {
			$cwd = getcwd();
			$projectFilesPath = $cwd !== false
				? realpath($cwd)
				: false;
		}

		$projectFilesPath = $projectFilesPath === false
			? ''
			: $projectFilesPath;

		$fileName = $projectFilesPath . '/' . $namespace . '.php';

		if ($this->onlyPrint) {
			$this->output->writeln($fileName);
			$this->output->writeln($content);
		} else {
			if ($this->force) {
				try {
					FileSystem::write($fileName, $content);
				} catch (IOException $e) {
					$this->output->write("File {$fileName} cannot be written. " . $e->getMessage());
				}
			} else {
				if (! file_exists($fileName)) {
					try {
						FileSystem::write($fileName, $content);
					} catch (IOException $e) {
						$this->output->write("File {$fileName} cannot be written. " . $e->getMessage());
					}
				} else {
					$this->output->writeln("File {$fileName} exists. Use force if you need recreate the model.");
				}
			}
		}
	}

	private function resolveNenonPath(): string
	{
		$path = __DIR__ . '/../../../../../modelDefinition/';

		if (is_string($this->input->getOption('def-dir'))) {
			$path = getcwd() . '/' . $this->input->getOption('def-dir');
		}

		$filePath = realpath($path . '/' . $this->getNeonName() . '.neon');

		if ($filePath !== false && file_exists($filePath))
		{
			return $filePath;
		}

		throw new \InvalidArgumentException("File {$path}/{$this->getNeonName()}.neon not found.");
	}

	private function getNeonName(): string
	{
		$result = preg_replace('/.neon/', '', $this->neonName);

		if (is_null($result)) {
			throw new \Exception('Some error occured.');
		}

		return $result;
	}

}
