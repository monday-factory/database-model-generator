## Dependency tree

```
	- Collection\IDatabaseDataCollection
	| └ Collection\BaseDatabaseDataCollection
	|
	- Data\IDatabaseData
	|
	- Storage\ILowLevelRelationalDatabaseStorage
	  └ Storage\ALowLevelRelationalDatabaseStorage
	- Storage\IRelationalDatabaseStorage
```

## Concept hierarchy

```
                                Presenter
                                    |
                                 Mediator
                                /        \
                  MiddleLevelApi          SomeClient/Api
                       /
                 LowLevelApi
```

## Basic usage

### Composer

```json
{
	"type": "project",
	"autoload": {
		"psr-4": {
			"App\\": "app/"
		}
	},
	"repositories": {
		"database-model": {
			"type": "vcs",
			"url": "ssh://git@gitlab.mondayfactory.cz:2222/mondayfactory/database-model.git"
		}
	},
	"require": {
		"monday-factory/database-model": "dev-master"
	}
}

```


### Data class

This Data class have two properties. $tokenUuid passed by user via constructor and $updated when can be passed only in $data via method fromRow. From row is factory used by Storage\ALowLevelRelationalDatabaseStorage in data fetching process.

```php
<?php

declare(strict_types=1);

namespace T2p\Common\Rancher\Service\Data;

use Dibi\Row;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use T2p\Common\AbstractModel\Data\IDatabaseData;
use T2p\Common\Rancher\Service\StatusEnum;

class StatusData implements IDatabaseData
{

	/**
	 * @var UuidInterface
	 */
	private $tokenUuid;

	/**
	 * @var \DateTime
	 */
	private $updated;

	/**
	 * @param UuidInterface $tokenUuid
	 */
	public function __construct(UuidInterface $tokenUuid)
	{
		$this->tokenUuid = $tokenUuid;
	}

	/**
	 * @param iterable $data [<br>
	 * 		UuidInterface tokenUuid,<br>
	 * ]
	 *
	 * @return IDatabaseData
	 */
	public static function fromData(iterable $data): IDatabaseData
	{
		return new self(
			$data['tokenUuid']
		);
	}

	/**
	 * @param Row $row
	 *
	 * @return $this
	 * @throws \Exception
	 */
	public static function fromRow(iterable $row): IDatabaseData
	{
		return (new self(
					Uuid::fromString($row['token_uuid'])
				)
			)
			->setUpdated($row['updated']);
	}

	public function toArray(): array
	{
		return get_object_vars($this);
	}

	/**
	 * @return array
	 */
	public function toDatabaseArray(): array
	{
		return [
			'tokenUuid' => $this->tokenUuid->toString(),
		];
	}

	/**
	 * @return UuidInterface
	 */
	public function getTokenUuid(): UuidInterface
	{
		return $this->tokenUuid;
	}

	/**
	 * @return \DateTimeImmutable
	 */
	public function getUpdated(): \DateTimeImmutable
	{
		return $this->updated;
	}

	/**
	 * @var \DateTimeImmutable $updated
	 */
	private function setUpdated(\DateTimeImmutable $updated)
	{
		$this->updated = $updated;
		return $this;
	}

}

```

### Collection factory

For collection is needed implement only one method ::create(). This factory is interaly called by Storage\ALowLevelRelationalDatabaseStorage in data fetching process.

```php
<?php

declare(strict_types=1);

namespace T2p\Common\Rancher\Service\Collection;

use T2p\Common\AbstractModel\Colection\BaseDatabaseDataCollection;
use T2p\Common\AbstractModel\Colection\IDatabaseDataCollection;
use T2p\Common\Rancher\Service\Data\StatusData;

class StatusCollection extends BaseDatabaseDataCollection
{
	/**
	 * @param array $data
	 *
	 * @return StatusCollection
	 */
	public static function create(array $data, ?string $idField = null): IDatabaseDataCollection
	{
		return new static($data, StatusData::class, $idField);
	}
}

```

