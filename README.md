# Cake Foundation

Reusable CakePHP 5 infrastructure for user identity, account lifecycle, authentication integration,
ULIDs, request actor context, and `created_by`/`modified_by` auditing.

Foundation deliberately does not define product roles, profiles, permissions, layouts, or business
onboarding. Applications extend users with associated profile tables and lifecycle event listeners.

## Install in a host application

Load `Foundation` as a CakePHP plugin and run its migrations:

```bash
bin/cake migrations migrate -p Foundation
```

Plugin account routes are disabled by default. Enable them in host configuration with:

```php
Configure::write('Foundation.routes.enabled', true);
```

The host application remains responsible for adding authentication middleware and using
`Foundation\Authentication\AuthenticationServiceFactory` from its
`AuthenticationServiceProviderInterface` implementation.

See `config/foundation.php` for safe defaults and extension points.
