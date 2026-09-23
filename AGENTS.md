# AGENTS.md

## Project Overview

Foundation is a reusable CakePHP plugin providing common application infrastructure used across multiple CakePHP projects.

The goal of Foundation is to eliminate repetitive application bootstrap work while providing a consistent implementation of common functionality such as user identity, authentication, account lifecycle, authorization infrastructure, auditing, and other cross-application concerns.

Foundation must remain application-independent.

Do not introduce concepts, fields, workflows, or dependencies that belong to a specific consuming application.

## Supported Environment

* PHP 8.4+
* CakePHP 5.x
* Composer
* MariaDB/MySQL-compatible databases unless otherwise documented

When introducing dependencies, prefer CakePHP functionality and existing project dependencies over adding new packages.

Do not add a new Composer dependency without a clear technical reason.

## Architectural Principles

### Keep Foundation Generic

Foundation contains functionality that is broadly reusable across applications.

Examples of appropriate Foundation concerns include:

* User identity
* Authentication
* Account registration
* Account activation and email verification
* Password reset
* Authorization infrastructure
* Persistent authentication
* ULID infrastructure
* Current actor tracking
* `created_by` / `modified_by` auditing
* Common account lifecycle email functionality

Foundation must not contain application-specific domain concepts.

When uncertain whether functionality belongs in Foundation, prefer keeping it in the consuming application until there is a clear reusable abstraction.

### User Model

The Foundation `User` model represents identity and account-level information.

Do not add application-specific profile information to the Foundation user model.

Consuming applications should extend users through associations to their own domain models.

For example:

```text
User
 ├── ProviderProfile
 └── CustomerProfile
```

Foundation should provide extension points rather than requiring consuming applications to modify Foundation source code.

Before changing how applications extend Foundation models, read `docs/EXTENDING.md`.

### CakePHP Conventions

Prefer CakePHP conventions and framework capabilities over custom implementations.

As a general rule:

* Controllers coordinate HTTP requests and application workflows.
* Table classes handle persistence, queries, associations, validation, and finders.
* Entities handle record-level transformations and behavior.
* Policies handle authorization decisions.
* Middleware handles request-level cross-cutting concerns.
* Services may be used when business or infrastructure logic does not naturally belong to a controller, table, entity, policy, or middleware component.

Avoid unnecessary abstraction.

Do not introduce additional architectural layers solely for theoretical separation when the existing CakePHP architecture adequately handles the requirement.

## Coding Standards

Follow the repository's `phpcs.xml` configuration.

In addition:

* Use tabs for indentation. Do not use spaces for indentation.
* Place opening braces on the same line as class, function, method, conditional, loop, and other block declarations.
* Follow existing naming conventions before introducing new ones.
* Use strict and explicit typing where appropriate.
* Prefer readable code over clever or overly abstract implementations.
* Do not reformat unrelated code when modifying a file.
* Do not make unrelated changes while implementing a task.
* Preserve backwards compatibility unless the task explicitly requires a breaking change.

Preferred formatting:

```php
public function authenticate(string $username, string $password): bool {
	if (!$this->isValid($username)) {
		return false;
	}

	return $this->verifyPassword($password);
}
```

## Security

Security-sensitive functionality should be treated conservatively.

Never:

* commit secrets, credentials, API keys, private keys, or local environment files;
* hard-code credentials or cryptographic secrets;
* log passwords, authentication tokens, reset tokens, or other sensitive credentials;
* weaken authentication or authorization behavior merely to simplify implementation.

Authentication, password handling, account activation, password reset, persistent authentication, and authorization changes require appropriate tests.

Use CakePHP security functionality and established project patterns where available rather than implementing custom cryptography.

## Database and Migrations

Database schema changes must be implemented through CakePHP migrations.

Do not manually alter schema assumptions without an accompanying migration.

Keep Foundation migrations limited to schema required by Foundation itself.

Application-specific tables and fields belong in the consuming application's migrations.

Maintain established identifier and auditing conventions.

## Extension and Compatibility

Foundation is intended to be consumed by multiple applications.

Public APIs, events, configuration keys, extension points, migrations, and documented behavior should therefore be treated as compatibility-sensitive.

Before changing an existing extension mechanism, consult:

`docs/EXTENDING.md`

Prefer providing extension points over requiring applications to fork, copy, or modify Foundation code.

## Tests

New functionality and bug fixes should include appropriate automated tests.

Tests should cover behavior rather than implementation details whenever practical.

Pay particular attention to:

* authentication;
* authorization;
* account registration;
* account activation;
* password reset;
* auditing;
* security boundaries;
* migrations;
* application extension points.

## Scope of Changes

When implementing a task:

1. Inspect the existing implementation and relevant documentation first.
2. Identify the established project pattern that most closely matches the requested change.
3. Prefer modifying or extending that pattern rather than introducing a parallel implementation.
4. Make the smallest coherent change that satisfies the requirement.
5. Add or update tests.
6. Review the resulting diff for unrelated changes.

Do not perform broad refactoring unless explicitly requested or necessary for the requested change.

If existing code appears broken or architecturally problematic but fixing it is outside the requested scope, identify the issue rather than silently refactoring it.

## Documentation

`AGENTS.md` defines instructions for agents working on this repository. Detailed technical documentation belongs under `docs/`.

Consult relevant documentation before changing the systems it describes.

Current documentation includes:

* `README.md` — project purpose, installation, and basic usage
* `docs/EXTENDING.md` — supported extension mechanisms and integration with consuming applications

Additional subsystem documentation should be added under `docs/` as Foundation evolves.

When behavior, configuration, installation, or a public extension point changes, update the relevant documentation as part of the change.

## Working With This Repository

Before writing code:

* Read this file.
* Read documentation relevant to the requested subsystem.
* Inspect the existing implementation.
* Inspect relevant tests.
* Understand how the proposed change affects consuming applications.

Do not assume that code from a consuming application belongs in Foundation simply because similar functionality could potentially be reused.

Foundation exists to provide a stable, minimal, reusable base—not to become a collection of every feature used by every application.
