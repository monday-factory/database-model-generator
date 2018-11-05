## Install

Add to composer.json

```json
"repositories": {
		"database-model-generator": {
			"type": "vcs",
			"url": "ssh://git@gitlab.mondayfactory.cz:2222/mondayfactory/database-model-generator.git"
		}
	}
```

Install it & enjoy ;-)

`composer require-dev --dev monday-factory/database-model-generator`

Now you must write a simple neon recipe located lives in `modelDefinition` directory.

### Recipe

`/data/modelDefinition/rancherService.neon`

```yaml
namespace: T2p\Common\Rancher\Service
databaseTable: token_rancher_service_status
databaseTableId:
databaseCols:
	rw:
		token_uuid:
			type: \Ramsey\Uuid\UuidInterface
		type:
			type: string
		status:
			type: \T2p\Common\Rancher\Service\StatusEnum
	ro:
		created:
			type: \DateTime
		updated:
			type: \DateTime
```

Now you call the generator command.

`php vendor/monday-factory/database-model-generator/src/bin/generator d:g:b rancherService -f app`

### Result

#### Collection
```php
<?php

declare(strict_types=1);

namespace T2p\Common\Rancher\Service\Collection;

use MondayFactory\DatabaseModel\Colection\BaseDatabaseDataCollection;
use MondayFactory\DatabaseModel\Colection\IDatabaseDataCollection;
use T2p\Common\Rancher\Service\Data\RancherServiceData;

class RancherServiceCollection extends BaseDatabaseDataCollection
{
	/**
	 * @param array $data
	 *
	 * @return IDatabaseDataCollection
	 */
	public static function create(iterable $data): IDatabaseDataCollection
	{
		return new static($data, RancherServiceData::class);
	}
}

```

#### Data object
```php
<?php

declare(strict_types=1);

namespace T2p\Common\Rancher\Service\Data;

use DateTime;
use MondayFactory\DatabaseModel\Data\IDatabaseData;
use Ramsey\Uuid\UuidInterface;
use T2p\Common\Rancher\Service\StatusEnum;

class RancherServiceData implements IDatabaseData
{
	/**
	 * @var UuidInterface
	 */
	private $tokenUuid;

	/**
	 * @var string
	 */
	private $type;

	/**
	 * @var StatusEnum
	 */
	private $status;

	/**
	 * @var DateTime
	 */
	private $created;

	/**
	 * @var DateTime
	 */
	private $updated;


	/**
	 * @var $tokenUuid
	 * @var $type
	 * @var $status
	 */
	public function __construct(UuidInterface $tokenUuid, string $type, StatusEnum $status)
	{
		$this->tokenUuid = $tokenUuid;
		$this->type = $type;
		$this->status = $status;
	}


	/**
	 * @var iterable $data
	 *
	 * @return IDatabaseData
	 */
	public static function fromData(iterable $data): IDatabaseData
	{
		return new self(
			$data['tokenUuid'],
			$data['type'],
			$data['status']
		);
	}


	/**
	 * @todo Finish implementation.
	 *
	 * @var array $row
	 *
	 * @return IDatabaseData
	 */
	public static function fromRow(array $row): IDatabaseData
	{
		return (new self(
				$data['token_uuid'],
				$data['type'],
				$data['status']
			)
		)
		->setCreated($row['created'])
		->setUpdated($row['updated']);
	}


	/**
	 * @return array
	 */
	public function toArray(): array
	{
		return get_object_vars($this);
	}


	/**
	 * @todo Finish implementation.
	 *
	 * @return array
	 */
	public function toDatabaseArray(): array
	{
		return [
			'token_uuid' => $this->tokenUuid,
			'type' => $this->type,
			'status' => $this->status,
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
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}


	/**
	 * @return StatusEnum
	 */
	public function getStatus(): StatusEnum
	{
		return $this->status;
	}


	/**
	 * @return DateTime
	 */
	public function getCreated(): DateTime
	{
		return $this->created;
	}


	/**
	 * @return DateTime
	 */
	public function getUpdated(): DateTime
	{
		return $this->updated;
	}


	/**
	 * @var UuidInterface
	 */
	public function setTokenUuid(UuidInterface $tokenUuid)
	{
		$this->tokenUuid = $tokenUuid;

		return $this;
	}


	/**
	 * @var string
	 */
	public function setType(string $type)
	{
		$this->type = $type;

		return $this;
	}


	/**
	 * @var StatusEnum
	 */
	public function setStatus(StatusEnum $status)
	{
		$this->status = $status;

		return $this;
	}
}

```

#### Low level database storage
```php
<?php

declare(strict_types=1);

namespace T2p\Common\Rancher\Service\Storage;

use MondayFactory\DatabaseModel\Storage\ALowLevelRelationalDatabaseStorage;
use T2p\Common\Rancher\Service\Collection\RancherServiceCollection;
use T2p\Common\Rancher\Service\Data\RancherServiceData;

class RancherServiceDatabaseLowLevelStorage extends ALowLevelRelationalDatabaseStorage
{
	/**
	 * @var string
	 */
	protected $tableName = 'token_rancher_service_status';

	/**
	 * @var string|int
	 */
	protected $idField;

	/**
	 * @var string
	 */
	protected $rowFactoryClass = RancherServiceData::class;

	/**
	 * @var string
	 */
	protected $collectionFactory = RancherServiceCollection::class;
}

```

