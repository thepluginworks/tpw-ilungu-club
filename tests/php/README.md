# iLungu Club PHP Integration Tests

This is a test-only WordPress PHPUnit harness. Its Composer dependencies live in this directory and are not part of the plugin runtime.

1. Copy `.env.example` to `.env.local` and provide a disposable test database and local WordPress core path.
2. Run `composer install` from this directory.
3. Run `composer test:upload-pages`.

`TPW_TEST_DB_NAME` must not point to the normal iLungu Club Local database. The harness loads the plugin from the repository root and uses the `wptests_` table prefix by default.