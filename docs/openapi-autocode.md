# OpenAPI field automation

The **OpenAPI Field Automation** workflow can prepare a draft PR for a narrow class of Discord API changes. A maintainer can add the `openapi-autocode` label to an OpenAPI report issue or run the workflow manually.

It compares the stable spec at `scripts/openapi-baseline.json` with the latest stable spec. The initial mapping covers `MessageResponse` and `GuildRoleResponse`. For a new, unformatted string, integer, or boolean response property that is not already modeled, it adds a docblock property and a `$fillable` entry to the mapped Part.

The workflow does not consume issue text, touch preview-only fields, generate repository/request behavior, infer accessors or nested Part types, or rewrite existing model fields. It skips refs, arrays, enums, formatted values, schema changes, and properties that already appear in a model. Those changes still need a maintainer to implement and review. Generated changes are opened as a draft PR; they are not merged automatically.

The workflow runs its focused generator tests and the unit suite on the generated files before it creates the PR. Since PRs created with `GITHUB_TOKEN` do not start a `pull_request` workflow run, it explicitly dispatches the existing `tests.yml` workflow on the PR branch so the normal matrix, syntax, and style checks run. Human review is still required.
