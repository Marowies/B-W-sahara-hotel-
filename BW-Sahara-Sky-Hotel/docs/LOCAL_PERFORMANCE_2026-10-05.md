# Local service performance laboratory — 5 October 2026

This is a bounded experiment on actual hotel search, availability, room pricing and checkout classes with synthetic InnoDB data. **It is not a full Laravel/CMS HTTP benchmark, a production load test, a sustained capacity certification or evidence that Hostinger Premium supports 1000 users.** Full CMS boot was blocked by the Windows sandbox's cache-file operations; see the payment report for the setup limit.

The laboratory has four loopback PHP development-server workers behind a loopback Node dispatcher with one active request per worker. It reuses the isolated regression bootstrap/helpers, omits CMS templates/slugs/amenities, authentication/CSRF middleware, administration and external providers, and records synthetic bookings only. Do not expose its router or dispatcher publicly or put them under the production document root.

Routes: room list, room details, availability search, price quote and checkout. Search/availability/pricing use the production services; checkout uses the actual CheckoutRequest validation and PublicController transaction. One hundred additional synthetic room rows are prepared; the checkout target has artificially large capacity to avoid treating expected sold-out responses as transport failures. This is not the real hotel's inventory or dataset. All database configurations must use loopback and a disposable `hotel_test_` name.

Each configuration first takes five sequential samples per route, then ramps through **25, 50, 100, 250, 500 and 1000 virtual users**. Each user has one request in flight and a 100ms think delay; the route mix includes 10% synthetic writes. A stage dispatches for five seconds and then drains outstanding requests. Timeout is 15 seconds. Latency includes queueing; stage throughput includes drain time. Successful throughput is reported separately from all responses/timeouts, so fast errors are not counted as useful capacity. These short stages are overload probes, not soak tests, browser sessions or field Core Web Vitals.

Two fresh, equivalently prepared synthetic schemas were used. The second configuration enabled OPcache for the same four PHP workers; diagnostics confirmed an enabled cache, 442 cached scripts and about 99.92% hits without restarts. The host is shared with other local work, so differences between runs are observations, not a controlled estimate of the cache's causal effect.

| Users | Before p95 ms | Before errors | Before successful req/s | OPcache p95 ms | OPcache errors | OPcache successful req/s |
| ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| 25 | 3166.33 | 0% | 8.83 | 2952.83 | 0% | 8.88 |
| 50 | 4239.93 | 0% | 11.69 | 5518.52 | 0% | 9.51 |
| 100 | 8267.00 | 0% | 11.79 | 5923.52 | 0% | 16.22 |
| 250 | 15041.55 | 34.97% | 9.96 | 13314.10 | 0% | 18.19 |
| 500 | 15156.84 | 68.71% | 8.68 | 15138.64 | 55.16% | 13.19 |
| 1000 | 15370.57 | 89.26% | 5.48 | 15326.84 | 74.53% | 13.59 |

## What the evidence supports

The dispatcher queue dominates high-load latency: the optimized run's successful requests at 1000 users had queue p95 about 14.16s. OPcache alone did not remove this worker/runtime bottleneck. Even zero-error low stages have multi-second p95, so zero errors must not be described as acceptable user experience.

Before-cache sequential baseline: room list 4 SQL queries, details/quote 3, availability 6 and checkout 14. Typical application times were hundreds of milliseconds while database time was usually a small part of that total. These counters do not reveal every production bottleneck: the schema is small, synthetic and different from the real CMS. No speculative production index or caching change was made from these results.

The required next benchmark is a real local/staging CMS runtime with its normal PHP/FastCGI/OPcache setup, representative private data and media, exact worker/DB limits, real HTTP middleware, validated endpoint semantics, monitoring and longer stable stages. Establish an acceptable latency/error target with the hotel. Only a separately authorized staging test near the Hostinger configuration can assess the hosting plan.

## Reproduce safely

Prepare a new synthetic schema using the integration runner, then run `php tests/Performance/seed.php PRIVATE_TEST_CONFIG`. Set `HOTEL_PHP_PATH` and `HOTEL_PERF_DB_CONFIG`, then start `node tests/Performance/lab.mjs`. It binds only to 127.0.0.1. `HOTEL_PERF_OPCACHE=1` enables the measured local runtime option.

Run `node tests/Performance/load.mjs`, optionally setting `HOTEL_LOAD_OUTPUT` to a local output file. The load client refuses a target other than HTTP 127.0.0.1. Optional `HOTEL_LOAD_STAGE_MS` and `HOTEL_LOAD_TIMEOUT_MS` tune duration/timeout; retain these settings in any result description. Stop the lab after use and remove only your explicitly identified disposable databases. No database dump, private configuration or runtime secret belongs in Git.
