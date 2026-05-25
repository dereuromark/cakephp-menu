<?php

declare(strict_types=1);

namespace Menu\Resolver;

use Cake\Core\InstanceConfigTrait;
use Menu\Item\Item;
use Menu\Item\ItemInterface;
use Psr\Http\Message\ServerRequestInterface;
use function array_intersect_key;
use function array_key_exists;
use function array_merge;
use function array_walk;
use function is_array;
use function is_int;
use function is_numeric;
use function ksort;
use const SORT_STRING;

class UrlArrayResolver implements ContextAwareResolverInterface
{
    use InstanceConfigTrait;
    use RuntimeStateTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'fuzzy' => true,
        'maxDepth' => null,
    ];

    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function __construct(
        protected ServerRequestInterface $request,
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

        $routes = $this->extractRoutes($item);
        if ($routes === []) {
            return;
        }

        $requestParams = (array)$this->request->getAttribute('params');
        foreach ($routes as $route) {
            if ($this->matches($requestParams, $route, $item)) {
                $this->applyActive($item);

                return;
            }
        }
    }

    /**
     * @phpstan-param array<string, mixed> $requestParams
     * @phpstan-param array<string|int, mixed> $linkArray
     */
    protected function matches(array $requestParams, array $linkArray, ItemInterface $item): bool
    {
        $normalizedRequestParams = $this->extractParams($requestParams);
        $normalizedRoute = $this->normalizeRoute($linkArray);

        if (isset($normalizedRoute['_name'])) {
            if (($normalizedRequestParams['_name'] ?? null) !== $normalizedRoute['_name']) {
                return false;
            }

            unset($normalizedRoute['_name'], $normalizedRequestParams['_name']);
            if ($normalizedRoute === []) {
                return true;
            }
        }

        $fuzzy = $item instanceof Item
            ? $item->isFuzzyMatch()
            : false;
        if (!$fuzzy) {
            $fuzzy = (bool)$this->getConfig('fuzzy');
        }

        if ($fuzzy) {
            if (isset($normalizedRoute['?'])) {
                if (array_intersect_key($normalizedRequestParams['?'], $normalizedRoute['?']) !== $normalizedRoute['?']) {
                    return false;
                }

                unset($normalizedRoute['?']);
            }

            return array_intersect_key($normalizedRequestParams, $normalizedRoute) === $normalizedRoute;
        }

        $exactRoute = $this->canonicalizeForExactMatch($normalizedRoute);
        $exactRequest = $this->canonicalizeForExactMatch($normalizedRequestParams);

        // Transport meta is always present on the request but rarely on a link, so only
        // enforce host/method when the route explicitly constrains them.
        foreach (['_host', '_method'] as $meta) {
            if (!array_key_exists($meta, $exactRoute)) {
                unset($exactRequest[$meta]);
            }
        }

        return $exactRequest === $exactRoute;
    }

    /**
     * Reduces a normalized parameter set to its addressable URL parts so that exact
     * (non-fuzzy) matching compares like with like.
     *
     * The query string is kept as an explicit `?` bucket and *also* exposed at the top level
     * (consistently for both route and request). Keeping the bucket means a query parameter
     * whose name collides with a routing key (e.g. `?action=edit`) still participates in the
     * comparison instead of being masked by the top-level routing value. An absent optional
     * segment (`plugin`/`prefix`/`_ext`) shows up as `null`; a link targeting the application
     * root specifies none of these, so they are folded away to let an exact `===` comparison
     * succeed. Truthy values (e.g. an actual plugin name) are kept, so they still differentiate
     * routes.
     *
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    protected function canonicalizeForExactMatch(array $params): array
    {
        $query = isset($params['?']) && is_array($params['?']) ? $params['?'] : [];
        $params['?'] = $query;
        $params += $query;

        foreach (['plugin', 'prefix', '_ext'] as $key) {
            if (array_key_exists($key, $params) && $params[$key] === null) {
                unset($params[$key]);
            }
        }

        ksort($params, SORT_STRING);

        return $params;
    }

    /**
     * Collapses the "empty" forms of optional routing segments to `null`, so that a link
     * using `plugin => false` matches a request that reports `plugin => null` (and likewise
     * for `prefix`/`_ext`). The key is preserved, so an explicit `plugin => false` still does
     * not match a request that is inside a plugin.
     *
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    protected function normalizeEmptyRoutingValues(array $params): array
    {
        foreach (['plugin', 'prefix', '_ext'] as $key) {
            if (array_key_exists($key, $params) && ($params[$key] === false || $params[$key] === '')) {
                $params[$key] = null;
            }
        }

        return $params;
    }

    /**
     * @param array<string, mixed> $requestParams
     *
     * @return array<string, mixed>
     */
    protected function extractParams(array $requestParams): array
    {
        $params = $requestParams;
        $params['?'] = $this->request->getQueryParams();
        $params['_method'] = $this->request->getMethod();
        $params['_host'] = $this->request->getUri()->getHost();
        $route = $params['_route'] ?? null;
        if (is_object($route) && method_exists($route, 'getName')) {
            $params['_name'] = $route->getName();
        }
        if (!isset($params['_ext'])) {
            $params['_ext'] = null;
        }

        $pass = isset($params['pass']) && is_array($params['pass']) ? $params['pass'] : [];
        unset(
            $params['pass'],
            $params['paging'],
            $params['models'],
            $params['url'],
            $params['autoRender'],
            $params['bare'],
            $params['requested'],
            $params['return'],
            $params['isAjax'],
            $params['_Token'],
            $params['_csrfToken'],
            $params['_matchedRoute'],
            $params['_route'],
        );

        $params = array_merge($params, $pass);
        $params += $params['?'];

        return $this->normalizeEmptyRoutingValues($this->normalizeParams($params));
    }

    /**
     * @param array<string|int, mixed> $route
     *
     * @return array<string, mixed>
     */
    protected function normalizeRoute(array $route): array
    {
        unset(
            $route['#'],
            $route['_base'],
            $route['_scheme'],
            $route['_port'],
            $route['_full'],
            $route['_ssl'],
        );

        /** @var array<string, mixed> $route */
        return $this->normalizeEmptyRoutingValues($this->normalizeParams($route));
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    protected function normalizeParams(array $params): array
    {
        ksort($params, SORT_STRING);
        array_walk($params, function (mixed &$value): void {
            if (is_numeric($value)) {
                $value = (string)$value;

                return;
            }
            if (is_array($value)) {
                /** @var array<string, mixed> $value */
                $value = $this->normalizeParams($value);
            }
        });

        return $params;
    }

    /**
     * Collects all array routes for the item: the link itself (when it is an array URL) and any
     * array `matchRoutes`. String/null links still contribute their array match routes, so an
     * alternate Cake-array route is honored regardless of the primary link type.
     *
     * @return list<array<string|int, mixed>>
     */
    protected function extractRoutes(ItemInterface $item): array
    {
        $routes = [];

        $link = $item->getLink();
        if ($link !== null) {
            $rawUrl = $link->getRawUrl();
            if (is_array($rawUrl)) {
                $routes[] = $rawUrl;
            }
        }

        if ($item instanceof Item) {
            foreach ($item->getMatchRoutes() as $route) {
                if (is_array($route)) {
                    $routes[] = $route;
                }
            }
        }

        return $routes;
    }
}