### LowLevelDatabaseStorage instance

This instance You use in High level Storage for communication with database. You must implement three arguments: 
 - `$tableName` // Database table name. 
 - `$rowFactoryClass` // `StatusData` defined above.
 - `$collectionFactory` // `StatusCollection` defined above.
 
 and optional
 
 - `$idField` // Value of this database field is used as key in `Collection\IDatabaseDataCollection` if is set, otherwise is array indexed from zero. If is not `$idField` value of result set unique will be thrown `UnexpectedValueException`.

```php
<?php

declare(strict_types=1);

namespace T2p\Common\Rancher\Service\Storage;

use T2p\Common\AbstractModel\Storage\ALowLevelRelationalDatabaseStorage;
use T2p\Common\Rancher\Service\Collection\StatusCollection;
use T2p\Common\Rancher\Service\Data\StatusData;

class StatusDatabaseLowLevelStorage extends ALowLevelRelationalDatabaseStorage
{
	/**
	 * @var string
	 */
	protected $tableName = 'token_rancher_service_status';

	/**
	 * @var string
	 */
	protected $rowFactoryClass = StatusData::class;

	/**
	 * @var string
	 */
	protected $collectionFactory = StatusCollection::class;

	/**
	 * @var scalar
	 */
	protected $idField;

}

```

### DatabaseStorage

DatabaseStorage is MiddleLevel database storage. In this class are impelemented methods e.g. `public function getShouldActiveContainersByType(string $containerType)`

```php
<?php

declare(strict_types=1);

namespace T2p\Common\Rancher\Service\Storage;

use MondayFactory\DatabaseModel\Colection\IDatabaseDataCollection;
use MondayFactory\DatabaseModel\Storage\IRelationalDatabaseStorage;
use MondayFactory\DatabaseModel\Storage\IStorage;

class StatusDatabaseStorage implements IRelationalDatabaseStorage
{
	/**
	 * @var StatusDatabaseLowLevelStorage
	 */
	private $storage;

	/**
	 * @param StatusDatabaseLowLevelStorage $lowLevelStorage
	 */
	public function __construct(StatusDatabaseLowLevelStorage $storage)
	{
		$this->storage = $storage;
	}

	/**
	 * @return IDatabaseDataCollection
	 */
	public function getActiveServices(): IDatabaseDataCollection
	{
		return $this->storage->findByCriteria([
				'status = "new"',
			]
		);
	}

}

```

### Mediator

Mediator is high level api (Facade). Mediator uses e.g. `Rancher\Client` and `StatusDatabaseStorage` and provide functions for manage operations depened on both apis.

```php
<?php

declare(strict_types=1);

namespace T2p\Common\Rancher\Service;

use T2p\Common\Rancher\Service\Storage\StatusDatabaseStorage;
use Tyldar\Rancher\Client;

class Mediator
{

	/**
	 * @var Client
	 */
	private $rancherClient;

	/**
	 * @var StatusDatabaseStorage
	 */
	private $databaseStorage;

	/**
	 * @param Client $rancher
	 * @param StatusDatabaseStorage $databaseStorage
	 */
	public function __construct(Client $rancher, StatusDatabaseStorage $databaseStorage)
	{
		$this->databaseStorage = $databaseStorage;
		$this->rancherClient = $rancher;

	}

	public function createRecreateMessagesForIllContainers()
	{
		$shouldBeActiveContainers = $this->databaseStorage->getActiveServices();
		$containersStatuses = $this->rancherClient->getAllContainers();

		// compare, create messages etc.
	}

}
```

### config.neon

```yaml
services:
	- T2p\Common\Rancher\Service\Storage\StatusDatabaseLowLevelStorage
	- T2p\Common\Rancher\Service\Storage\StatusDatabaseStorage
	- T2p\Common\Rancher\Service\Mediator
```
