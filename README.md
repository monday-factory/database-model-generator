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
      toString: 'toString()'
      fromString: '\Ramsey\Uuid\Uuid::fromString(?)'
    type:
      type: string
    status:
      type: \T2p\Common\Rancher\Service\StatusEnum
      fromString: '\T2p\Common\Rancher\Service\StatusEnum::get(?)'
      toString: 'getValue()'
  ro:
    created:
      type: \DateTime
      fromString: '\DateTime(?)'
    updated:
      type: \DateTime
      fromString: '\DateTime(?)'
      nullable: true
```

Now you call the generator command.

`vendor/bin/database-model-generator b rancherService -f app`
