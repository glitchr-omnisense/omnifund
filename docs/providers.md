# Writing a provider

A provider is a package `omnifund/<name>` with a factory and a provider class:

- the factory extends `Omnifund\ProviderFactory`: `populate()` names it
  (`omnifund.factory_name`), lists its required options (`omnifund.required_options`) and the
  defaults of the others; `build()` returns the provider. The HTTP client comes through the
  factory's constructor (`Symfony\Contracts\HttpClient\HttpClientInterface`), never created
  inside.
- the provider implements the contracts it can answer - `ProfileInterface`, `SponsorsInterface`,
  `TiersInterface` - and says so in `capabilities()`, with whether a key is needed and the terms
  that apply. A platform that keeps its sponsors private (Liberapay) implements the profile only;
  the registry then refuses `sponsors()` with a `NotSupportedException`.
- every call goes through `Http\Json::request()`, which turns a network failure into an
  `UnreachableException` and an error status into a `ProviderException`; a GraphQL `errors`
  member is a `ProviderException` too. `Http\Json`'s typed readers walk an answer without
  trusting its shape.
- amounts are `Model\Money` in hundredths of the currency, with the `Model\Period` the platform
  counts in (Liberapay: the week; GitHub: the month; Open Collective's budget: the year).
- tests run in the core's harness (`docker/harness/plugins.php` lists the providers), on
  recorded answers (`MockHttpClient`), with a `BareTest` loading every class with the package's
  requirements alone, and a `LiveTest` (group `live`) where the platform can be read without an
  account.
