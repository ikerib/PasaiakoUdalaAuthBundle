# Events

The bundle dispatches two events during the authentication process that allow you to customize behavior without modifying the bundle code.

## LdapGroupsLoadedEvent

Dispatched after loading LDAP groups and mapping them to roles, but **before** creating the `LdapUser` object. Allows modifying the roles assigned to the user.

**Class**: `PasaiaUdala\AuthBundle\Event\LdapGroupsLoadedEvent`

**When dispatched**: In both authenticators (LDAP and Certificate), just before returning the `LdapUser`.

**Available methods**:

| Method | Description |
|--------|-------------|
| `getUsername(): string` | Username |
| `getGroups(): array` | User's LDAP groups (array of CNs) |
| `getRoles(): array` | Current roles (result of mapping) |
| `setRoles(array $roles): void` | Replace the roles |
| `getAuthMethod(): string` | Authentication method: `'ldap'` or `'certificate'` |

### Example: add a custom role

```php
<?php

namespace App\EventListener;

use PasaiaUdala\AuthBundle\Event\LdapGroupsLoadedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class AddCustomRoleListener
{
    public function __invoke(LdapGroupsLoadedEvent $event): void
    {
        $roles = $event->getRoles();

        // Add ROLE_CERTIFICATE to users who authenticate by certificate
        if ($event->getAuthMethod() === 'certificate') {
            $roles[] = 'ROLE_CERTIFICATE';
        }

        // Add extra role based on LDAP group
        if (in_array('informatika', $event->getGroups(), true)) {
            $roles[] = 'ROLE_SUPER_ADMIN';
        }

        $event->setRoles(array_unique($roles));
    }
}
```

---

## PostAuthenticationEvent

Dispatched after a successful authentication (after the security token is created). Allows running post-login actions such as audit logs, database synchronization, etc.

**Class**: `PasaiaUdala\AuthBundle\Event\PostAuthenticationEvent`

**When dispatched**: In `onAuthenticationSuccess()` of both authenticators.

**Available methods**:

| Method | Description |
|--------|-------------|
| `getUser(): LdapUser` | The authenticated user object |
| `getAuthMethod(): string` | Authentication method: `'ldap'` or `'certificate'` |

### Example: audit log

```php
<?php

namespace App\EventListener;

use PasaiaUdala\AuthBundle\Event\PostAuthenticationEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class AuditLoginListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(PostAuthenticationEvent $event): void
    {
        $user = $event->getUser();

        $this->logger->info('User logged in', [
            'username' => $user->getUserIdentifier(),
            'method' => $event->getAuthMethod(),
            'roles' => $user->getRoles(),
            'certificate' => $user->isCertificateAuthenticated(),
        ]);
    }
}
```

### Example: sync user to database

```php
<?php

namespace App\EventListener;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PasaiaUdala\AuthBundle\Event\PostAuthenticationEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class SyncUserListener
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function __invoke(PostAuthenticationEvent $event): void
    {
        $ldapUser = $event->getUser();
        $username = $ldapUser->getUserIdentifier();

        // Find or create user in local database
        $user = $this->userRepository->findOneBy(['username' => $username]);

        if ($user === null) {
            $user = new User();
            $user->setUsername($username);
            $this->em->persist($user);
        }

        // Update data from LDAP
        $user->setDisplayName($ldapUser->getDisplayName());
        $user->setEmail($ldapUser->getMail());
        $user->setDepartment($ldapUser->getDepartment());
        $user->setLastLoginAt(new \DateTimeImmutable());
        $user->setLastLoginMethod($event->getAuthMethod());

        $this->em->flush();
    }
}
```
