<?php

declare(strict_types=1);

namespace Menu\Resolver;

use Cake\Core\InstanceConfigTrait;
use Menu\Item\Item;
use Menu\Item\ItemInterface;
use Menu\Item\StateResetInterface;
use Psr\Http\Message\RequestInterface;
use function array_filter;
use function array_merge;
use function array_pad;
use function explode;
use function is_int;
use function is_string;
use function parse_url;
use function sort;
use function strcasecmp;
use function strtolower;
use function urldecode;
use const SORT_STRING;

class Psr7UrlResolver implements ContextAwareResolverInterface
{
    use InstanceConfigTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'ignoreQueryString' => true,
        'maxDepth' => null,
    ];

    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function __construct(
        protected RequestInterface $request,
        array $options = [],
    ) {
        $this->setConfig($options);
    }

    public function resolve(ItemInterface $item): void
    {
        $this->resolveWithContext($item, new ResolverContext());
    }

    public function resolveWithContext(ItemInterface $item, ResolverContext $context): void
    {
        $maxDepth = $this->getConfig('maxDepth');
        if (is_int($maxDepth) && $context->getDepth() > $maxDepth) {
            return;
        }

        $uri = $this->request->getUri();
        $requestPath = $uri->getPath();
        $requestQuery = $uri->getQuery();
        $ignoreQueryString = $item instanceof Item && $item->getIgnoreQueryString() !== null
            ? $item->getIgnoreQueryString()
            : (bool)$this->getConfig('ignoreQueryString');

        foreach ($this->extractRoutes($item) as $route) {
            if (!is_string($route)) {
                continue;
            }

            $parts = parse_url($route);
            if ($parts === false) {
                continue;
            }

            // Match path (and query) separately rather than the full URI string: the request
            // URI is absolute (scheme + host), whereas a link is usually a base-relative string.
            // An absolute route must additionally agree on scheme/host/port so a link to another
            // host never lights up on a same-path local request.
            if (isset($parts['host']) && !$this->hostMatches($parts)) {
                continue;
            }
            if ($requestPath !== ($parts['path'] ?? '')) {
                continue;
            }
            if (!$ignoreQueryString && !$this->queryMatches($requestQuery, $parts['query'] ?? '')) {
                continue;
            }

            if ($item instanceof StateResetInterface) {
                $item->setRuntimeActive(true);
            } else {
                $item->setActive(true);
            }

            return;
        }
    }

    /**
     * Confirms an absolute route's scheme/host/port agree with the current request.
     *
     * @param array<string, mixed> $parts Components from `parse_url()` of the route.
     */
    protected function hostMatches(array $parts): bool
    {
        $uri = $this->request->getUri();
        if (strcasecmp((string)$parts['host'], $uri->getHost()) !== 0) {
            return false;
        }
        if (isset($parts['scheme']) && strcasecmp((string)$parts['scheme'], $uri->getScheme()) !== 0) {
            return false;
        }
        if (isset($parts['port'])) {
            // PSR-7 reports a scheme's default port as null, so normalize an explicit default
            // port (80 for http, 443 for https) before comparing.
            $scheme = isset($parts['scheme']) ? strtolower((string)$parts['scheme']) : $uri->getScheme();
            $defaultPort = $scheme === 'https' ? 443 : ($scheme === 'http' ? 80 : null);
            $routePort = $parts['port'] === $defaultPort ? null : $parts['port'];
            if ($routePort !== $uri->getPort()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Compares two query strings as order-insensitive parameter sets.
     *
     * Works on the raw key/value pairs (decoded) rather than `parse_str()`, which is lossy: it
     * collapses repeated keys (`a=1&a=2`) and rewrites keys such as `a.b` to `a_b`. Strict query
     * matching must keep those distinct.
     */
    protected function queryMatches(string $requestQuery, string $routeQuery): bool
    {
        return $this->normalizeQuery($requestQuery) === $this->normalizeQuery($routeQuery);
    }

    /**
     * @return list<string>
     */
    protected function normalizeQuery(string $query): array
    {
        if ($query === '') {
            return [];
        }

        $pairs = [];
        foreach (explode('&', $query) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
            $pairs[] = urldecode($key) . '=' . urldecode($value);
        }
        sort($pairs, SORT_STRING);

        return $pairs;
    }

    /**
     * @return list<array<string|int, mixed>|string>
     */
    protected function extractRoutes(ItemInterface $item): array
    {
        $routes = [];
        if ($item instanceof Item) {
            $routes = $item->getMatchRoutes();
        }

        $link = $item->getLink();
        if ($link === null) {
            return $routes;
        }

        $rawUrl = $link->getRawUrl();

        return array_merge(
            $routes,
            array_filter([$rawUrl], static fn (mixed $route): bool => is_string($route)),
        );
    }
}
