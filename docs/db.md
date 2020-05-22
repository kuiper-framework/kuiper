# Kuiper ORM 

model 声明：

```php
<?php

use kuiper\db\annotation\Id;
use kuiper\db\annotation\GeneratedValue;
use kuiper\db\annotation\Enumerated;
use kuiper\di\annotation\Repository;

class User
{
/**
 * @Id
 * @GeneratedValue
 */
    private $id;

/**
 * @var string
 */
private $name;

/**
 * @Enumerated("STRING")
 * @var UserStatus 
 */
private $status;
}

class UserStatus extends \kuiper\helper\Enum
{
    public const ENABLED = 'enabled';
    public const DISABLED = 'disabled';
}

/**
 * @Entity(User::class)
 */
interface UserRepositoryInterface extends CrudRepository
{
}

/**
 * @Repository 
 */
class UserRepository extends AbstractRepository implements UserRepositoryInterface
{
}

class UserService
{
/**
 * @var UserRepositoryInterface
 */
    private $userRepository;

public function findUser(int $userId): User
{
return $this->userRepository->findById($userId);
}
}
```