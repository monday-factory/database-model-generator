<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModelGenerator\Command;

use MondayFactory\DatabaseModel\Storage\ALowLevelRelationalDatabaseStorage;
use MondayFactory\DatabaseModelGenerator\Generator\LowLevelDatabaseStorageGenerator;
use Nette\Neon\Neon;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateBasicModel extends Command
{

	public const COMMAND_NAME = 'database-model:generate:basic-model';

	/**
	 * @var array
	 */
	private $definition;

	/**
	 * @var string
	 */
	private $neonName;


	public function __construct()
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->setName(self::COMMAND_NAME);
		$this->setDescription('Generates basic MondayFactory Model files from definition.');

		$this->addArgument('neonName', InputArgument::REQUIRED, 'Name of the neon with definition.');
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): void
	{
		try {
			$this->neonName = $input->getArgument('neonName');
			$neon = $this->resolveNenonPath();
			$this->definition = Neon::decode(file_get_contents($neon));
		} catch (\Exception $e) {
			$output->writeln($e->getMessage());
			return;
		}

		$lowLevelDatabaseStorage = (new LowLevelDatabaseStorageGenerator($this->definition, $this->getNeonName()))->generate();


//		$lowLevelDatabaseStorageNamespace = new PhpNamespace($this->getNamespace('Storage'));
//		$lowLevelDatabaseStorageNamespace->addUse(ALowLevelRelationalDatabaseStorage::class);
//		$lowLevelDatabaseStorageClass = $lowLevelDatabaseStorageNamespace->addClass($this->getDatabaseLowLevelStorageClassName());
//
//		$lowLevelDatabaseStorageClass->setExtends(ALowLevelRelationalDatabaseStorage::class);
//
//		$lowLevelDatabaseStorageClass->addProperty('tableName', $this->definition['databaseTableId'])
//			->setVisibility('protected')
//			->addComment("\n@var string");
//
//
//		$lowLevelDatabaseStorageClass->addProperty('idField', $this->definition['databaseTable'])
//			->setVisibility('protected')
//			->addComment("\n@var string|int");
//
//
//		$lowLevelDatabaseStorageClass->addProperty('rowFactoryClass', $this->getRowFactoryNamespace() . '\\' . $this->getRowFactoryClassName())
//			->setVisibility('protected')
//			->addComment("\n@var string");
//
//
//		$lowLevelDatabaseStorageClass->addProperty('collectionFactory',  $this->getCollectionFactoryNamespace() . '\\' . $this->getCollectionFactoryClassName())
//			->setVisibility('protected')
//			->addComment("\n@var string");

//		dump($this->getRowFactoryClassName());

		echo $lowLevelDatabaseStorage;

		return;
	}

	private function resolveNenonPath()
	{
		$filePath = realpath(__DIR__. '/../../../../../modelDefinition/' . $this->getNeonName() . '.neon');

		if (file_exists($filePath))
		{
			return $filePath;
		}

		throw new \InvalidArgumentException("File {$filePAth} not found.");
	}

	private function getNeonName()
	{
		return  preg_replace('/.neon/', '', $this->neonName);
	}

}
