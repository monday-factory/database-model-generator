<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Command;

use MondayFactory\DatabaseModelGenerator\Generator\CollectionGenerator;
use MondayFactory\DatabaseModelGenerator\Generator\DataGenerator;
use MondayFactory\DatabaseModelGenerator\Generator\LowLevelDatabaseStorageGenerator;
use MondayFactory\DatabaseModelGenerator\Generator\Printer;
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

	protected static $defaultName = 'basic-model';

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
	private $printToStdOut;

	/**
	 * @var bool
	 */
	private $dryRun;

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

	/**
	 * @var array
	 */
	private $errors = [];

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
			InputArgument::OPTIONAL,
			'Path to the project files e.g. "app", or absolute "/data/app".',
			getcwd() . '/app/model');
		$this->addArgument(
			'whatGenerate',
			InputArgument::IS_ARRAY | InputArgument::OPTIONAL ,
			'What You need to generate? You can add more types as list separated by space. 
				For all leave argument blank. Allowed values: 
				[' . join(', ', $this->generators) . ']' , ['collection', 'data', 'llstorage']);
		$this->addOption('def-dir', null, InputOption::VALUE_REQUIRED, '', getcwd() . '/modelDefinition');
		$this->addOption('ignored-namespace', 'I', InputOption::VALUE_REQUIRED, '', null);
		$this->addOption('p', 'p', InputOption::VALUE_NONE, 'Print generated files to stdout');
		$this->addOption('dry-run', null, InputOption::VALUE_NONE);
		$this->addOption('force', 'f', InputOption::VALUE_NONE);
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->input = $input;

		if (is_string($input->getArgument('projectFilesPath'))) {
			$this->projectFilesPath = $input->getArgument('projectFilesPath');
		} else {
			throw new \InvalidArgumentException('Parameter projectFilesPath must be string.');
		}

		$this->printToStdOut = is_bool($input->getOption('p'))
			? $input->getOption('p')
			: false;
		$this->dryRun = is_bool($input->getOption('dry-run'))
			? $input->getOption('dry-run')
			: false;
		$this->force = is_bool($input->getOption('force'))
			? $input->getOption('force')
			: false;
		$this->output = $output;

		if ($this->dryRun) {
			$this->output->write("<fg=yellow>Dry run!</>", true);
		}

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

			return 1;
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

		if (count($this->errors)) {
			$this->output->writeln("<fg=yellow>Finished with errors.</> ");

			foreach ($this->errors as $error) {
				$this->output->writeln("<fg=yellow>$error</> ");
			}

		} else {
			$this->output->writeln("<fg=green>Done.</>");
		}

		return 0;
	}

	private function generateCollection(): void
	{
		$collectionGenerator = new CollectionGenerator($this->definition, $this->getNeonName());
		$printer = new Printer();

		$this->processOutput($printer->printFile($collectionGenerator->file), $collectionGenerator->getFileNamespace());
	}

	private function generateData(): void
	{
		$dataGenerator = new DataGenerator($this->definition, $this->getNeonName());
		$printer = new Printer();

		$this->processOutput($printer->printFile($dataGenerator->file), $dataGenerator->getFileNamespace());
	}

	private function generateLlstorage(): void
	{
		$lowLevelDatabaseStorage = new LowLevelDatabaseStorageGenerator($this->definition, $this->getNeonName());
		$printer = new Printer();

		$this->processOutput($printer->printFile($lowLevelDatabaseStorage->file), $lowLevelDatabaseStorage->getFileNamespace());
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

			if (strlen($this->projectFilesPath) > 1 && $projectFilesPath === false) {
			}
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


		if ($this->dryRun) {
			if (file_exists($fileName)) {
				$this->output->write("<fg=red>File {$fileName} exists. You must use force (--force|-f) to recreate.</>", true);
			} elseif ($projectFilesPath === '') {
				$this->errors[] = "Cannot save file $fileName, directory $this->projectFilesPath is not exists!";

				$this->output->write("<fg=red>Directory $this->projectFilesPath is not exists.</> ", true);
			} else {
				$this->output->write("<fg=green>File {$fileName} is ready for create.</>", true);
			}
		}
		if ($this->printToStdOut) {
			if (! $this->dryRun) {
				$this->output->write("<fg=green>{$fileName}</>", true);
			}

			try {
				$oldFileContent = @file_get_contents($fileName);

				if (false === $oldFileContent) {
					$this->output->writeln($content);
				} else {
					require_once 'SideBySide.php';

					$diff = new \SideBySide();
					$diff->setAffixes(\SideBySide::AFFIXES_CLI);
					echo $diff->compute($oldFileContent, $content)[1];
				}

			} catch (\Exception $e) {
				$this->output->writeln($content);
			}

		}
		if (! $this->dryRun) {
			if ($projectFilesPath === '') {
				$this->errors[] = "Cannot save file $fileName, directory $this->projectFilesPath is not exists!";

				return;
			}

			if ($this->force) {
				try {
					FileSystem::write($fileName, $content);
				} catch (IOException $e) {
					$this->output->write("<fg=red>File {$fileName} cannot be written.</> " . $e->getMessage(), true);
				}
			} else {
				if (! file_exists($fileName)) {
					try {
						FileSystem::write($fileName, $content);
					} catch (IOException $e) {
						$this->output->write("<fg=red>File {$fileName} cannot be written.</> " . $e->getMessage(), true);
					}
				} else {
					$this->output->write("<fg=red>File {$fileName} exists. Use force (--force|-f) if you need recreate the model.</>", true);
				}
			}
		}
	}

	private function resolveNenonPath(): string
	{
		$path = __DIR__ . '/../../../../../modelDefinition/';

		if (is_string($this->input->getOption('def-dir'))) {
			$path = $this->input->getOption('def-dir');
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
